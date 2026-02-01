<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Support;

use Throwable;
use PhpMVC\Framework\Validation\Exception\ValidationException;

class ExceptionHandler
{
    public function showThrowable(Throwable $throwable)
    {
        if ($throwable instanceof ValidationException) {
            return $this->showValidationException($throwable);
        }

        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
            $this->showFriendlyThrowable($throwable);
        }
    }

    public function showValidationException(ValidationException $exception)
    {
        if ($session = session()) {
            $session->put($exception->getSessionName(), $exception->getErrors());
        }
        
        return redirect(env('HTTP_REFERER'));
    }

    public function showFriendlyThrowable(Throwable $throwable)
    {
        $whoops = new Run();
        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->register();

        throw $throwable;
    }
}