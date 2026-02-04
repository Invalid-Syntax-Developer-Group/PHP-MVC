<?php
declare(strict_types=1);

Use Exception;
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
        require_authenticated_session();

        $session = session();

        if (!$session) {
            throw new Exception('Session is not enabled');
        }

        $session->put('token', $token = bin2hex(random_bytes($length / 2)));

        return $token;
    }
}

if (!function_exists('secure')) {
    function secure()
    {
        require_authenticated_session();

        $session = session();

        if (!$session) {
            throw new Exception('Session is not enabled');
        }

        if (!isset($_POST['csrf']) || !$session->has('token') ||  !hash_equals($session->get('token'), $_POST['csrf'])) {
            throw new Exception('CSRF token mismatch');
        }
    }
}

if (!function_exists('require_authenticated_session')) {
    /**
     * Enforce an authenticated session only when security helpers are used.
     *
     * Behaviour:
     *  - If the request arrives without a session cookie (and without an incoming sid), redirect to login.
     *  - If the authenticated-user session key is missing, redirect to login.
     */
    function require_authenticated_session(): void
    {
        $auth = (array)config('session.auth', []);
        if (!($auth['enabled'] ?? false)) {
            return;
        }

        $requestUri = (string)filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_UNSAFE_RAW);
        foreach ((array)($auth['skip_paths'] ?? []) as $prefix) {
            $prefix = (string)$prefix;
            if ($prefix !== '' && str_starts_with($requestUri, $prefix)) {
                return;
            }
        }

        // Ensure the session exists (and cookie params are applied) before we decide to redirect.
        // Resolving the session driver will start PHP's session if needed.
        $sessionDriver = session();
        if (!$sessionDriver) {
            throw new Exception('Session is not enabled');
        }

        $cookieName = session_name();
        if ($cookieName === '') {
            $cookieName = (string)config('session.native.name', 'PHPSESSID');
        }

        $hasCookie = isset($_COOKIE[$cookieName]) && is_string($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] !== '';

        $hasIncomingSid = false;
        $incomingParams = (array)config('session.incoming_session_id_params', []);
        foreach ($incomingParams as $paramName) {
            $candidate = filter_input(INPUT_GET, (string)$paramName, FILTER_UNSAFE_RAW);
            if (is_string($candidate) && $candidate !== '') {
                $hasIncomingSid = true;
                break;
            }
        }

        // If this request did not present a session cookie, force the auth flow.
        // Allow an incoming sid parameter to prevent loops on the return-trip.
        if (!$hasCookie && !$hasIncomingSid) {
            redirect_to_login($auth, $requestUri);
        }

        $requiredKey = (string)($auth['required_session_key'] ?? 'user_id');
        if (!isset($_SESSION[$requiredKey]) || $_SESSION[$requiredKey] === null || $_SESSION[$requiredKey] === '') {
            redirect_to_login($auth, $requestUri);
        }
    }
}

if (!function_exists('redirect_to_login')) {
    function redirect_to_login(array $auth, string $requestUri): void
    {
        $scheme = (string)filter_input(INPUT_SERVER, 'REQUEST_SCHEME', FILTER_SANITIZE_URL);
        if ($scheme === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        }

        $httpHost = (string)filter_input(INPUT_SERVER, 'HTTP_HOST', FILTER_SANITIZE_URL);
        $returnUrl = $scheme . '://' . $httpHost . $requestUri;

        $domain = '';
        if ($httpHost !== '' && $httpHost !== 'localhost' && filter_var($httpHost, FILTER_VALIDATE_IP) === false) {
            $parts = explode('.', $httpHost);
            if (count($parts) >= 2) {
                $domain = '.' . implode('.', array_slice($parts, -2));
            }
        }

        $subDomain    = (string)($auth['subdomain_prefix'] ?? '');
        $path         = (string)($auth['path'] ?? '');
        $returnParam  = (string)($auth['return_param'] ?? 'return');

        $loginHost   = ($domain !== '') ? ($subDomain . $domain) : $httpHost;
        $location    = $scheme . '://' . $loginHost . $path . '?' . rawurlencode($returnParam) . '=' . rawurlencode($returnUrl);

        header('Location: ' . $location, true, 302);
        exit;
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
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

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
