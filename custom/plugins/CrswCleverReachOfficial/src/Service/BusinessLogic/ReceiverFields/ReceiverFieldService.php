<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\ReceiverFields;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Contracts\FieldType;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\FieldService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Contracts\TranslationService as BaseTranslationService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ReceiverFieldService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\ReceiverFields
 */
class ReceiverFieldService extends FieldService
{
    /**
     * @var array
     */
    private static $fields = [
        'salutation' => [
            'id' => 'account.personalSalutationLabel',
            'name' => 'salutation',
        ],
        'firstname' => [
            'id' => 'account.personalFirstNameLabel',
            'name' => 'firstname',
        ],
        'lastname' => [
            'id' => 'account.personalLastNameLabel',
            'name' => 'lastname',
        ],
        'birthday' => [
            'id' => 'account.personalBirthdayLabel',
            'name' => 'birthday',
        ],
        'lastorderdate' => [
            'id' => 'account.overviewNewestOrderHeader',
            'name' => 'lastorderdate',
        ],
        'customernumber' => [
            'id' => 'clever-reach.attributes.customerNumber',
            'name' => 'customernumber',
        ],
        'language' => [
            'id' => 'clever-reach.attributes.language',
            'name' => 'language',
        ],
        'street' => [
            'id' => 'address.streetLabel',
            'name' => 'street',
        ],
        'streetnumber' => [
            'id' => 'clever-reach.attributes.streetNumber',
            'name' => 'streetnumber'
        ],
        'zip' => [
            'id' => 'address.zipcodeLabel',
            'name' => 'zip',
        ],
        'shop' => [
            'id' => 'clever-reach.attributes.shop',
            'name' => 'shop',
        ],
        'city' => [
            'id' => 'address.cityLabel',
            'name' => 'city',
        ],
        'company' => [
            'id' => 'address.companyNameLabel',
            'name' => 'company',
        ],
        'country' => [
            'id' => 'address.countryLabel',
            'name' => 'country',
        ],
        'state' => [
            'id' => 'address.countryStateLabel',
            'name' => 'state',
        ],
        'phone' => [
            'id' => 'address.phoneNumberLabel',
            'name' => 'phone',
        ],
        'ordercount' => [
            'id' => 'clever-reach.attributes.orderCount',
            'name' => 'ordercount',
        ],
        'totalspent' => [
            'id' => 'clever-reach.attributes.totalSpent',
            'name' => 'totalspent'
        ]
    ];

    /**
     * Fields that do not have translations in Shopware snippets.
     *
     * @var string[]
     */
    private static $fieldsWithCustomTranslations = [
        'shop',
        'customernumber',
        'language',
        'totalspent',
        'ordercount',
        'streetnumber'
    ];

    /**
     * Retrieve list of fields that an integration supports.
     *
     * @return Field[]
     */
    public function getFields(): array
    {
        $fields = $this->getSupportedFields();

        foreach ($fields as $field) {
            $field->setDescription($this->getFieldLabel(self::$fields[$field->getName()]));
        }

        return $fields;
    }

    /**
     * Retrieves lit of supported fields.
     *
     * @return Field[]
     */
    protected function getSupportedFields(): array
    {
        return [
            new Field('salutation', FieldType::TEXT),
            new Field('firstname', FieldType::TEXT),
            new Field('lastname', FieldType::TEXT),
            new Field('street', FieldType::TEXT),
            new Field('streetnumber', FieldType::TEXT),
            new Field('zip', FieldType::TEXT),
            new Field('city', FieldType::TEXT),
            new Field('company', FieldType::TEXT),
            new Field('state', FieldType::TEXT),
            new Field('country', FieldType::TEXT),
            new Field('birthday', FieldType::DATE),
            new Field('phone', FieldType::TEXT),
            new Field('shop', FieldType::TEXT),
            new Field('customernumber', FieldType::TEXT),
            new Field('language', FieldType::TEXT),
            new Field('lastorderdate', FieldType::DATE),
            new Field('ordercount', FieldType::NUMBER),
            new Field('totalspent', FieldType::NUMBER),
        ];
    }

    /**
     * Retrieves field label.
     *
     * @param array $fieldName
     *
     * @return string
     */
    protected function getFieldLabel($fieldName): string
    {
        $lang = $this->getTranslationService()->getSystemLanguage();
        $catalogue = $this->getTranslator()->getCatalogue($lang);

        if (in_array($fieldName['name'], static::$fieldsWithCustomTranslations, true)) {
            return $this->getTranslationService()->translate($fieldName['id']);
        }

        $label = $catalogue->get($fieldName['id'], 'storefront');

        if ($label === $fieldName['id']) {
            $label = $catalogue->get($fieldName['id']);
        }

        return $label;
    }

    /**
     * @return TranslatorInterface
     */
    private function getTranslator(): TranslatorInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(TranslatorInterface::class);
    }

    /**
     * @return BaseTranslationService
     */
    private function getTranslationService(): BaseTranslationService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(BaseTranslationService::class);
    }
}
