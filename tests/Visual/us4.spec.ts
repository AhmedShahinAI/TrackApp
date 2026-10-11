/**
 * T182 — the academic-structure screens, against the TermsScreen / BranchesScreen / CoursesScreen /
 * SectionsScreen / SectionDetail / StudentsScreen references.
 *
 * ## What is worth pixels here, and what is not
 *
 * Six list and detail screens, plus the three statements that only exist as rendered text and
 * would therefore disappear without a single test failing:
 *
 *   - the branches notice that deactivation is the only removal (FR-049),
 *   - the reassignment confirmation that access moves while authorship does not (FR-048),
 *   - the withdraw dialog's mandatory reason (FR-047, FR-082).
 *
 * Each of those gets its own shot rather than relying on the full-page diff, for the reason the
 * audit immutability notice got one in US3: a sentence removed from a busy page is invisible in a
 * whole-page comparison, and these three are the places where the platform's rules are the only
 * thing standing between a user and a wrong assumption.
 *
 * ## Why the instructor variant is shot separately
 *
 * The sections list is the screen US2's scope is visible on, and the instructor's version is not a
 * styling variant — it has no branch filter and a different empty state. A single admin screenshot
 * would pass while the instructor's view regressed to the generic "nothing in your scope", which
 * tells an instructor nothing about what to do next.
 *
 * ## Determinism
 *
 * Unlike the audit screen, these lists are a function of the fixture only: nothing the suite does
 * writes a term, a branch, a course or an enrollment. So no filtering is needed and no rows are
 * masked. What IS masked is the student-detail enrollment date, which the fixture seeds relative
 * to `now()` — a fixed-looking column that in fact changes every day the suite runs.
 */
import { expect, test, type Page } from '@playwright/test';
import { refuseErrorPages } from './helpers/render-reference';
import { accountFor, adminFor, expectLocale, signIn } from './support/session';

const INTERACTION_WIDTH = 1440;

function skipUnlessInteractionWidth(testInfo: { project: { use: { viewport?: { width: number } | null } } }): void {
    test.skip(
        testInfo.project.use.viewport?.width !== INTERACTION_WIDTH,
        'Interaction-driven; runs at 1440 only.',
    );
}

async function stabilise(page: Page) {
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => document.fonts.ready);
    await page.waitForTimeout(150);

    // Never photograph an exception page. Planted and proven in US3: --update-snapshots will
    // otherwise freeze a styled 500 as the baseline and every later run agrees with it.
    await refuseErrorPages(page);
}

/** Dates seeded relative to now(), which look fixed and are not. */
function volatileDates(page: Page) {
    return [page.locator('td[data-label*="تاريخ"], td[data-label*="From"], td[data-label*="To"]')];
}

