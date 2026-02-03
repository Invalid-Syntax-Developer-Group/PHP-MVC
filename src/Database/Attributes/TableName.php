<?php
declare(strict_types=1);
namespace PhpMVC\Database\Attributes;

use Attribute;

/**
 * Attribute TableName
 *
 * Declares the database table name associated with a model or entity class.
 * This attribute is intended to be applied at the class level and consumed
 * via reflection to resolve the underlying table without hard-coding it
 * inside the model logic.
 *
 * Example usage:
 * ```php
 * #[TableName('users')]
 * class User
 * {
 *     // ...
 * }
 * ```
 *
 * @package PhpMVC\Database\Attributes
 */
#[Attribute(Attribute::TARGET_CLASS)]
class TableName
{
    /**
     * The database table name.
     *
     * @var string
     */
    public string $name;

    /**
     * TableName constructor.
     *
     * Assigns the database table name represented by this attribute.
     *
     * @param string $name The database table name.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
