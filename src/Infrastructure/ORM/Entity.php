<?php

namespace Logeecom\Infrastructure\ORM;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;

/**
 * Class Entity.
 *
 * @package Logeecom\Infrastructure\ORM\Entities
 */
abstract class Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Identifier.
     *
     * @var int
     */
    public $id;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected static $fields = array('id');

    /**
     * Returns full class name.
     *
     * @return string Fully qualified class name.
     */
    public static function getClassName()
    {
        return static::CLASS_NAME;
    }

    /**
     * Transforms raw array data to this entity instance.
     *
     * @param array $data Raw array data.
     *
     * @return static Transformed entity object.
     */
    public static function fromArray(array $data)
    {
        $instance = new static();
        foreach (static::$fields as $fieldName) {
            $instance->$fieldName = static::getArrayValue($data, $fieldName);
        }

        return $instance;
    }

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Configuration object.
     */
    abstract public function getConfig();

    /**
     * Transforms entity to its array format representation.
     *
     * @return array Entity in array format.
     */
    public function toArray()
    {
        $data = array();
        foreach (static::$fields as $fieldName) {
            $data[$fieldName] = $this->$fieldName;
        }

        return $data;
    }

    /**
     * Gets entity identifier.
     *
     * @return int Identifier.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets instance value for given index key.
     *
     * @param string $indexKey Name of index column.
     *
     * @return mixed Value for index.
     */
    public function getIndexValue($indexKey)
    {
        $methodName = 'get' . ucfirst($indexKey);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        $methodName = 'is' . ucfirst($indexKey);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        if (isset($this->$indexKey)) {
            return $this->$indexKey;
        }

        throw new \InvalidArgumentException('Neither field not getter found for index "' . $indexKey . '".');
    }

    /**
     * Gets value from the array for given key.
     *
     * @param array $search An array with keys to check.
     * @param string $key Key to get value for.
     * @param mixed $default Default value if key is not present.
     *
     * @return string Value from the array for given key if key exists; otherwise, $default value.
     */
    protected static function getArrayValue($search, $key, $default = '')
    {
        return array_key_exists($key, $search) ? $search[$key] : $default;
    }
}