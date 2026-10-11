<?php

declare(strict_types=1);

namespace App\Modules\Shared\Rules;

use App\Modules\Settings\Settings;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * T304 - validate a value against a named settings list (FR-108, FR-109).
 *
 * This is what lets `degree` and `activity` be plain string columns instead of database enums. The
 * permitted values live in the `section_degrees` and `section_activities` settings, so adding a
 * degree is a settings row - no migration, no deployment, no code change (constitution IV).
 *
 * Used by the section form, the section import, and the registrar import, which is why it is a rule
 * rather than three copies of an `in:` list that could drift apart.
 */
final class InSetting implements ValidationRule
{
    public function __construct(private readonly string $settingKey) {}

    /**
     * The interface types $fail as returning a PotentiallyTranslatedString, which is what makes the
     * ->translate() call below valid - declaring it as returning void here would be both wrong and
     * the reason the chain looked like a bug.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! in_array($value, Settings::values($this->settingKey), true)) {
            // No `attribute` replacement of our own: passing the raw name ("degree") overrode the
            // translated one, so an Arabic message read «في degree». Laravel fills :attribute from
            // the lang file's attribute map, as it does for every built-in rule.
            $fail('validation.in_setting')->translate();
        }
    }
}
