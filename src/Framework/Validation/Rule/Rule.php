<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Validation\Rule;

interface Rule
{
    public function validate(array $data, string $field, array $params);
    public function getMessage(array $data, string $field, array $params);
}