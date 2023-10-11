<?php declare(strict_types=1);

namespace Recommendy;

use Doctrine\DBAL\Connection;
use Exception;
use Recommendy\Manager\CustomFieldManager;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Recommendy extends Plugin
{
    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->setParameter($this->getContainerPrefix() . '.plugin_name', $this->getName());
    }

    /**
     * @param InstallContext $context
     * @throws InconsistentCriteriaIdsException
     */
    public function install(InstallContext $context): void
    {
        parent::install($context);
        ($this->getCustomFieldManager($context->getContext()))->install();
    }

    /**
     * @param UpdateContext $context
     */
    public function update(UpdateContext $context): void
    {
        parent::update($context);
        ($this->getCustomFieldManager($context->getContext()))->install();
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        ($this->getCustomFieldManager($context->getContext()))->uninstall();

        $connection = $this->container->get(Connection::class);
        try {
            $connection->executeStatement('DROP TABLE IF EXISTS `recommendy_bundle_matrix`;');
            $connection->executeStatement('DROP TABLE IF EXISTS `recommendy_identifier`;');
            $connection->executeStatement('DROP TABLE IF EXISTS `recommendy_article_similarity`;');
            $connection->executeStatement('DROP TABLE IF EXISTS `recommendy_tracking`;');
        } catch (Exception $exception) {
        }
    }

    /**
     * @param Context $context
     * @return CustomFieldManager
     */
    private function getCustomFieldManager(Context $context): CustomFieldManager
    {
        return new CustomFieldManager(
            $context,
            $this->container->get('custom_field_set.repository'),
            $this->container->get('snippet.repository')
        );
    }
}
