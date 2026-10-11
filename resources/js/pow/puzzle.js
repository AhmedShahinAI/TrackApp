/**
 * The support form's proof-of-work puzzle — the ONE definition the worker and the in-page path both
 * use (research.md R-08, T358). The server's verifier (T370) checks exactly this rule; the README
 * states it for that side.
 *
 * ## The puzzle
 *
 *   challenge   the server's string: base64url, 16–256 characters (`[A-Za-z0-9_-]`), so its UTF-8
 *               bytes are its ASCII bytes and no encoding question arises.
 *   difficulty  an integer, DIFFICULTY_MIN–DIFFICULTY_MAX, chosen and REMEMBERED by the server.
 *   nonce       any integer 0 ≤ n ≤ Number.MAX_SAFE_INTEGER, written in canonical decimal: no sign,
 *               no leading zeros (except "0" itself), no exponent, no fraction.
 *   rule        SHA-256 over the UTF-8 bytes of `challenge + ":" + nonce` begins with at least
 *               `difficulty` zero BITS, counted from the most significant bit of the first byte
 *               across byte boundaries — not hex characters.
 *
 * ANY qualifying nonce is a solution; the server does not ask for the smallest one (proving that
 * would cost it 2^difficulty hashes per request). Verifying costs it one SHA-256.
 *
 * ## Why it is shaped like this
 *
 *   - No network access of any kind: it is handed a challenge and returns a number
 *     (tests/Architecture/ChallengeSolverIsSelfHostedTest.php).
 *   - `crypto.subtle` exists only in secure contexts. Over plain http — a LAN address, a proxy that
 *     lost TLS — a small pure-JS SHA-256 is used instead, so the form still works rather than
 *     failing for a reason the visitor cannot see.
 *   - The loop yields to the event loop every ~16 ms and reports progress, so the in-page path does
 *     not freeze a low-end phone, a status can be announced, and cancellation takes effect.
 */

export const SEPARATOR = ':';
export const DIFFICULTY_MIN = 1;
export const DIFFICULTY_MAX = 32;
export const CHALLENGE_PATTERN = /^[A-Za-z0-9_-]{16,256}$/;

const YIELD_EVERY_MS = 16;

/** Leading zero bits of a byte array, most significant bit first, across byte boundaries. */
export function leadingZeroBits(bytes) {
    let bits = 0;

    for (let i = 0; i < bytes.length; i++) {
        if (bytes[i] === 0) {
            bits += 8;
            continue;
        }

        // Math.clz32 counts within 32 bits; a byte occupies the low 8, hence the 24.
        return bits + Math.clz32(bytes[i]) - 24;
    }

    return bits;
}

/** The exact bytes hashed for one nonce. */
export function encode(challenge, nonce) {
    return new TextEncoder().encode(challenge + SEPARATOR + nonce.toString(10));
}

/** Whether a challenge and difficulty are ones the server could have issued. */
export function isValidPuzzle(challenge, difficulty) {
    return typeof challenge === 'string'
        && CHALLENGE_PATTERN.test(challenge)
        && Number.isInteger(difficulty)
        && difficulty >= DIFFICULTY_MIN
        && difficulty <= DIFFICULTY_MAX;
}

/**
 * The iteration cap: a caller's value if it is a positive safe integer, else 2^(difficulty + 8) —
 * the chance of a solvable puzzle having no solution below that is about e^-256. One rule, used by
 * both paths, so the same input stops at the same place in either.
 */
export function iterationCap(difficulty, maxNonce) {
    if (Number.isSafeInteger(maxNonce) && maxNonce > 0) {
        return maxNonce;
    }

    return Math.min(2 ** Math.min(difficulty + 8, 52), Number.MAX_SAFE_INTEGER);
}

/* ── SHA-256 ──────────────────────────────────────────────────────────────────────────────────── */

const K = new Uint32Array([
    0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5,
    0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3, 0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174,
    0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
    0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967,
    0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13, 0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85,
    0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
    0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3,
    0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208, 0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2,
]);

