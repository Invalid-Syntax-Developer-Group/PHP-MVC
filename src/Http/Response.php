<?php
declare(strict_types=1);
namespace PhpMVC\Http;

use InvalidArgumentException;

/**
 * Class Response
 *
 * HTTP response abstraction used to construct and emit responses in a
 * framework-agnostic, fluent manner.
 *
 * This class encapsulates:
 *  - Response type (HTML, JSON, or REDIRECT)
 *  - HTTP status code
 *  - Response headers
 *  - Response body content
 *
 * It allows controllers and middleware to build responses declaratively
 * and defer actual output until {@see Response::send()} is called.
 *
 * Supported response types:
 *  - HTML     : Sends text/html content
 *  - JSON     : Sends application/json content
 *  - REDIRECT : Sends an HTTP redirect via Location header
 *
 * Design notes:
 *  - Uses a fluent interface for mutation methods
 *  - Getter/setter dual-purpose methods return current value when called
 *    with no arguments
 *  - Output is emitted immediately when {@see Response::send()} is invoked
 *
 * @package PhpMVC\Http
 * @since   1.1
 */
final class Response
{
    /**
     * HTML redirect response type.
     */
    const REDIRECT = 'REDIRECT';

    /**
     * HTML content response type.
     */
    const HTML = 'HTML';

    /**
     * JSON content response type.
     */
    const JSON = 'JSON';

    /**
     * @var string Current response type.
     */
    private string $type = self::HTML;

    /**
     * @var string|null Redirect target URL.
     */
    private ?string $redirect = null;

    /**
     * @var mixed Response body content.
     */
    private mixed $content = '';

    /**
     * @var int HTTP status code.
     */
    private int $status = 200;

    /**
     * @var array<string,string> HTTP headers to be sent.
     */
    private array $headers = [];

    /**
     * Get or set the response content.
     *
     * Acts as a getter when called without arguments and as a setter
     * when a value is provided.
     *
     * @param mixed|null $content Response body content.
     *
     * @return mixed|static Current content (getter) or fluent instance (setter).
     */
    public function content(mixed $content = null): mixed
    {
        if (is_null($content)) return $this->content;
        $this->content = $content;
        return $this;
    }

    /**
     * Get or set the HTTP status code.
     *
     * Acts as a getter when called without arguments and as a setter
     * when a status code is provided.
     *
     * @param int|null $status HTTP status code.
     *
     * @return int|static Current status (getter) or fluent instance (setter).
     */
    public function status(?int $status = null): int|static
    {
        if (is_null($status)) return $this->status;
        $this->status = $status;
        return $this;
    }

    /**
     * Add or overwrite an HTTP response header.
     *
     * @param string $key   Header name.
     * @param string $value Header value.
     *
     * @return static Fluent return for chaining.
     */
    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Get or set a redirect response.
     *
     * When setting a redirect URL, the response type is automatically
     * changed to {@see REDIRECT}.
     *
     * @param string|null $redirect Redirect target URL.
     *
     * @return mixed|static Current redirect (getter) or fluent instance (setter).
     */
    public function redirect(?string $redirect = null): mixed
    {
        if (is_null($redirect)) {
            return $this->redirect;
        }

        $this->redirect = $redirect;
        $this->type = static::REDIRECT;
        $this->status = 302; // Default to 302 Found for redirects
        return $this;
    }

    /**
     * Set a JSON response.
     *
     * Assigns the response content and switches the response type
     * to {@see JSON}.
     *
     * @param mixed $content Data to be JSON-encoded.
     *
     * @return static Fluent return for chaining.
     */
    public function json(mixed $content): static
    {
        $this->content = $content;
        $this->type = static::JSON;
        return $this;
    }

    /**
     * Get or set the response type.
     *
     * Acts as a getter when called without arguments and as a setter
     * when a type is provided.
     *
     * @param string|null $type Response type (HTML, JSON, REDIRECT).
     *
     * @return string|static Current type (getter) or fluent instance (setter).
     */
    public function type(?string $type = null): string|static
    {
        if (is_null($type)) return $this->type;
        $this->type = $type;
        return $this;
    }

    /**
     * Send the HTTP response to the client.
     *
     * Emits headers, sets the HTTP status code, and outputs the response
     * body based on the configured response type.
     *
     * @return void
     *
     * @throws InvalidArgumentException If the response type is unsupported.
     */
    public function send(): void
    {
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        switch ($this->type) {
            case static::HTML:
                header('Content-Type: text/html');
                http_response_code($this->status);
                echo $this->content;
                return;
            case static::JSON:
                header('Content-Type: application/json');
                http_response_code($this->status);
                echo json_encode($this->content);
                return;
            case static::REDIRECT:
                header("Location: {$this->redirect}");
                http_response_code($this->status);
                return;
        }

        throw new InvalidArgumentException("{$this->type} is not a recognised type");
    }
}