/**
 * US7 — the in-scope screens (design-system.md §9) and how to reach each one, shared by the
 * screen-wide sweeps: screens-all, axe-all and rtl-mirroring.
 *
 * Mirrors tests/Support/Screens.php, which drives the same list through the PHP suite. The fixture
 * ids are UiFixtureSeeder's: section 13, student 100, import jobs 11 (mapping), 13 (checking, queue
 * waiting), 14 (preview), 16 (importing) and 1 (report).
 */
export type ScreenAccount = 'guest' | 'admin' | 'instructor';

export type Screen = {
    key: string;
    path: string;
    as: ScreenAccount;
};

export const SCREENS: Screen[] = [
    { key: 'login', path: '/login', as: 'guest' },
    { key: 'home', path: '/home', as: 'admin' },
    { key: 'profile', path: '/profile', as: 'admin' },
    { key: 'preferences', path: '/profile/preferences', as: 'admin' },
    { key: 'password', path: '/profile/password', as: 'admin' },
    { key: 'users', path: '/users', as: 'admin' },
    { key: 'audit', path: '/audit', as: 'admin' },
    { key: 'terms', path: '/terms', as: 'admin' },
    { key: 'branches', path: '/branches', as: 'admin' },
    { key: 'programs', path: '/programs', as: 'admin' },
    { key: 'courses', path: '/courses', as: 'admin' },
    { key: 'sections', path: '/sections', as: 'admin' },
    { key: 'sections-mine', path: '/sections', as: 'instructor' },
    { key: 'section', path: '/sections/13', as: 'admin' },
    { key: 'section-import', path: '/sections/13/import', as: 'admin' },
    { key: 'students', path: '/students', as: 'admin' },
    { key: 'student', path: '/students/100', as: 'admin' },
    { key: 'imports', path: '/imports', as: 'admin' },
    { key: 'imports-new', path: '/imports/new', as: 'admin' },
    { key: 'import-mapping', path: '/imports/11', as: 'admin' },
    { key: 'registrar-upload', path: '/imports/new/registrar', as: 'admin' },
    { key: 'registrar-confirm', path: '/imports/17', as: 'admin' },
    { key: 'courses-upload', path: '/imports/new/courses', as: 'admin' },
    { key: 'users-upload', path: '/imports/new/users', as: 'admin' },
    { key: 'import-checking', path: '/imports/13', as: 'admin' },
    { key: 'import-preview', path: '/imports/14', as: 'admin' },
    { key: 'import-importing', path: '/imports/16', as: 'admin' },
    { key: 'import-report', path: '/imports/1', as: 'admin' },
    { key: 'refusal-404', path: '/sections/999999', as: 'admin' },
];