for (const locale of ['ar', 'en'] as const) {
    test.describe(`US4 · ${locale}`, () => {
        test('the terms list', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/terms');
            await stabilise(page);
            await expectLocale(page, locale);

            await expect(page).toHaveScreenshot(`${locale}-terms.png`, {
                fullPage: true,
                mask: volatileDates(page),
            });
        });

        test('the branches list', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/branches');
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-branches.png`, { fullPage: true });
        });

        test('the branches screen states that deactivation is the only removal', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/branches');
            await stabilise(page);

            /*
             * Its own shot, for the same reason the audit immutability notice has one. FR-049 means
             * someone will come here looking for a delete button; this sentence is the only thing
             * that tells them why there isn't one, and it must be said before they go looking.
             */
            await expect(page.locator('.track-alert').first())
                .toHaveScreenshot(`${locale}-branches-notice.png`);
        });

        test('the courses list', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/courses');
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-courses.png`, { fullPage: true });
        });

        test('the sections list, admin', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/sections');
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-sections-admin.png`, { fullPage: true });
        });

        test('the sections list, instructor — a branch filter only when there is a choice', async ({ page }) => {
            await signIn(page, locale, accountFor('instructor', locale));
            await page.goto('/sections');
            await stabilise(page);

            /*
             * The rule is "offer the filter only when it can change something", not "hide it from
             * instructors" — and the difference matters, because the fixture instructor holds a
             * cross-branch access grant (FR-026) and therefore genuinely sees two branches.
             *
             * Asserting the role instead of the rule is what the first version of this test did,
             * and it failed against a screen that was behaving correctly. So: the select is either
             * absent, or it offers at least two real branches beside the "all" placeholder. One
             * option is the state that must never render.
             */
            const branchFilter = page.locator('.track-filters select[name="branch_id"]');

            if (await branchFilter.count() > 0) {
                const choices = await branchFilter.locator('option:not([value=""])').count();
                expect(choices).toBeGreaterThan(1);
            }

            await expect(page).toHaveScreenshot(`${locale}-sections-instructor.png`, { fullPage: true });
        });

        test('the students list', async ({ page }) => {
            await signIn(page, locale, adminFor(locale));
            await page.goto('/students');
            await stabilise(page);

            await expect(page).toHaveScreenshot(`${locale}-students.png`, { fullPage: true });
        });
    });
}

test.describe('US4 · interactions', () => {
    test.beforeEach(({}, testInfo) => skipUnlessInteractionWidth(testInfo));

    test('the section detail page, each tab', async ({ page }) => {
        await signIn(page, 'ar', adminFor('ar'));
        await page.goto('/sections');
        await stabilise(page);

        // Through the list, not by a hard-coded id: the row link is itself the thing under test
        // (the literal "/sections/{id}" placeholder shipped once and 404'd every row).
        await page.locator('tr.track-table__row--clickable').first().click();
        await stabilise(page);

        await expect(page).toHaveScreenshot('ar-section-roster.png', {
            fullPage: true,
            mask: volatileDates(page),
        });

        await page.getByRole('tab', { name: /سجل المدرّسين/ }).click();
        await stabilise(page);

        /*
         * The history tab, which is the only screen that can answer "who moved this section". Its
         * `changed_by` column is a different person from `instructor` — the one who performed the
         * handover rather than the one who received it — and a table that showed only the latter
         * would look complete and answer the wrong question.
         */
        await expect(page.locator('#section-tabs-panel-history')).toHaveScreenshot(
            'ar-section-history.png',
            { mask: volatileDates(page) },
        );
    });

    test('reassignment says that access moves but authorship does not', async ({ page }) => {
        await signIn(page, 'ar', adminFor('ar'));
        await page.goto('/sections');
        await stabilise(page);
        await page.locator('tr.track-table__row--clickable').first().click();
        await stabilise(page);

        await page.getByRole('button', { name: /تغيير المدرّس/ }).click();
        await page.waitForTimeout(200);

        /*
         * The sentence is the whole reason this is a dialog rather than a select. Without it, the
         * obvious reading of "change instructor" is that the section and everything in it now
         * belongs to someone else — a belief that leads either to avoiding a legitimate
         * reassignment or to expecting a history rewrite that will never happen.
         */
        await expect(page.locator('#assign-dialog .track-dialog')).toHaveScreenshot('ar-assign-dialog.png');
    });

    test('withdrawing requires a reason before the request is made', async ({ page }) => {
        await signIn(page, 'ar', adminFor('ar'));
        await page.goto('/sections');
        await stabilise(page);
        await page.locator('tr.track-table__row--clickable').first().click();
        await stabilise(page);

        await page.getByRole('button', { name: /سحب من الشعبة/ }).first().click();
        await page.waitForTimeout(200);

        /*
         * `:visible`, not `[open]`. x-track-dialog is a div with Alpine's x-show, not a native
         * <dialog>, so it never carries an `open` attribute — the first version of this locator
         * matched nothing and timed out, which reads exactly like a dialog that failed to open.
         *
         * One withdraw dialog is rendered per active enrollment, so the visible one is the subject;
         * the rest are in the DOM with display:none.
         */
        const dialog = page.locator('.track-dialog:visible').first();
        await expect(dialog).toHaveScreenshot('ar-withdraw-dialog.png');

        /*
         * Asserted, not merely photographed. FR-047 makes the reason mandatory, and it is enforced
         * in three places — here, the controller, and WithdrawEnrollment's 409. This checks the
         * first one actually blocks submission: the layer where it is optional is the layer through
         * which every unexplained withdrawal arrives.
         */
        const textarea = dialog.locator('textarea[name="reason"]');
        await expect(textarea).toHaveAttribute('required', '');

        const blocked = await textarea.evaluate(
            (element) => !(element as HTMLTextAreaElement).checkValidity(),
        );
        expect(blocked).toBe(true);
    });

    test('a withdrawn enrollment keeps its row and its reason', async ({ page }) => {
        await signIn(page, 'ar', adminFor('ar'));
        await page.goto('/students');
        await stabilise(page);

        await page.locator('tr.track-table__row--clickable').first().click();
        await stabilise(page);

        /*
         * Constitution V at the surface. If a withdrawal removed the row, this page would answer
         * "who is enrolled" and lose "who left, and why" — and the reason WithdrawEnrollment
         * insists on would be written and never read by anyone.
         *
         * Count-tolerant: the fixture may or may not seed a withdrawal in this branch, so the
         * assertion is that a withdrawn row, if present, carries its reason — not that one exists.
         */
        const withdrawn = page.locator('tr.track-table__row--muted');

        if (await withdrawn.count() > 0) {
            await expect(withdrawn.first().locator('.track-table__note')).not.toBeEmpty();
        }

        await expect(page).toHaveScreenshot('ar-student-detail.png', {
            fullPage: true,
            mask: volatileDates(page),
        });
    });
});
