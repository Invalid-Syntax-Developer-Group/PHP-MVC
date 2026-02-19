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
    function validate(array $data, array $rules, string $sessionName = 'errors', array $ruleVariables = [])
    {
        return app('validator')->validate($data, $rules, $sessionName, $ruleVariables);
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
        $default = (string)config('session.default', 'native');
        $config = config("session.{$default}", []);
        $authentication = $config['authentication'] ?? [];
        $key = (string)($authentication['csrf'] ?? 'token');

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

if (!function_exists('component')) {
    function component(string $name, array $props = [])
    {
        $base = app('paths.base');
        $class = $base . '\\Components\\' . str_replace('.', '\\', $name);    
        if (class_exists($class)) {
            return (string) new $class($props);
        }

        throw new Exception("Component {$name} not found");
    }
}

if (!function_exists('cookie_domain')) {
    function cookie_domain(): string
    {
        $httpHost = (string)filter_input(INPUT_SERVER, 'HTTP_HOST', FILTER_SANITIZE_URL);
        if (!empty($httpHost) && $httpHost !== 'localhost' &&
        filter_var($httpHost, FILTER_VALIDATE_IP) === false) {
            $parts = explode('.', $httpHost);
            if (count($parts) >= 2) {
                return '.' . implode('.', array_slice($parts, -2));
            }
        }
        return 'localhost';
    }
}

if (!function_exists('secure_cookie')) {
    function secure_cookie(): bool
    {
        $serverPort = (int)(filter_input(INPUT_SERVER, 'SERVER_PORT', FILTER_VALIDATE_INT) ?? ($_SERVER['SERVER_PORT'] ?? 0));
        if ($serverPort === 443) {
            return true;
        }

        $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
        if (in_array($https, ['on', '1'], true)) {
            return true;
        }

        $forwardedProto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        if (!empty($forwardedProto) && str_contains($forwardedProto, 'https')) {
            return true;
        }

        $frontEndHttps = strtolower((string)($_SERVER['HTTP_FRONT_END_HTTPS'] ?? ''));
        if (in_array($frontEndHttps, ['on', '1'], true)) {
            return true;
        }

        return false;
    }
}
