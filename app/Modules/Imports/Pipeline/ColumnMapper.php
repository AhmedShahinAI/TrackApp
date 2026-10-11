<?php

declare(strict_types=1);

namespace App\Modules\Imports\Pipeline;

/**
 * T225 — proposing which source column means which target (FR-056).
 *
 * ## The suggestion is a suggestion
 *
 * It is shown pre-filled and the operator confirms or corrects it; nothing is imported on its
 * strength alone. So it is tuned to be right on the files the college actually has and to leave a
 * target unmapped when unsure, rather than to be clever: a wrong guess the operator does not notice
 * is worse than a blank they must fill.
 *
 * ## Normalised, because Arabic headers are typed by hand
 *
 * "الرقم الجامعى", "الرقم  الجامعي" and "الرقم الجامعي" are one header to a person and three to
 * `===`. Hamza forms, alef maqsura, taa marbuta, tatweel, diacritics, case and spacing are folded
 * before comparing.
 *
 * ## Exact first, then contained, and each header once
 *
 * Every target is tried for an exact alias match before any target is tried for a partial one, so
 * "اسم المقرر" lands on course name before "المقرر" can be claimed by a looser rule. A header is
 * given to at most one target: two targets reading the same column is a mapping no operator meant.
 */
final class ColumnMapper
{
    /**
     * @param  array<string, list<string>>  $aliases  target => accepted header spellings
     * @param  list<string>  $headers
     * @return array<string, string|null> target => header, or null when nothing fits
     */
    public function suggest(array $aliases, array $headers): array
    {
        $normalisedHeaders = array_map(self::normalise(...), $headers);
        $suggestion = array_fill_keys(array_keys($aliases), null);
        $taken = [];

        foreach ([true, false] as $exact) {
            foreach ($aliases as $target => $spellings) {
                if ($suggestion[$target] !== null) {
                    continue;
                }

                foreach ($normalisedHeaders as $index => $header) {
                    if ($header === '' || isset($taken[$index])) {
                        continue;
                    }

                    if ($this->matches($header, array_map(self::normalise(...), $spellings), $exact)) {
                        $suggestion[$target] = $headers[$index];
                        $taken[$index] = true;

                        break;
                    }
                }
            }
        }

        return $suggestion;
    }

    /**
     * Target => column index, for a confirmed mapping against the file's actual header row.
     *
     * The first column carrying the header wins when a file repeats one. Matching is exact on the
     * trimmed header — the mapping was chosen from this same list, so normalising again could only
     * make two different columns collide.
     *
     * @param  array<string, string|null>  $mapping
     * @param  list<string>  $headers
     * @return array<string, int>
     */
    public function indexes(array $mapping, array $headers): array
    {
        $indexes = [];

        foreach ($mapping as $target => $header) {
            if ($header === null || $header === '') {
                continue;
            }

            $index = array_search(trim($header), array_map('trim', $headers), true);

            if ($index !== false) {
                $indexes[$target] = (int) $index;
            }
        }

        return $indexes;
    }

    public static function normalise(string $value): string
    {
        $value = mb_strtolower(trim($value));

        // Diacritics (U+064B–U+0652), superscript alef, and tatweel carry no meaning in a header.
        $value = (string) preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $value);

        $value = strtr($value, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي',
        ]);

        // Underscores, hyphens, dots and runs of space are all "a gap between words".
        $value = (string) preg_replace('/[\s_\-.\/]+/u', ' ', $value);

        return trim($value);
    }

    /**
     * @param  list<string>  $spellings
     */
    private function matches(string $header, array $spellings, bool $exact): bool
    {
        foreach ($spellings as $spelling) {
            if ($spelling === '') {
                continue;
            }

            if ($exact ? $header === $spelling : str_contains($header, $spelling)) {
                return true;
            }
        }

        return false;
    }
}
