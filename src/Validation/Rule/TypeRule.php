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
        if (empty($data[$field])) return true;

        $expectedType = $params[0] ?? null;

        if ($expectedType === null) return false; // No type specified, cannot validate

        switch ($expectedType) {
            case 'string':
                return is_string($data[$field]);
            case 'integer':
                return is_int($data[$field]);
            case 'float':
                return is_float($data[$field]);
            case 'boolean':
                return is_bool($data[$field]);
            case 'array':
                return is_array($data[$field]);
            case 'object':
                return is_object($data[$field]);
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
        $expectedType = $params[0] ?? 'unknown';
        return "{$field} should be of type {$expectedType}";
    }
}
