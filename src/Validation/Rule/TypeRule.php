<?php
declare(strict_types=1);
namespace PhpMVC\Validation\Rule;

/**
 * Class TypeRule
 *
 * Validation rule that checks whether a field's value is of a specified type.
 *
 * Supported types include:
 *  - "string": The value must be a string.
 *  - "integer": The value must be an integer.
 *  - "number": The value must be numeric (integer or float).
 *  - "float": The value must be a float.
 *  - "boolean": The value must be a boolean.
 *  - "array": The value must be an array.
 *  - "object": The value must be an object.
 *
 * This rule is **non-blocking for empty values**: if the field is empty or not
 * present, validation passes. It is intended to be combined with
 * {@see RequiredRule} when the field must be present.
 */
final class TypeRule implements Rule
{
    /**
     * Validate that the field's value is of the specified type.
     *
     * If the field is empty or not set, validation succeeds.
     * Otherwise, validation checks if the value matches the expected type.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name currently under validation.
     * @param array  $params Rule parameters where $params[0] is the expected type.
     *
     * @return bool True if the value is empty or matches the expected type; false otherwise.
     */
    public function validate(array $data, string $field, array $params): bool
    {
        $value = $data[$field] ?? null;
        if ($value === null || $value === '') return true; // Consider empty values as valid

        $expectedType = $params[0] ?? null;
        if (!is_string($expectedType)) return false; // Expected type must be a string
        $expectedType = trim($expectedType);

        switch ($expectedType) {
            case 'string':
                return is_string($value);
            case 'integer':
                return is_int($value);
            case 'number':
                return is_numeric($value);
            case 'float':
                return is_float($value);
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_object($value);
            default:
                return false; // Unsupported type specified
        }
    }

    /**
     * Get the validation error message.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name that failed validation.
     * @param array  $params Rule parameters where $params[0] is the expected type.
     *
     * @return string Error message describing the type requirement.
     */
    public function getMessage(array $data, string $field, array $params): string
    {
        $expectedType = 'unknown';

        if (isset($params[0]) && is_string($params[0])) {
            $trimmed = trim($params[0]);
            if (!empty($trimmed)) $expectedType = $trimmed;
        }

        return "{$field} should be of type {$expectedType}";
    }
}
