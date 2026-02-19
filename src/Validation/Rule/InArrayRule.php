<?php
declare(strict_types=1);
namespace PhpMVC\Validation\Rule;

/**
 * Class InArrayRule
 *
 * Validation rule that checks whether a field's value is one of a predefined set of allowed values.
 *
 * This rule is **non-blocking for empty values**: if the field is empty or not
 * present, validation passes. It is intended to be combined with
 * {@see RequiredRule} when the field must be present.
 */
final class InArrayRule implements Rule
{
    /**
     * Validate that the field's value is one of the allowed values.
     *
     * If the field is empty or not set, validation succeeds.
     * Otherwise, validation checks if the value is in the allowed values array.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name currently under validation.
     * @param array  $params Rule parameters where $params[0] is an array of allowed values.
     *
     * @return bool True if the value is empty or in the allowed values; false otherwise.
     */
    public function validate(array $data, string $field, array $params): bool
    {
        if (empty($data[$field])) return true;

        $allowedValues = $this->allowedValues($params);

        if (empty($allowedValues)) return false;

        return in_array($data[$field], $allowedValues, true);
    }

    /**
     * Get the validation error message for a failed rule.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name that failed validation.
     * @param array  $params Rule parameters used during validation.
     *
     * @return string Human-readable validation error message.
     */
    public function getMessage(array $data, string $field, array $params): string
    {
        $allowedValues = $this->allowedValues($params);

        return sprintf(
            'The %s field must be one of the following values: %s.',
            $field,
            implode(', ', $allowedValues)
        );
    }

    private function allowedValues(array $params): array
    {
        if (isset($params[0]) && is_array($params[0])) {
            return $params[0];
        }

        return array_values(array_filter(
            $params,
            static fn($value) => !is_array($value)
        ));
    }
}
