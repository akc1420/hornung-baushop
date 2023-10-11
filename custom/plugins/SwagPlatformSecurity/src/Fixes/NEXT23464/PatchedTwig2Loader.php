<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT23464;

use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

class PatchedTwig2Loader extends FilesystemLoader
{
    /**
     * Checks if the template can be found.
     *
     * In Twig 3.0, findTemplate must return a string or null (returning false won't work anymore).
     *
     * @param string $name  The template name
     * @param bool   $throw Whether to throw an exception when an error occurs
     *
     * @return string|false|null The template name or false/null
     */
    protected function findTemplate($name, $throw = true)
    {
        $name = preg_replace('#/{2,}#', '/', str_replace('\\', '/', $name));;

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (isset($this->errorCache[$name])) {
            if (!$throw) {
                return null;
            }

            throw new LoaderError($this->errorCache[$name]);
        }

        // https://regex101.com/r/SQEXYN/1
        $isStorefrontDistPath = preg_match('/@\w*\/\.\.\/app\/storefront\/dist.*/', $name);

        $parts = explode('/', $name);

        if (!$isStorefrontDistPath || (array_count_values($parts)['..'] ?? 0) > 1) {
            try {
                list($namespace, $shortname) = $this->parseName($name);

                $this->validateName($shortname);
            } catch (LoaderError $e) {
                if (!$throw) {
                    return null;
                }

                throw $e;
            }
        }

        return parent::findTemplate($name, $throw);
    }

    private function parseName(string $name, string $default = self::MAIN_NAMESPACE): array
    {
        if (isset($name[0]) && '@' == $name[0]) {
            if (false === $pos = strpos($name, '/')) {
                throw new LoaderError(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $name));
            }

            $namespace = substr($name, 1, $pos - 1);
            $shortname = substr($name, $pos + 1);

            return [$namespace, $shortname];
        }

        return [$default, $name];
    }

    private function validateName(string $name): void
    {
        if (false !== strpos($name, "\0")) {
            throw new LoaderError('A template name cannot contain NUL bytes.');
        }

        $name = ltrim($name, '/');
        $parts = explode('/', $name);
        $level = 0;
        foreach ($parts as $part) {
            if ('..' === $part) {
                --$level;
            } elseif ('.' !== $part) {
                ++$level;
            }

            if ($level < 0) {
                throw new LoaderError(sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
            }
        }
    }
}
