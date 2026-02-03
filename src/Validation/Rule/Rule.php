<?php
declare(strict_types=1);
namespace PhpMVC\Validation\Rule;

/**
 * Interface Rule
 *
 * Contract for a single validation rule within the validation system.
 *
 * Implementations encapsulate:
 *  - The logic required to determine whether a given field/value
 *    satisfies a specific validation constraint.
 *  - The generation of a human-readable error message when validation fails.
 *
 * Rules are typically registered with the validation manager under a short
 * alias (e.g. "required", "email", "min") and invoked dynamically during
 * validation.
 */
interface Rule
{
    /**
     * Determine whether the given field passes validation.
     *
     * @param array  $data   The full input data set being validated.
     * @param string $field  The field name currently under validation.
     * @param array  $params Optional rule parameters (e.g. min length).
     *
     * @return bool True if the field passes validation; false otherwise.
     */
    public function validate(array $data, string $field, array $params): bool;

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
    public function getMessage(array $data, string $field, array $params): string;
}
