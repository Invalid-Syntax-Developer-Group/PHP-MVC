<?php
declare(strict_types=1);
namespace PhpMVC\Logging\Driver;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Class StreamDriver
 *
 * Monolog-based logging driver that writes log entries to a stream (typically
 * a file) using {@see StreamHandler}.
 *
 * This driver lazily initializes a {@see Logger} instance on first use and
 * supports standard log severity levels defined by the {@see Driver} interface.
 * All logging methods return the current instance to allow fluent chaining.
 *
 * Expected configuration keys:
 *  - name    : string  Logger channel name.
 *  - path    : string  File path or stream URI to write logs to.
 *  - minimum : int     Minimum log level (Monolog constant, e.g. Logger::INFO).
 *
 * @package PhpMVC\Logging\Driver
 * @since   1.0
 */
class StreamDriver implements Driver
{
    /**
     * Driver configuration options.
     *
     * @var array
     */
    private array $config;

    /**
     * Lazily-initialized Monolog logger instance.
     *
     * @var Logger|null
     */
    private ?Logger $logger = null;

    /**
     * StreamDriver constructor.
     *
     * Stores the configuration required to initialize the underlying
     * Monolog logger and stream handler.
     *
     * @param array $config Logger configuration array.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Log an informational message.
     *
     * @param string $message Informational log message.
     *
     * @return static Fluent interface.
     */
    public function info(string $message): static
    {
        $this->logger()->info($message);
        return $this;
    }

    /**
     * Log a warning message.
     *
     * @param string $message Warning log message.
     *
     * @return static Fluent interface.
     */
    public function warning(string $message): static
    {
        $this->logger()->warning($message);
        return $this;
    }

    /**
     * Log an error message.
     *
     * @param string $message Error log message.
     *
     * @return static Fluent interface.
     */
    public function error(string $message): static
    {
        $this->logger()->error($message);
        return $this;
    }

    /**
     * Retrieve or initialize the Monolog logger instance.
     *
     * Creates the logger on first access, assigns the configured channel
     * name, and attaches a {@see StreamHandler} using the configured path
     * and minimum log level.
     *
     * @return Logger Initialized Monolog logger.
     */
    private function logger(): Logger
    {
        if (!isset($this->logger)) {
            $this->logger = new Logger($this->config['name']);
            $this->logger->pushHandler(new StreamHandler($this->config['path'], $this->config['minimum']));
        }

        return $this->logger;
    }
}
