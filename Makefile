SHELL := /bin/bash

tests: export APP_ENV=test
tests:
	symfony php bin/phpunit --testdox $(@)
	vendor/bin/rector process --dry-run
	vendor/bin/phpstan --memory-limit=2G
	vendor/bin/php-cs-fixer check --diff
cs:
	vendor/bin/php-cs-fixer fix
.PHONY: tests cs
