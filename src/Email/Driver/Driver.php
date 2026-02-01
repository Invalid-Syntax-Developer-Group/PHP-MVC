<?php
declare(strict_types=1);
namespace PhpMVC\Email\Driver;

/**
 * Interface Driver
 *
 * Contract for email delivery drivers within the PhpMVC framework.
 *
 * An email driver is responsible for composing and sending an email message
 * using a specific transport mechanism (e.g. PHP mail, SMTP, third-party API).
 * Implementations are expected to support a fluent interface for message
 * composition, followed by a terminal {@see send()} operation.
 *
 * Composition model:
 *  - Message attributes (recipient, subject, body) are set via chained calls
 *  - Implementations may internally validate required fields prior to sending
 *
 * Typical usage:
 *  ```php
 * $driver
 *      ->to('user@example.com')
 *      ->subject('Welcome')
 *      ->html('<p>Hello!</p>')
 *      ->send();
 * ```
 *
 * Error handling:
 *  - Implementations may throw domain-specific exceptions (e.g.
 *    CompositionException, DriverException) when composition or delivery fails
 *
 * @package PhpMVC\Email\Driver
 * @since   1.0
 */
interface Driver
{
    /**
     * Set the primary recipient address.
     *
     * @param string $to Recipient email address.
     *
     * @return static Fluent return for chaining.
     */
    public function to(string $to): static;

    /**
     * Set the email subject line.
     *
     * @param string $subject Email subject.
     *
     * @return static Fluent return for chaining.
     */
    public function subject(string $subject): static;

    /**
     * Set the plain-text body of the email.
     *
     * Implementations may use this as the sole body or as an alternative
     * body when an HTML message is also provided.
     *
     * @param string $text Plain-text message body.
     *
     * @return static Fluent return for chaining.
     */
    public function text(string $text): static;

    /**
     * Set the HTML body of the email.
     *
     * Implementations may generate a plain-text alternative automatically
     * or require {@see text()} to be called explicitly.
     *
     * @param string $html HTML message body.
     *
     * @return static Fluent return for chaining.
     */
    public function html(string $html): static;

    /**
     * Send the composed email message.
     *
     * This is the terminal operation in the fluent composition chain.
     * Implementations should validate that all required message attributes
     * are present before attempting delivery.
     *
     * @return void
     */
    public function send(): void;
}