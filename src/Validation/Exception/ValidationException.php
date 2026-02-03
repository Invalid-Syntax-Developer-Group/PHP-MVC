<?php
declare(strict_types=1);
namespace PhpMVC\Validation\Exception;

use InvalidArgumentException;

/**
 * Class ValidationException
 *
 * Domain exception used to represent one or more validation failures.
 *
 * This exception acts as a transport object for:
 *  - A structured collection of validation error messages (typically grouped by field name).
 *  - The session "error bag" key that upstream handlers may use to persist errors across redirects.
 *
 * Typical usage:
 *  - A validator/manager populates errors via {@see ValidationException::setErrors()} and sets the session key
 *    via {@see ValidationException::setSessionName()} before throwing the exception.
 *  - An exception handler inspects the instance, stores {@see ValidationException::getErrors()} into the session
 *    under {@see ValidationException::getSessionName()}, and redirects back to the originating page.
 *
 * Error shape convention:
 *  - Common patterns include:
 *      - array<string, string>  (one message per field), or
 *      - array<string, string[]> (multiple messages per field)
 *    This class does not enforce a specific schema beyond "array".
 */
class ValidationException extends InvalidArgumentException
{
    /**
     * Validation errors payload.
     *
     * @var array<string, mixed>
     */
    protected array $errors = [];

    /**
     * Session key/name to store the validation errors under.
     *
     * @var string
     */
    protected string $sessionName = 'errors';

    /**
     * Set the validation errors payload.
     *
     * @param array<string, mixed> $errors Validation errors, commonly grouped by field.
     *
     * @return static
     */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Get the validation errors payload.
     *
     * @return array<string, mixed> Validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Set the session key/name used to persist the errors.
     *
     * @param string $sessionName Session key for the error bag.
     *
     * @return static
     */
    public function setSessionName(string $sessionName): static
    {
        $this->sessionName = $sessionName;
        return $this;
    }

    /**
     * Get the session key/name used to persist the errors.
     *
     * @return string Session key for the error bag.
     */
    public function getSessionName(): string
    {
        return $this->sessionName;
    }
}
