<?php
declare(strict_types=1);
namespace PhpMVC\Validation;

use InvalidArgumentException;
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
 * @since 1.2
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
     *  - Runs the corresponding processor from {@see Rules} by alias.
     *  - Collects one or more error messages per field.
     *  - Throws {@see ValidationException} if any errors exist.
     *  - If successful, clears the session error bag for `$sessionName` (if session is available).
     *  - Returns only the subset of `$data` that corresponds to keys present in `$rules`.
     *
     * @param array  $data        Raw input data (typically request input).
     * @param array  $rules       Validation rules keyed by field name.
     * @param string $sessionName Session key for storing errors (default: 'errors').
     * @param array  $ruleVariables Optional associative array of variable names to values for parameter resolution in rules.
     *
     * @return array Filtered data containing only keys present in the `$rules` definition.
     *
     * @throws ValidationException When validation fails.
     */
    public function validate(array $data, array $rules, string $sessionName = 'errors', array $ruleVariables = []): array
    {
        $errors = [];

        foreach ($rules as $field => $rulesForField) {
            foreach ($rulesForField as $rule) {
                ['name' => $name, 'parameters' => $params] = $this->parseRuleDefinition($rule, $ruleVariables);

                if (!isset($this->rules[$name])) {
                    throw new InvalidArgumentException("Validation rule '{$name}' is not registered.");
                }

                $processor = $this->rules[$name];

                if (!$processor->validate($data, $field, $params)) {
                    if (!isset($errors[$field])) {
                        $errors[$field] = [];
                    }

                    $errors[$field][] = $processor->getMessage($data, $field, $params);
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

    /**
     * Normalize parameters from various formats into a consistent array structure.
     *
     * Supported input formats:
     *  - Array: ['param1', 'param2']
     *  - String (comma-separated): "param1,param2"
     *  - Null or empty string: treated as an empty array
     *
     * @param mixed $parameters The parameters to normalize.
     * @param bool  $fromStringSyntax Whether the input is expected to be a string with comma-separated values.
     *
     * @return array Normalized parameters as an array.
     */
    private function normalizeParameters(mixed $parameters, bool $fromStringSyntax = false): array
    {
        if (is_array($parameters)) return $parameters;

        if ($parameters === null || empty($parameters)) return [];

        if ($fromStringSyntax && is_string($parameters)) {
            return array_map('trim', explode(',', $parameters));
        }

        return (array)$parameters;
    }

    /**
     * Parse a rule definition which can be either a string (with optional parameters) or an array.
     *
     * Supported formats:
     *  - String: "rule" or "rule:param1,param2"
     *  - Array with 'name' key: ['name' => 'rule', 'parameters' => [...]]
     *  - List array: ['rule', param1, param2]
     *
     * @param string|array $definition The rule definition to parse.
     *
     * @return array An associative array with 'name' and 'parameters' keys.
     *
     * @throws InvalidArgumentException If the definition format is invalid.
     */
    private function parseRuleDefinition($definition, array $ruleVariables = []): array
    {
        if (is_string($definition)) {
            $parts = explode(':', $definition, 2);
            $name = $parts[0];
            $params = $this->normalizeParameters($parts[1] ?? '', true);
        } else if (is_array($definition)) {
            if (isset($definition['name'])) {
                $name = $definition['name'];
                $params = $this->normalizeParameters($definition['parameters'] ?? [], false);
            } else if (array_is_list($definition)) {
                $name = $definition[0] ?? '';
                $params = $this->normalizeParameters(array_slice($definition, 1), false);
            } else {
                throw new InvalidArgumentException('Invalid rule definition array format.');
            }

            if (!is_array($params)) {
                $params = [$params];
            }
        } else {
            throw new InvalidArgumentException('Rule definition must be a string or an array.');
        }

        $params = $this->resolveParameterTokens($params, $ruleVariables);

        return ['name' => $name, 'parameters' => $params];
    }

    /**
     * Resolve parameter tokens in an array of parameters.
     *
     * Each parameter is processed through {@see resolveParameterToken()} to handle
     * any string tokens that start with "$" as variable references.
     *
     * @param array $params The array of parameters to resolve.
     * @param array $ruleVariables An associative array of variable names to values for resolution.
     *
     * @return array The array of parameters with any tokens resolved to their corresponding values.
     */
    private function resolveParameterTokens(array $params, array $ruleVariables): array
    {
        return array_map(
            fn($parameter) => $this->resolveParameterToken($parameter, $ruleVariables),
            $params
        );
    }

    /**
     * Resolve a single parameter token if it starts with "$".
     *
     * If the parameter is a string starting with "$", it is treated as a variable reference
     * and looked up in the provided `$ruleVariables` array. If the variable is not defined,
     * an exception is thrown. If the parameter does not start with "$", it is returned as-is.
     *
     * @param mixed $parameter The parameter to resolve.
     * @param array $ruleVariables An associative array of variable names to values for resolution.
     *
     * @return mixed The resolved parameter value or the original parameter if no resolution was needed.
     *
     * @throws InvalidArgumentException If a variable reference is not defined in `$ruleVariables`.
     */
    private function resolveParameterToken(mixed $parameter, array $ruleVariables): mixed
    {
        if (!is_string($parameter) || !str_starts_with($parameter, '$')) {
            return $parameter;
        }

        $name = substr($parameter, 1);
        if (empty($name)) {
            return $parameter;
        }

        if (!array_key_exists($name, $ruleVariables)) {
            throw new InvalidArgumentException(
                "Validation rule variable '{$parameter}' is not defined."
            );
        }

        return $ruleVariables[$name];
    }
}
