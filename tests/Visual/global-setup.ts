/**
 * Prepare the visual suite's database before anything runs.
 *
 * The suite used to depend on `database/visual.sqlite` already existing and already being seeded,
 * which made it fragile in a way that cost two debugging sessions: the file looks like a build
 * artifact, so it gets cleaned up, and a suite whose database vanishes mid-run reports sixty
 * failures in components that have nothing to do with the change under test.
 *
 * Creating and seeding it here makes the suite self-sufficient. It is also the honest place for it:
 * the fixtures these baselines render are part of the suite's definition, not of the developer's
 * local state.
 *
 * Hashing is deliberately cheap — see the webServer env in playwright.config.ts for why.
 */
import { execFileSync } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { dirname } from 'node:path';

const DATABASE = 'database/visual.sqlite';

const ENV = {
    ...process.env,
    APP_ENV: 'local',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: DATABASE,
    CACHE_STORE: 'file',
    SESSION_DRIVER: 'database',
    QUEUE_CONNECTION: 'sync',
    HASH_DRIVER: 'bcrypt',
    BCRYPT_ROUNDS: '4',
};

function artisan(...args: string[]): void {
    execFileSync('php', ['artisan', ...args, '--force'], {
        env: ENV,
        stdio: 'pipe',
        encoding: 'utf8',
    });
}

export default function globalSetup(): void {
    // Rebuilt from scratch every run. A database carried over between runs is a database whose
    // contents nobody can state, and a baseline is only meaningful against known fixtures.
    rmSync(DATABASE, { force: true });
    mkdirSync(dirname(DATABASE), { recursive: true });
    writeFileSync(DATABASE, '');

    artisan('migrate');
    artisan('db:seed');

    // T051 — the dataset every baseline renders against.
    artisan('db:seed', '--class=UiFixtureSeeder');

    console.log(`  visual database prepared: ${DATABASE}`);
}
