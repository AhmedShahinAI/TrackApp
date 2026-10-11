# Acknowledgement — accessibility corrections to the approved palette (ui-conflicts C-17, task T283)

**For:** the author of the TRACK design system
**Prepared by:** the TRACK engineering team, 2026-10-09
**Status:** ready for the walkthrough — not yet acknowledged (the walkthrough and the signature are a
person's actions)

## What this is

Accessibility to WCAG 2.1 AA is a hard requirement for TRACK: the platform's constitution and its
acceptance criteria both make it a pass-or-fail gate, the same as security. Some colour pairings in the
approved palette fall below the minimum contrast that standard sets, so the platform darkens those
colours slightly. **Nothing else changes**: every correction keeps the same hue, and no spacing,
radius, font or layout value moves. The brand green `#10B981` stays exactly as designed wherever it is
a fill rather than text (progress bars, switches, the active sidebar marker, badge backgrounds).

The task text says seven corrections; there are **nine**. Seven were found by measuring the palette
before building. Two more (A11Y-08, A11Y-09) only appear when a colour sits on a particular background,
and were found by testing the built screens.

## The corrections

Contrast is the ratio between text and its background; AA asks for 4.5:1 for normal text and 3:1 for
large text and for focus outlines.

| # | Where you see it | Was | Becomes | Contrast before → after (needed) |
|---|---|---|---|---|
| A11Y-01 | Large green KPI numbers on white | `#10B981` | `#059669` | 2.54 → 3.77 (3.0) |
| A11Y-02 | Green text at normal size on white | `#10B981` | `#047857` | 2.54 → 5.48 (4.5) |
| A11Y-03 | The focus outline around a selected control | `#10B981` | `#059669` | 2.54 → 3.77 (3.0) |
| A11Y-04 | Warning (amber) text on white | `#D97706` | `#B45309` | 3.19 → 5.02 (4.5) |
| A11Y-05 | Red label on a light-red badge | `#DC2626` | `#B91C1C` | 3.95 → 5.30 (4.5) |
| A11Y-06 | Amber label on a light-amber badge | `#D97706` | `#92400E` | 2.86 → 6.37 (4.5) |
| A11Y-07 | Light grey ("muted") text on white | `#9CA3AF` | `#6B7280` | 2.54 → 4.83 (4.5) |
| A11Y-08 | White label or icon on a green button or step marker | green `#10B981` | green `#047857` | 2.54 → 5.48 (4.5) |
| A11Y-09 | Grey secondary text on the light-grey panel `#F1F5F9` | `#6B7280` | `#4B5563` | 4.41 → 6.90 (4.5) |

A11Y-09's value is also used, for the same reason, by two controls added later: the language switch
(عربي / EN) and the hint text in the file-upload area.

Full detail, with the exact tokens and the measurement method:
`specs/001-foundation-access-roster/ACCESSIBILITY-DELTA.md`.

## How to see it

Open the component gallery (`/dev/components`, local environment) next to the original kit
(`design-system/ui_kits/web-app/index.html`). Only the colours above should differ.

## Acknowledgement

I have reviewed the nine corrections above and accept them as the platform's colour values.

- [ ] Accepted as listed
- [ ] Accepted with these changes (each must still meet the "needed" ratio): ______________________

Name ______________________ · Role ______________________ · Date ____________

Once signed, record the date in `specs/001-foundation-access-roster/ui-conflicts.md` C-17 and mark T283
done in tasks.md.
