<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT23562;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Adapter\Twig\EntityTemplateLoader;
use Shopware\Core\Framework\Feature;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;


class PatchedEntityTemplateLoader extends EntityTemplateLoader
{
    /**
     * @var array<string, array<string, array{template: string, updatedAt: \DateTimeInterface|null}|null>>
     */
    private $databaseTemplateCache = [];

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var EntityTemplateLoader
     */
    private $original;

    public function __construct(array $origArgs, EntityTemplateLoader $original, Connection $connection, string $environment)
    {
        parent::__construct(...$origArgs);
        $this->original = $original;
        $this->connection = $connection;
        $this->environment = $environment;
    }

    public function clearInternalCache(): void
    {
        Feature::triggerDeprecationOrThrow(
            'v6.5.0.0',
            Feature::deprecatedMethodMessage(__CLASS__, __METHOD__, 'v6.5.0.0', 'reset()')
        );

        $this->reset();
    }

    public function reset(): void
    {
        $this->databaseTemplateCache = [];
    }

    public function getSourceContext(/* string */ $name): Source
    {
        $template = $this->findDatabaseTemplatePatched($name);

        if (!$template) {
            throw new LoaderError(sprintf('Template "%s" is not defined.', $name));
        }

        return new Source($template['template'], $name);
    }

    public function getCacheKey(/* string */ $name): string
    {
        return $name;
    }

    public function isFresh(/* string */ $name, /* int */ $time): bool
    {
        $template = $this->findDatabaseTemplatePatched($name);
        if (!$template) {
            return false;
        }

        return $template['updatedAt'] === null || $template['updatedAt']->getTimestamp() < $time;
    }

    /**
     * @return bool
     */
    public function exists(/* string */ $name)
    {
        $template = $this->findDatabaseTemplatePatched($name);
        if (!$template) {
            return false;
        }

        return true;
    }

    /**
     * @return array{template: string, updatedAt: \DateTimeInterface|null}|null
     */
    private function findDatabaseTemplatePatched(string $name): ?array
    {
        if (isset($_ENV['DISABLE_EXTENSIONS']) && $_ENV['DISABLE_EXTENSIONS'] === false) {
            return null;
        }

        /*
         * In dev env app templates are directly loaded over the filesystem
         * @see TwigLoaderConfigCompilerPass::addAppTemplatePaths()
         */
        if ($this->environment === 'dev') {
            return null;
        }

        $templateName = $this->splitTemplateName($name);
        $namespace = $templateName['namespace'];
        $path = $templateName['path'];

        if (empty($this->databaseTemplateCache)) {
            $query = 'SELECT
                        `app_template`.`path` AS `path`,
                        `app_template`.`template` AS `template`,
                        `app_template`.`updated_at` AS `updatedAt`,
                        `app`.`name` AS `namespace`
                    FROM `app_template`
                    INNER JOIN `app` ON `app_template`.`app_id` = `app`.`id`
                    WHERE `app_template`.`active` = 1 AND `app`.`active` = 1';
            if (is_callable(
                [
                    $this->connection,
                    'fetchAllAssociative'
                ]
            )) {
                /** @var array<array{path: string, template: string, updatedAt: string|null, namespace: string}> $templates */
                $templates = $this->connection->fetchAllAssociative($query);
            } else {
                $templates = $this->connection->fetchAll($query);
            }

            foreach ($templates as $template) {
                $this->databaseTemplateCache[$template['path']][$template['namespace']] = [
                    'template' => $template['template'],
                    'updatedAt' => $template['updatedAt'] ? new \DateTimeImmutable($template['updatedAt']) : null,
                ];
            }
        }

        if (\array_key_exists($path, $this->databaseTemplateCache) && \array_key_exists($namespace, $this->databaseTemplateCache[$path])) {
            return $this->databaseTemplateCache[$path][$namespace];
        }

        /** @deprecated tag:v6.5.0 - only for intermediate backwards compatibility */
        if (
            \array_key_exists('../' . $path, $this->databaseTemplateCache)
            && \array_key_exists($namespace, $this->databaseTemplateCache['../' . $path])
        ) {
            return $this->databaseTemplateCache['../' . $path][$namespace];
        }

        // we have already loaded all DB templates
        // if the namespace is not included return null
        return $this->databaseTemplateCache[$path][$namespace] = null;
    }

    /**
     * @return array{namespace: string, path: string}
     */
    private function splitTemplateName(string $template): array
    {
        // remove static template inheritance prefix
        if (mb_strpos($template, '@') !== 0) {
            return ['path' => $template, 'namespace' => ''];
        }

        // remove "@"
        $template = mb_substr($template, 1);

        $template = explode('/', $template);
        $namespace = array_shift($template);
        $template = implode('/', $template);

        return ['path' => $template, 'namespace' => $namespace];
    }
}
