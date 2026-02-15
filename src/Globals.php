<?php
declare(strict_types=1);

use PhpMVC\Core\Application;
use PhpMVC\View\View;

if (!function_exists('app')) {
    function app(?string $alias = null): mixed
    {
        if (is_null($alias)) {
            return Application::getInstance();
        }

        return Application::getInstance()->resolve($alias);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): View
    {
        $template = str_replace('.', '/', $template);
        return app('view')->render($template, $data);
    }
}

if (!function_exists('validate')) {
    function validate(array $data, array $rules, string $sessionName = 'errors')
    {
        return app('validator')->validate($data, $rules, $sessionName);
    }
}

if (!function_exists('response')) {
    function response()
    {
        return app('response');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url)
    {
        return response()->redirect($url);
    }
}

if (!function_exists('csrf')) {
    function csrf(int $length = 64)
    {
        $session = session();
        $key = (string)config('session.auth.csrf_identifier', 'token');

        if (!$session) {
            throw new Exception('Session is not enabled');
        }

        $session->put($key, $token = bin2hex(random_bytes($length / 2)));

        return $token;
    }
}

if (!function_exists('dd')) {
    function dd(...$params)
    {
        var_dump(...$params);
        die;
    }
}

if (!function_exists('basePath')) {
    function basePath(string $newBasePath = null): ?string
    {
        return app('paths.base');
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        if (isset($_SERVER[$key])) return $_SERVER[$key];
        if (isset($_ENV[$key])) return $_ENV[$key];

        $value = getenv($key);
        if ($value !== false) return $value;

        return $default;
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return app('config');
        }

        return app('config')->get($key, $default);
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return app('session');
        }

        return app('session')->get($key, $default);
    }
}

if (!function_exists('email')) {
    function email()
    {
        return app('email');
    }
}
