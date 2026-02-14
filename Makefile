SHELL := /bin/bash

tests: export APP_ENV=test
tests:
	symfony php bin/phpunit --testdox $@
	vendor/bin/rector process --dry-run
	vendor/bin/phpstan --memory-limit=2G
#	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff
.PHONY: tests
