<?php

namespace Shopware\Core\Framework\Routing\Annotation;

/**
 * @Annotation
 *
 * @Attributes({
 *   @Attribute("scopes",  type = "array"),
 * })
 */
class RouteScope
{
    /**
     * @var array
     */
    private $scopes;

    public function __construct(array $values)
    {
        foreach ($values as $k => $v) {
            if (!method_exists($this, $name = 'set'.$k)) {
                throw new \RuntimeException(sprintf('Unknown key "%s" for annotation "@%s".', $k, self::class));
            }

            $this->$name($v);
        }
    }

    /**
     * @return string
     */
    public function getAliasName()
    {
        return 'routeScope';
    }

    /**
     * @return bool
     */
    public function allowArray()
    {
        return false;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setScopes(array $scopes): void
    {
        $this->scopes = $scopes;
    }

    public function hasScope(string $scopeName): bool
    {
        return \in_array($scopeName, $this->scopes, true);
    }
}
