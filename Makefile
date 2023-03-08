SHELL := /bin/bash

create-test-db:
	symfony console doctrine:database:drop --force --env=test || true
	symfony console doctrine:database:create --env=test
	symfony console doctrine:migrations:migrate -n --env=test
.PHONY: create-test-db

load-test-fixtures:
	symfony console doctrine:fixtures:load -n --env=test
.PHONY: load-test-fixtures

tests:
	symfony php bin/phpunit $@
.PHONY: tests