<?php
declare(strict_types=1);
namespace PhpMVC\Validation\Rule;

/**
 * Class RequiredRule
 *
 * Validation rule that ensures a given field is present and not empty.
 *
 * This rule fails if the field is missing from the input data or if its
 * value evaluates as empty according to PHP's `empty()` semantics.
 *
 * Common use cases include enforcing mandatory form inputs such as
 * usernames, passwords, or email addresses.
 */
final class RequiredRule implements Rule
{
    /**
     * Validate that the field exists and is not empty.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name currently under validation.
     * @param array  $params Unused for this rule.
     *
     * @return bool True if the field is present and not empty; false otherwise.
     */
    public function validate(array $data, string $field, array $params): bool
    {
        return !empty($data[$field]);
    }

    /**
     * Get the validation error message.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name that failed validation.
     * @param array  $params Unused for this rule.
     *
     * @return string Error message indicating the field is required.
     */
    public function getMessage(array $data, string $field, array $params): string
    {
        return "{$field} is required";
    }
}
