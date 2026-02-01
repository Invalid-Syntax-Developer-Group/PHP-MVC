<?php
declare(strict_types=1);
namespace PhpMVC\Support;

use PhpMVC\Application;

class Config
{
    private array $loaded = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        if (!isset($this->loaded[$file])) {
            $base = Application::getInstance()->resolve('paths.base');
            $separator = DIRECTORY_SEPARATOR;

            $this->loaded[$file] = (array) require "{$base}{$separator}src{$separator}config{$separator}{$file}.php";
        }

        if ($value = $this->withDots($this->loaded[$file], $segments)) {
            return $value;
        }

        return $default;
    }

    private function withDots(array $array, array $segments): mixed
    {
        $current = $array;

        foreach ($segments as $segment) {
            if (!isset($current[$segment])) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}