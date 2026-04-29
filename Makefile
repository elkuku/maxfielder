SHELL := /bin/bash

tests: export APP_ENV=test
tests:
	symfony php bin/phpunit --testdox $(@)
	-vendor/bin/rector process --dry-run || echo "Rector found issues (non-blocking)"
	-vendor/bin/phpstan --memory-limit=2G || echo "PHPStan found issues (non-blocking)"
#	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff
.PHONY: tests
