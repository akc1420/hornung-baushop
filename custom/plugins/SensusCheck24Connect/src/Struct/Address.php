<?php declare(strict_types=1);

namespace Sensus\Check24Connect\Struct;

class Address
{
    /**
     * @var string
     */
    protected $salutation;

    /**
     * @var string
     */
    protected $firstname;

    /**
     * @var string
     */
    protected $lastname;

    /**
     * @var string
     */
    protected $company;

    /**
     * @var string
     */
    protected $street;

    /**
     * @var string
     */
    protected $zip;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $country;

    /**
     * Address constructor.
     * @param string $salutation
     * @param string $firstname
     * @param string $lastname
     * @param string $company
     * @param string $street
     * @param string $zip
     * @param string $city
     * @param string $country
     */
    public function __construct(string $salutation, string $firstname, string $lastname, string $company, string $street, string $zip, string $city, string $country)
    {
        $this->salutation = $salutation;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->company = $company;
        $this->street = $street;
        $this->zip = $zip;
        $this->city = $city;
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getSalutation(): string
    {
        return $this->salutation;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @return string
     */
    public function getCompany(): string
    {
        return $this->company;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @return string
     */
    public function getZip(): string
    {
        return $this->zip;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }




}