/** SHA-256 in plain JavaScript (FIPS 180-4), for contexts without crypto.subtle. */
export function sha256Js(message) {
    const bitLength = message.length * 8;
    const padded = new Uint8Array(((message.length + 9 + 63) >> 6) << 6);
    padded.set(message);
    padded[message.length] = 0x80;

    const view = new DataView(padded.buffer);
    // Messages here are far below 2^32 bits; the high word of the length stays zero.
    view.setUint32(padded.length - 4, bitLength >>> 0);

    const h = new Uint32Array([0x6a09e667, 0xbb67ae85, 0x3c6ef372, 0xa54ff53a, 0x510e527f, 0x9b05688c, 0x1f83d9ab, 0x5be0cd19]);
    const w = new Uint32Array(64);
    const rotr = (x, n) => (x >>> n) | (x << (32 - n));

    for (let offset = 0; offset < padded.length; offset += 64) {
        for (let t = 0; t < 16; t++) {
            w[t] = view.getUint32(offset + t * 4);
        }

        for (let t = 16; t < 64; t++) {
            const s0 = rotr(w[t - 15], 7) ^ rotr(w[t - 15], 18) ^ (w[t - 15] >>> 3);
            const s1 = rotr(w[t - 2], 17) ^ rotr(w[t - 2], 19) ^ (w[t - 2] >>> 10);
            w[t] = (w[t - 16] + s0 + w[t - 7] + s1) >>> 0;
        }

        let [a, b, c, d, e, f, g, hh] = h;

        for (let t = 0; t < 64; t++) {
            const t1 = (hh + (rotr(e, 6) ^ rotr(e, 11) ^ rotr(e, 25)) + ((e & f) ^ (~e & g)) + K[t] + w[t]) >>> 0;
            const t2 = ((rotr(a, 2) ^ rotr(a, 13) ^ rotr(a, 22)) + ((a & b) ^ (a & c) ^ (b & c))) >>> 0;
            hh = g;
            g = f;
            f = e;
            e = (d + t1) >>> 0;
            d = c;
            c = b;
            b = a;
            a = (t1 + t2) >>> 0;
        }

        h[0] += a; h[1] += b; h[2] += c; h[3] += d; h[4] += e; h[5] += f; h[6] += g; h[7] += hh;
    }

    const out = new Uint8Array(32);
    const outView = new DataView(out.buffer);

    for (let i = 0; i < 8; i++) {
        outView.setUint32(i * 4, h[i]);
    }

    return out;
}

const subtle = globalThis.crypto && globalThis.crypto.subtle;

/** SHA-256 through the platform where it is available (a secure context), else in JavaScript. */
export async function sha256(bytes) {
    if (subtle) {
        return new Uint8Array(await subtle.digest('SHA-256', bytes));
    }

    return sha256Js(bytes);
}

/* ── The search ───────────────────────────────────────────────────────────────────────────────── */

const yieldToEventLoop = () => new Promise((resolve) => setTimeout(resolve, 0));

/**
 * Find a qualifying nonce.
 *
 * @param {string} challenge
 * @param {number} difficulty
 * @param {{ maxNonce?: number, signal?: AbortSignal, onProgress?: (tried: number) => void }} [options]
 * @returns {Promise<number>}
 * @throws {Error} 'invalid' | 'exhausted' | 'aborted'
 */
export async function search(challenge, difficulty, options = {}) {
    if (!isValidPuzzle(challenge, difficulty)) {
        throw new Error('invalid');
    }

    const { signal, onProgress } = options;
    const cap = iterationCap(difficulty, options.maxNonce);
    let slice = Date.now();

    for (let nonce = 0; nonce <= cap; nonce++) {
        if (leadingZeroBits(await sha256(encode(challenge, nonce))) >= difficulty) {
            return nonce;
        }

        if (Date.now() - slice >= YIELD_EVERY_MS) {
            onProgress?.(nonce + 1);
            await yieldToEventLoop();

            if (signal?.aborted) {
                throw new Error('aborted');
            }

            slice = Date.now();
        }
    }

    throw new Error('exhausted');
}
