/**
 * T013 — copy the vendored brand assets into the public web root at build time.
 *
 * The design system is read-only reference (design-system/VENDORED.md), so assets are copied rather
 * than symlinked or served from there: a symlink into a vendored folder would make the public root
 * depend on a directory the project promises never to touch, and would break on a re-vendoring.
 *
 * Runs before `vite build`. Idempotent — safe to run repeatedly.
 */
import { cp, mkdir, access } from 'node:fs/promises';
import { constants } from 'node:fs';

const SOURCE = 'design-system/assets';
const TARGET = 'public/img/brand';

try {
    await access(SOURCE, constants.R_OK);
} catch {
    console.error(
        `\n  Cannot read ${SOURCE}.\n` +
        `  The design system must be vendored first (task T004):\n` +
        `    unzip "TRACK Design System.zip" -d design-system\n`
    );
    process.exit(1);
}

await mkdir(TARGET, { recursive: true });
await cp(SOURCE, TARGET, { recursive: true });
console.log(`  brand assets: ${SOURCE} -> ${TARGET}`);
