<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Registration;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts\RegistrationService as BaseRegistrationService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\System\User\UserEntity;

/**
 * Class RegistrationService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Registration
 */
class RegistrationService implements BaseRegistrationService
{
    /**
     * Returns shop owner registration data as base64 encoded json
     *
     * @return string base64 encoded json
     */
    public function getData(): string
    {
        $userData = $this->getUserData();

        return base64_encode(json_encode($userData));
    }

    /**
     * @return array
     */
    private function getUserData(): array
    {
        $request = $this->getRequestStack()->getCurrentRequest();

        if (!$request) {
            return [];
        }

        $userId = $request->get('sw-context')->getSource()->getUserId();
        /** @var UserEntity $user */
        $user = $this->getUserRepository()->search(new Criteria([$userId]), $request->get('sw-context'))->first();

        return [
            'email' => $user->getEmail(),
            'company' => '',
            'firstname' => $user->getFirstName() ?: $user->getUsername(),
            'lastname' => $user->getLastName(),
            'gender' => '',
            'street' => '',
            'zip' => '',
            'city' => '',
            'country' => '',
            'phone' => ''
        ];
    }

    /**
     * @return RequestStack
     */
    private function getRequestStack(): RequestStack
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(RequestStack::class);
    }

    /**
     * @return EntityRepository
     */
    private function getUserRepository(): EntityRepository
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService('User' . EntityRepository::class);
    }
}
