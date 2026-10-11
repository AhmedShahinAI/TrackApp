/**
 * T358 — solve a support-form proof-of-work challenge (research.md R-08).
 *
 * Runs ./worker.js off the main thread; solves in-page only where a worker cannot start or load.
 * Both paths run the same `search` from ./puzzle.js, so the same input gives the same nonce and stops
 * at the same cap in either. No network request is made: the challenge comes from the caller (which
 * fetched it from our own GET /support/challenge) and the nonce goes back to it.
 *
 *     import { solve } from './pow/solve';
 *     const nonce = await solve(challenge, difficulty, { signal, onProgress });
 *
 * Rejects with Error('invalid' | 'exhausted' | 'aborted'). The form then refuses to submit and says
 * so — the fail-closed behaviour FR-129 requires.
 *
 * The worker's URL is fixed here, not a parameter: a caller cannot point it anywhere else.
 */
import { isValidPuzzle, search } from './puzzle.js';

/**
 * @param {string} challenge
 * @param {number} difficulty
 * @param {{ maxNonce?: number, signal?: AbortSignal, onProgress?: (tried: number) => void }} [options]
 * @returns {Promise<number>}
 */
export function solve(challenge, difficulty, options = {}) {
    const { signal, onProgress, maxNonce } = options;

    if (!isValidPuzzle(challenge, difficulty)) {
        return Promise.reject(new Error('invalid'));
    }

    // Already cancelled: no work at all, on either path.
    if (signal?.aborted) {
        return Promise.reject(new Error('aborted'));
    }

    const inPage = () => search(challenge, difficulty, { maxNonce, signal, onProgress });

    if (typeof Worker === 'undefined') {
        return inPage();
    }

    return new Promise((resolve, reject) => {
        let worker;

        try {
            worker = new Worker(new URL('./worker.js', import.meta.url), { type: 'module' });
        } catch {
            inPage().then(resolve, reject);
            return;
        }

        const onAbort = () => {
            cleanup();
            reject(new Error('aborted'));
        };

        // Every way out removes the listener and stops the worker, so nothing outlives the call.
        const cleanup = () => {
            signal?.removeEventListener('abort', onAbort);
            worker.terminate();
        };

        signal?.addEventListener('abort', onAbort, { once: true });

        worker.addEventListener('message', (event) => {
            const data = event.data || {};

            if (Number.isInteger(data.progress)) {
                onProgress?.(data.progress);
                return;
            }

            cleanup();
            Number.isInteger(data.nonce) ? resolve(data.nonce) : reject(new Error(data.error ?? 'invalid'));
        });

        // The worker file did not load (blocked, missing): solve here rather than hang or fail open.
        worker.addEventListener('error', () => {
            cleanup();
            inPage().then(resolve, reject);
        });

        worker.postMessage({ challenge, difficulty, maxNonce });
    });
}
