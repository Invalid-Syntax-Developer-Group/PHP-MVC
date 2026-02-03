<?php
declare(strict_types=1);
namespace PhpMVC\Validation;

use PhpMVC\Validation\Rule\Rule;
use PhpMVC\Validation\Exception\ValidationException;

/**
 * Class Manager
 *
 * Validation orchestrator responsible for:
 *  - Registering validation rule processors under string aliases.
 *  - Executing a rule set against input data.
 *  - Aggregating field-level error messages.
 *  - Throwing a {@see ValidationException} when validation fails and optionally
 *    clearing prior session errors on success.
 *
 * Rule syntax:
 *  - Each rule is referenced by its alias (registered via {@see Manager::addRule()}).
 *  - Rules may include parameters using the format: "rule:param1,param2".
 *    Example: "min:8" or "in:admin,manager,user" (depending on implemented rules).
 *
 * Expected rule contract:
 *  - A rule must implement {@see Rule::validate()} and {@see Rule::getMessage()}.
 *  - {@see Rule::validate()} should return true when the field is valid; false otherwise.
 *
 * Session behavior:
 *  - On validation failure, the exception is populated with errors and a session name
 *    (default: 'errors') so an upstream exception handler can persist them.
 *  - On validation success, if a session driver is available, the stored error key
 *    for the provided session name is removed.
 *
 * @package PhpMVC\Validation
 */
class Manager
{
    /**
     * Registered rule processors keyed by alias.
     *
     * @var array<string, Rule>
     */
    protected array $rules = [];

    /**
     * Register a rule processor under an alias.
     *
     * @param string $alias The string alias used in validation definitions (e.g., 'required', 'email').
     * @param Rule   $rule  The rule processor instance.
     *
     * @return static
     */
    public function addRule(string $alias, Rule $rule): static
    {
        $this->rules[$alias] = $rule;
        return $this;
    }

    /**
     * Validate input data against a ruleset.
     *
     * The `$rules` array should be keyed by field name, with each value being a list
     * of rule strings (aliases, optionally with parameters).
     *
     * Example:
     * ```
     * $manager->validate(
     *     ['email' => 'user@example.com', 'password' => 'secret'],
     *     ['email' => ['required', 'email'], 'password' => ['required', 'min:8']]
     * );
     * ```
     *
     * Behavior:
     *  - Iterates each field and its rule list.
     *  - Parses parameters when a rule contains ":" (e.g., "min:8").
     *  - Runs the corresponding processor from {@see $rules} by alias.
     *  - Collects one or more error messages per field.
     *  - Throws {@see ValidationException} if any errors exist.
     *  - If successful, clears the session error bag for `$sessionName` (if session is available).
     *  - Returns only the subset of `$data` that corresponds to keys present in `$rules`.
     *
     * @param array  $data        Raw input data (typically request input).
     * @param array  $rules       Validation rules keyed by field name.
     * @param string $sessionName Session key for storing errors (default: 'errors').
     *
     * @return array Filtered data containing only keys present in the `$rules` definition.
     *
     * @throws ValidationException When validation fails.
     */
    public function validate(array $data, array $rules, string $sessionName = 'errors'): array
    {
        $errors = [];

        foreach ($rules as $field => $rulesForField) {
            foreach ($rulesForField as $rule) {
                $name = $rule;
                $params = [];

                if (str_contains($rule, ':')) {
                    [$name, $params] = explode(':', $rule);
                    $params = explode(',', $params);
                }
                
                $processor = $this->rules[$name];

                if (!$processor->validate($data, $field, $params)) {
                    if (!isset($errors[$field])) {
                        $errors[$field] = [];
                    }

                    array_push($errors[$field], $processor->getMessage($data, $field, $params));
                }
            }
        }

        if (count($errors)) {
            $exception = new ValidationException();
            $exception->setErrors($errors);
            $exception->setSessionName($sessionName);
            throw $exception;
        } else {
            if ($session = session()) {
                $session->forget($sessionName);
            }
        }

        return array_intersect_key($data, $rules);
    }
}
