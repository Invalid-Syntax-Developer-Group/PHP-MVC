<?php
declare(strict_types=1);
namespace PhpMVC\Email\Exception;

use RuntimeException;

/**
 * Class DriverException
 *
 * Domain-specific exception for email driver–related failures.
 *
 * This exception is thrown when an email driver cannot be resolved,
 * instantiated, or used due to invalid configuration or unsupported
 * driver types.
 *
 * Typical scenarios include:
 *  - Missing `type` key in email configuration
 *  - Unregistered or unsupported email driver alias
 *  - Runtime failures during driver initialization
 *
 * By extending {@see RuntimeException}, this class represents errors
 * that occur during application execution rather than compile-time
 * or developer errors.
 *
 * Intended usage:
 *  - Thrown by email driver factories and driver resolution logic
 *  - Caught by higher-level application code to handle email
 *    configuration or delivery issues gracefully
 *
 * @package PhpMVC\Email\Exception
 * @since   1.0
 */
final class DriverException extends RuntimeException
{
}