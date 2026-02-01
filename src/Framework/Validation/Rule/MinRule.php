<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Validation\Rule;

use InvalidArgumentException;

final class MinRule implements Rule
{
    public function validate(array $data, string $field, array $params): bool
    {
        if (empty($data[$field])) {
            return true;
        }

        if (empty($params[0])) {
            throw new InvalidArgumentException('specify a min length');
        }

        $length = (int) $params[0];

        return strlen($data[$field]) >= $length;
    }

    public function getMessage(array $data, string $field, array $params): string
    {
        $length = (int) $params[0];

        return "{$field} should be at least {$length} characters";
    }
}