<?php
declare(strict_types=1);
namespace PhpMVC\Validation\Rule;

/**
 * Class EmailRule
 *
 * Validation rule that checks whether a field contains a valid email-like value.
 *
 * This rule is **non-blocking for empty values**: if the field is empty or not
 * present, validation passes. It is intended to be combined with
 * {@see RequiredRule} when the field must be present.
 *
 * Note:
 * This implementation performs a **lightweight validation** by checking for the
 * presence of an '@' character. It does not perform full RFC-compliant email
 * validation.
 */
final class EmailRule implements Rule
{
    /**
     * Validate that the field contains an email-like value.
     *
     * If the field is empty or not set, validation succeeds.
     * Otherwise, validation passes if the value contains an '@' character.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name currently under validation.
     * @param array  $params Rule parameters (unused).
     *
     * @return bool True if the value is empty or appears to be an email; false otherwise.
     */
    public function validate(array $data, string $field, array $params): bool
    {
        if (empty($data[$field])) {
            return true;
        }

        return str_contains($data[$field], '@');
    }

    /**
     * Get the validation error message.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name that failed validation.
     * @param array  $params Rule parameters (unused).
     *
     * @return string Error message describing the email requirement.
     */
    public function getMessage(array $data, string $field, array $params): string
    {
        return "{$field} should be an email";
    }
}
