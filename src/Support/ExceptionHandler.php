<?php
declare(strict_types=1);
namespace PhpMVC\Support;

use Throwable;
use PhpMVC\Validation\Exception\ValidationException;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;

/**
 * Class ExceptionHandler
 *
 * Centralized exception presentation/response handler for the framework.
 *
 * Responsibilities:
 *  - Detects and handles known exception types (e.g., {@see ValidationException})
 *  - In development environments, renders a friendly debug page for unhandled throwables
 *  - For handled cases, returns an appropriate response (typically a redirect)
 *
 * Environment behavior:
 *  - If app environment is set to 'dev', non-validation throwables are presented
 *    using a "friendly" debugging handler.
 *  - In non-dev environments, unknown throwables are not explicitly handled here and
 *    will fall through, allowing upstream logic to decide a generic error response.
 *
 * @package PhpMVC\Support
 */
class ExceptionHandler
{
    /**
     * Route a {@see Throwable} to an appropriate display/response method.
     *
     * Behavior:
     *  - If the throwable is a {@see ValidationException}, stores validation errors
     *    into the session and redirects back to the referrer.
     *  - Otherwise, if APP_ENV is 'dev', renders a friendly debug view and rethrows
     *    the throwable to allow the debugger to handle it.
     *  - If neither condition applies, no explicit return is made.
     *
     * @param Throwable $throwable The throwable to present/handle.
     *
     * @return mixed A framework response for handled scenarios, or null if not handled.
     */
    public function showThrowable(Throwable $throwable)
    {
        if ($throwable instanceof ValidationException) {
            return $this->showValidationException($throwable);
        }

        if ($this->isDevEnvironment()) {
            $this->showFriendlyThrowable($throwable);
        }
    }

    /**
     * Handle a {@see ValidationException} by persisting errors to session and redirecting.
     *
     * If a session driver is available, the validation errors are written using the
     * session key provided by the exception. The user is then redirected back to the
     * previous page (based on the HTTP referrer).
     *
     * @param ValidationException $exception The validation exception instance.
     *
     * @return mixed A redirect response to the referrer.
     */
    public function showValidationException(ValidationException $exception)
    {
        if ($session = session()) {
            $session->put($exception->getSessionName(), $exception->getErrors());
        }
        
        return redirect(env('HTTP_REFERER'));
    }

    /**
     * Render a developer-friendly exception page and rethrow the throwable.
     *
     * Uses a Whoops-style pretty page handler to display a detailed exception page.
     * After registering the handler, the throwable is rethrown so the registered
     * handler can render the output.
     *
     * @param Throwable $throwable The throwable to display.
     *
     * @return void
     *
     * @throws Throwable Always rethrows the provided throwable after registering the handler.
     */
    public function showFriendlyThrowable(Throwable $throwable)
    {
        $whoops = new Run();
        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->register();

        throw $throwable;
    }

    /**
     * Determine if the application is running in a development environment.
     *
     * Checks common environment variable names for indicators of a
     * development setting (e.g., 'dev', 'development', 'local').
     *
     * @return bool True if the environment is considered development.
     */
    private function isDevEnvironment(): bool
    {
        $keys = [
            'APP_ENV',
            'APP_ENVIRONMENT',
            'APPLICATION_ENV',
            'APPLICATION_ENVIRONMENT',
        ];

        $devValues = [
            'dev',
            'development',
            'localhost',
        ];

        foreach ($keys as $key) {
            if (!isset($_ENV[$key])) {
                continue;
            }

            $value = strtolower(trim((string) $_ENV[$key]));

            if (in_array($value, $devValues, true)) {
                return true;
            }
        }

        return false;
    }
}
