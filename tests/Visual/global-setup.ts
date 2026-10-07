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
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';

/*
 * Outside the repository, deliberately.
 *
 * The project lives in a OneDrive-synced folder, and the suite's SQLite file was in it. Two things
 * followed. The sync service holds handles on files it is uploading, and US3 turned every refused
 * request into a write — `AuditDeniedAccess` records `access.denied` — so four parallel workers now
 * write to one SQLite file that a background process is also touching. The result was
 * "SQLSTATE[HY000]: General error: 8 attempt to write a readonly database" rendered as a Laravel
 * error page, captured by --update-snapshots, and committed as the reference for a 403 screen.
 *
 * The system temp directory is local, unsynced, and nobody's backup.
 */
export const VISUAL_DATABASE = join(tmpdir(), 'track-visual.sqlite');

const DATABASE = VISUAL_DATABASE;

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
    for (const suffix of ['', '-wal', '-shm']) {
        rmSync(DATABASE + suffix, { force: true });
    }
    mkdirSync(dirname(DATABASE), { recursive: true });
    writeFileSync(DATABASE, '');

    /*
     * WAL before anything else. The default journal mode takes a database-wide write lock, and with
     * four workers each writing an audit row per refused request, that is a lock contention problem
     * rather than a theoretical one. WAL lets readers continue while one writer works.
     */
    execFileSync('php', ['-r', `$db = new PDO('sqlite:' . ${JSON.stringify(DATABASE)}); $db->exec('PRAGMA journal_mode=WAL');`], {
        env: ENV,
        stdio: 'pipe',
        encoding: 'utf8',
    });

    artisan('migrate');
    artisan('db:seed');

    // T051 — the dataset every baseline renders against.
    artisan('db:seed', '--class=UiFixtureSeeder');

    console.log(`  visual database prepared: ${DATABASE}`);
}
