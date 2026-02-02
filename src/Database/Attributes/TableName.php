<?php
declare(strict_types=1);
namespace PhpMVC\Database\Attributes;

use Attribute;

#[Attribute]
class TableName
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}