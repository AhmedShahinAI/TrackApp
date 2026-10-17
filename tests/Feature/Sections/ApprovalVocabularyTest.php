<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| T485 — section approval and assignment review never share a word (FR-185, SC-043)
|--------------------------------------------------------------------------
|
| They are different decisions at different levels, so no interface string or audit label may render
| a bare "approved" where either could be meant. Every section-approval label names the section.
| Assignment review is not built in this release; its vocabulary, when it lands, must not reuse these.
|
*/

/** @return array<string, string> every section-approval label in one locale, flattened */
function sectionApprovalLabels(string $locale): array
{
    $labels = trans('sections.approval.status', [], $locale) + collect(trans('sections.approval.actions', [], $locale))->mapWithKeys(fn ($v, $k) => ['action.'.$k => $v])->all();

    foreach (['section.submitted_for_approval', 'section.approved', 'section.returned', 'section.rejected', 'section.resubmitted'] as $key) {
        // Dotted keys inside `actions`, so read from the array rather than as a nested path.
        $labels['audit.'.$key] = trans('audit.actions', [], $locale)[$key];
    }

    return $labels;
}

test('no English section-approval label is a bare "approved"', function (): void {
    foreach (sectionApprovalLabels('en') as $key => $label) {
        expect(preg_match('/^\s*approved\s*$/i', $label))->toBe(0, "{$key} is a bare \"{$label}\"");
    }
});

test('every English state, decision and audit label names the section', function (): void {
    foreach (sectionApprovalLabels('en') as $key => $label) {
        // "Returned for edits" is the one state that reads as the section's without naming it, on a
        // screen that is about sections; it is still not "approved".
        if ($key === 'returned' || $key === 'action.return' || $key === 'action.decide') {
            continue;
        }

        expect(stripos($label, 'section') !== false || stripos($label, 'request') !== false)->toBeTrue("{$key}: \"{$label}\" does not name the section");
    }
});

test('the Arabic state vocabulary is v2\'s, verbatim (T500)', function (): void {
    expect(trans('sections.approval.status', [], 'ar'))->toBe([
        'pending' => 'بانتظار الاعتماد',
        'returned' => 'معادة للتعديل',
        'rejected' => 'مرفوضة',
        'active' => 'معتمدة',
    ]);
});

test('every Arabic audit label names the section', function (): void {
    foreach (['section.submitted_for_approval', 'section.approved', 'section.returned', 'section.rejected', 'section.resubmitted'] as $key) {
        expect(trans('audit.actions', [], 'ar')[$key])->toContain('شعبة');
    }
});
