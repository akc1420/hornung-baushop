.DEFAULT_GOAL := help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
.PHONY: help

init:  ## Initialize shopware, install cms-extensions, dump the test database
	cd ../../../ \
		&& ./psh.phar init\
		&& php bin/console plugin:refresh\
		&& php bin/console plugin:install --activate -c SwagCmsExtensions\
		&& ./psh.phar init-test-databases\
		&& ./psh.phar storefront:init\
		&& ./psh.phar administration:init\
		&& cd custom/plugins/cms-extensions\
		&& composer install
.PHONY: init

administration-fix: ## Run eslint on the administration files
	../../../vendor/shopware/platform/src/Administration/Resources/app/administration/node_modules/.bin/eslint --ignore-path .eslintignore --config ../../../vendor/shopware/platform/src/Administration/Resources/app/administration/.eslintrc.js --ext .js,.vue --fix src/Resources/app/administration
.PHONY: administration-fix

storefront-fix: ## Run eslint on the storefront files
	../../../vendor/shopware/platform/src/Storefront/Resources/app/storefront/node_modules/.bin/eslint --ignore-path .eslintignore --config ../../../vendor/shopware/platform/src/Storefront/Resources/app/storefront/.eslintrc.js --ext .js,.vue --fix src/Resources/app/storefront
.PHONY: storefront-fix

administration-lint: ## Run eslint on the administration files
	../../../vendor/shopware/platform/src/Administration/Resources/app/administration/node_modules/.bin/eslint --ignore-path .eslintignore --config ../../../vendor/shopware/platform/src/Administration/Resources/app/administration/.eslintrc.js --ext .js,.vue src/Resources/app/administration
.PHONY: administration-lint

storefront-lint: ## Run eslint on the storefront files
	../../../vendor/shopware/platform/src/Storefront/Resources/app/storefront/node_modules/.bin/eslint --ignore-path .eslintignore --config ../../../vendor/shopware/platform/src/Storefront/Resources/app/storefront/.eslintrc.js --ext .js,.vue src/Resources/app/storefront
.PHONY: storefront-lint

eslint: administration-lint ## Synonym to 'administration-lint'
.PHONY: eslint

eslint-fix: administration-fix ## Synonym to 'administration-fix'
.PHONY: eslint-fix

ecs-fix: ## Run easy coding style in fix mode
	php vendor/bin/ecs --fix
.PHONY: ecs-fix

ecs-dry: ## Run easy coding style in dry mode
	php vendor/bin/ecs
.PHONY: ecs-dry

phpstan: ## Run phpstan
	php bin/phpstan-config-generator.php \
    && composer dump-autoload \
    && php ../../../dev-ops/analyze/vendor/bin/phpstan analyze --configuration phpstan.neon --autoload-file=../../../vendor/autoload.php src tests
.PHONY: phpstan

psalm: ## Run vimeo psalm
	php ../../../dev-ops/analyze/vendor/bin/psalm --config=psalm.xml --threads=2 --diff --show-info=false
.PHONY: psalm

phpunit: ## Run phpunit
	composer dump-autoload \
	    && touch vendor/composer/InstalledVersions.php \
 		&& ./../../../vendor/bin/phpunit --filter "$(FILTER)"
.PHONY: phpunit

phpunit-coverage: ## Run phpunit with coverage report
	composer dump-autoload \
	    && touch vendor/composer/InstalledVersions.php \
 		&& php -d pcov.enabled=1 -d pcov.directory=./.. ./../../../vendor/bin/phpunit --configuration phpunit.xml.dist --log-junit ./../../../build/artifacts/phpunit-cms-extensions.junit.xml --coverage-clover ./../../../build/artifacts/phpunit-cms-extensions.clover.xml --coverage-html ./../../../build/artifacts/coverage-cms-extensions --coverage-text --filter "$(FILTER)"
.PHONY: phpunit-coverage
