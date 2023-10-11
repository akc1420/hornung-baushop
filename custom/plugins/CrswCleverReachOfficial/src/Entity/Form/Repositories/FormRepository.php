<?php

namespace Crsw\CleverReachOfficial\Entity\Form\Repositories;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetriveFormException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;
use Crsw\CleverReachOfficial\Entity\Base\Repositories\BaseRepository;

/**
 * Class FormRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Form\Repositories
 */
class FormRepository extends BaseRepository
{
	/**
	 * @param string $shopwareId
	 *
	 * @return string
	 *
	 * @throws FailedToRetriveFormException
	 */
    public function getByShopwareId(string $shopwareId): string
    {
		if (!ctype_xdigit($shopwareId)) {
			throw new FailedToRetriveFormException();
		}

        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "SELECT data FROM cleverreach_entity
        WHERE type = 'Form' AND HEX(shopware_id) =:shopwareId";

        $data = $this->getConnection()->fetchAll($sql, ['shopwareId' => $shopwareId]);

        if (!isset($data[0])) {
            return '';
        }

        /** @var Form $entity */
        $entity = $this->unserializeEntity($data[0]['data']);

        return $entity->getContent();
    }

    /**
     * Gets default CleverReach form.
     *
     * @return array
     */
    public function getDefaultForm(): array
    {
        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "SELECT HEX(shopware_id) AS shopware_id, data FROM cleverreach_entity
        WHERE type = 'Form'";

        $forms = $this->getConnection()->fetchAll($sql);

        if (!isset($forms[0])) {
            return [];
        }

        foreach ($forms as $form) {
            /** @var Form $entity */
            $entity = $this->unserializeEntity($form['data']);

            if ($entity->getName() === 'Shopware 6') {
                return $form;
            }
        }

        return $forms[0];
    }

    /**
     * Gets default CleverReach form entity.
     *
     * @return Form|null
     */
    public function getDefaultFormEntity(): ?Form
    {
        $form = $this->getDefaultForm();

        if (!empty($form['data'])) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $this->unserializeEntity($form['data']);
        }

        return null;
    }
}
