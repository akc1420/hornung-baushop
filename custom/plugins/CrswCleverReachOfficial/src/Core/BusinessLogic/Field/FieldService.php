<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Field;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Contracts\FieldService as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\Contracts\FieldType;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Translator;

abstract class FieldService implements BaseService
{
    /**
     * Retrieve list of fields that an integration supports.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Field\DTO\Field[]
     */
    public function getFields()
    {
          $fields = $this->getSupportedFields();

          foreach ($fields as $field) {
              $field->setDescription($this->getFieldLabel($field->getName()));
          }

          return $fields;
    }

    /**
     * Retrieves lit of supported fields.
     *
     * @return Field[]
     */
     protected function getSupportedFields()
     {
         return array(
             new Field('salutation', FieldType::TEXT),
             new Field('title', FieldType::TEXT),
             new Field('firstname', FieldType::TEXT),
             new Field('lastname', FieldType::TEXT),
             new Field('street', FieldType::TEXT),
             new Field('zip', FieldType::TEXT),
             new Field('city', FieldType::TEXT),
             new Field('company', FieldType::TEXT),
             new Field('state', FieldType::TEXT),
             new Field('country', FieldType::TEXT),
             new Field('birthday', FieldType::DATE),
             new Field('phone', FieldType::TEXT),
             new Field('language', FieldType::TEXT),
         );
     }

    /**
     * Retrieves field label.
     *
     * @param string $fieldName Field identifier.
     *
     * @return string
     */
    protected function getFieldLabel($fieldName)
    {
        return Translator::translate($fieldName);
    }
}