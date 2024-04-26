<?php

namespace Packlink\BusinessLogic\Http\DTO;

use Logeecom\Infrastructure\Data\DataTransferObject;

/**
 * Class User. Represents Packlink User.
 *
 * @package Packlink\BusinessLogic\Http\DTO
 */
class User extends DataTransferObject
{
    /**
     * First name.
     *
     * @var string
     */
    public $firstName;
    /**
     * Last name.
     *
     * @var string
     */
    public $lastName;
    /**
     * Email.
     *
     * @var string
     */
    public $email;
    /**
     * Default platform country. Two letter country code.
     *
     * @var string
     */
    public $country;
    /**
     * @var string
     */
    public $customerType;
    /**
     * @var string
     */
    public $taxId;

    /**
     * Transforms DTO to its array format suitable for http client.
     *
     * @return array DTO in array format.
     */
    public function toArray()
    {
        return array(
            'name' => $this->firstName,
            'surname' => $this->lastName,
            'email' => $this->email,
            'platform_country' => $this->country,
            'customer_type' => $this->customerType,
            'tax_id' => $this->taxId
        );
    }

    /**
     * Transforms raw array data to its DTO.
     *
     * @param array $raw Raw array data.
     *
     * @return static Transformed DTO object.
     */
    public static function fromArray(array $raw)
    {
        $user = new static();

        $user->firstName = static::getDataValue($raw, 'name');
        $user->lastName = static::getDataValue($raw, 'surname');
        $user->email = static::getDataValue($raw, 'email');
        $user->country = static::getDataValue($raw, 'platform_country');
        $user->customerType = static::getDataValue($raw, 'customer_type');
        $user->taxId = static::getDataValue($raw, 'tax_id');

        return $user;
    }
}
