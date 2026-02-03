<?php
declare(strict_types=1);
namespace PhpMVC\Validation\Rule;

use InvalidArgumentException;

/**
 * Class MinRule
 *
 * Validation rule that enforces a minimum string length for a given field.
 *
 * This rule is **non-blocking for empty values**: if the field is empty or not
 * present, validation passes. It is intended to be combined with
 * {@see RequiredRule} when the field must be both present and meet a minimum
 * length.
 *
 * The minimum length must be provided as the first rule parameter
 * (e.g. `min:8`). If no parameter is supplied, an exception is thrown.
 */
final class MinRule implements Rule
{
    /**
     * Validate that the field value meets the minimum length requirement.
     *
     * If the field is empty or not set, validation succeeds.
     * If no minimum length parameter is provided, an exception is thrown.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name currently under validation.
     * @param array  $params Rule parameters; index 0 must contain the minimum length.
     *
     * @return bool True if the value meets the minimum length or is empty; false otherwise.
     *
     * @throws InvalidArgumentException If no minimum length is specified.
     */
    public function validate(array $data, string $field, array $params): bool
    {
        if (empty($data[$field])) {
            return true;
        }

        if (empty($params[0])) {
            throw new InvalidArgumentException('specify a min length');
        }

        $length = (int) $params[0];

        return strlen($data[$field]) >= $length;
    }

    /**
     * Get the validation error message.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name that failed validation.
     * @param array  $params Rule parameters; index 0 contains the minimum length.
     *
     * @return string Error message describing the minimum length requirement.
     */
    public function getMessage(array $data, string $field, array $params): string
    {
        $length = (int) $params[0];

        return "{$field} should be at least {$length} characters";
    }
}
