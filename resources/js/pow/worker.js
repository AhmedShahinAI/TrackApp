/**
 * The puzzle, run off the main thread (T358). A module worker that Vite emits as its own hashed,
 * same-origin file — never a blob: or data: URL, which the CSP refuses — so `script-src 'self'`
 * already covers it and every deploy ships a fresh name.
 *
 * Same search as the in-page path: it imports the one definition in ./puzzle.js.
 *
 * In:  { challenge, difficulty, maxNonce? }
 * Out: { progress: tried } … then { nonce } or { error: 'invalid' | 'exhausted' }
 */
import { search } from './puzzle.js';

self.addEventListener('message', async (event) => {
    const { challenge, difficulty, maxNonce } = event.data || {};

    try {
        const nonce = await search(challenge, difficulty, {
            maxNonce,
            onProgress: (tried) => self.postMessage({ progress: tried }),
        });

        self.postMessage({ nonce });
    } catch (error) {
        self.postMessage({ error: error instanceof Error ? error.message : 'invalid' });
    }
});
