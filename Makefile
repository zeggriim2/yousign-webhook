DC = docker compose run --rm php

.PHONY: help build install test phpstan cs cs-fix

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-10s\033[0m %s\n", $$1, $$2}'

build: ## Build l'image Docker
	docker compose build

install: ## Installe les dépendances Composer
	$(DC) composer install

test: ## Lance les tests PHPUnit
	$(DC) vendor/bin/phpunit

phpstan: ## Lance l'analyse statique PHPStan
	$(DC) vendor/bin/phpstan analyse --memory-limit=512M

cs: ## Vérifie le style de code (dry-run)
	$(DC) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Corrige le style de code
	$(DC) vendor/bin/php-cs-fixer fix
