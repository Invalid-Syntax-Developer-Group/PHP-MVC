<?php
declare(strict_types=1);
namespace PhpMVC\Email\Exception;

use InvalidArgumentException;

/**
 * Class CompositionException
 *
 * Exception thrown when an email message cannot be composed correctly.
 *
 * This exception represents validation or construction errors that occur
 * before an email is handed off to a transport or delivery driver.
 *
 * Typical scenarios include:
 *  - Missing required message fields (e.g. recipient, subject, body)
 *  - Invalid message format or unsupported composition options
 *  - Conflicting or incomplete message configuration
 *
 * By extending {@see InvalidArgumentException}, this exception signals
 * that the caller has provided invalid or insufficient input for
 * composing an email.
 *
 * Intended usage:
 *  - Thrown during email message building or validation
 *  - Caught by application-level code to provide user-facing
 *    validation feedback or to abort message delivery
 *
 * @package PhpMVC\Email\Exception
 * @since   1.0
 */
final class CompositionException extends InvalidArgumentException
{
}
