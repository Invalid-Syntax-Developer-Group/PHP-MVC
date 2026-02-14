<?php
declare(strict_types=1);
namespace PhpMVC\Validation\Rule;

/**
 * Class NonNegativeRule
 *
 * Validation rule that checks whether a field's value is a non-negative number.
 *
 * This rule is **non-blocking for empty values**: if the field is empty or not
 * present, validation passes. It is intended to be combined with
 * {@see RequiredRule} when the field must be present.
 */
final class NonNegativeRule implements Rule
{
    /**
     * Validate that the field's value is a non-negative number.
     *
     * If the field is empty or not set, validation succeeds.
     * Otherwise, validation checks if the value is a valid number and non-negative.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name currently under validation.
     * @param array  $params Optional rule parameters (not used for this rule).
     *
     * @return bool True if the value is empty or a non-negative number; false otherwise.
     */
    public function validate(array $data, string $field, array $params): bool
    {
        if (empty($data[$field])) return true;

        if (!is_numeric($data[$field])) return false;

        return $data[$field] >= 0;
    }

    /**
     * Get the validation error message for a failed rule.
     *
     * This method is only expected to be called when {@see Rule::validate()}
     * returns false.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name that failed validation.
     * @param array  $params Optional rule parameters used during validation.
     *
     * @return string Human-readable validation error message.
     */
    public function getMessage(array $data, string $field, array $params): string
    {
        return "The {$field} field must be a non-negative number.";
    }
}
