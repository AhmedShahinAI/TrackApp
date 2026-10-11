/**
 * T358 (review) — the proof-of-work puzzle, run with Node's built-in runner: `node --test tests/js`.
 *
 * No test framework to install: node:test and node:crypto are the whole toolkit. The independent
 * reference for every hash is Node's own SHA-256, never the code under test.
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createHash, randomBytes } from 'node:crypto';
import {
    CHALLENGE_PATTERN, DIFFICULTY_MAX, DIFFICULTY_MIN,
    encode, isValidPuzzle, iterationCap, leadingZeroBits, search, sha256, sha256Js,
} from '../../resources/js/pow/puzzle.js';

const reference = (bytes) => new Uint8Array(createHash('sha256').update(bytes).digest());
const challenge = 'c0ffee-test-challenge_01';

test('the pure-JS SHA-256 agrees with Node on every length around the padding boundaries', () => {
    for (let length = 0; length <= 200; length++) {
        const bytes = new Uint8Array(randomBytes(length));
        assert.deepEqual(sha256Js(bytes), reference(bytes), `length ${length}`);
    }
});

test('leading zero bits are counted across byte boundaries, not by hex character', () => {
    assert.equal(leadingZeroBits(Uint8Array.of(0x80)), 0);
    assert.equal(leadingZeroBits(Uint8Array.of(0x01)), 7);
    assert.equal(leadingZeroBits(Uint8Array.of(0x00, 0x10)), 11);
    assert.equal(leadingZeroBits(Uint8Array.of(0x00, 0x00, 0xff)), 16);
    assert.equal(leadingZeroBits(new Uint8Array(32)), 256);
});

test('the bytes hashed are the UTF-8 of challenge ":" canonical-decimal nonce', () => {
    assert.equal(new TextDecoder().decode(encode(challenge, 0)), `${challenge}:0`);
    assert.equal(new TextDecoder().decode(encode(challenge, 1234567)), `${challenge}:1234567`);
    assert.equal(new TextDecoder().decode(encode(challenge, Number.MAX_SAFE_INTEGER)), `${challenge}:9007199254740991`);
});

test('only a puzzle the server could have issued is attempted', () => {
    assert.ok(isValidPuzzle(challenge, DIFFICULTY_MIN));
    assert.ok(isValidPuzzle(challenge, DIFFICULTY_MAX));

    for (const [c, d] of [[challenge, 0], [challenge, DIFFICULTY_MAX + 1], [challenge, 4.5], ['short', 8], ['has space in it!!!!', 8], ['x'.repeat(257), 8], [42, 8]]) {
        assert.equal(isValidPuzzle(c, d), false, `${String(c).slice(0, 20)} / ${d}`);
    }

    assert.ok(CHALLENGE_PATTERN.test('AbCd-_0123456789'));
});

test('the iteration cap is one rule: a positive safe integer is honoured, anything else is the default', () => {
    assert.equal(iterationCap(10, 50), 50);

    for (const bad of [1.5, Infinity, -3, 0, NaN, undefined, 2 ** 60]) {
        assert.equal(iterationCap(10, bad), 2 ** 18, String(bad));
    }
});

test('a found nonce satisfies the rule, checked independently', async () => {
    for (const difficulty of [1, 8, 12, 16]) {
        const nonce = await search(challenge, difficulty);
        assert.ok(leadingZeroBits(reference(encode(challenge, nonce))) >= difficulty, `difficulty ${difficulty}`);
    }
});

test('both hash paths reach the same nonce — the platform digest and the JavaScript one', async () => {
    const viaSubtle = await search(challenge, 14);

    // The same search with the JavaScript hash forced, as a context without crypto.subtle runs it.
    let nonce = 0;
    while (leadingZeroBits(sha256Js(encode(challenge, nonce))) < 14) nonce++;

    assert.equal(viaSubtle, nonce);
    assert.deepEqual(await sha256(encode(challenge, nonce)), sha256Js(encode(challenge, nonce)));
});

test('the shared vector the PHP verifier holds (tests/Unit/Support/ProofOfWorkTest.php)', async () => {
    // Changing the encoding, the separator or the bit order breaks this file or that one.
    const shared = 'TRACK-shared_vector-0001';

    assert.equal(await search(shared, 12), 6232);
    assert.equal(leadingZeroBits(reference(encode(shared, 6232))), 16);
    assert.equal(await search(shared, 8), 84);
});

test('an invalid puzzle and an exhausted cap are refused, never answered with a wrong nonce', async () => {
    await assert.rejects(search('short', 8), /invalid/);
    await assert.rejects(search(challenge, 32, { maxNonce: 20 }), /exhausted/);
});

test('cancellation stops a search that is running', async () => {
    const controller = new AbortController();
    setTimeout(() => controller.abort(), 30);

    await assert.rejects(search(challenge, 32, { signal: controller.signal }), /aborted/);
});
