# Digi-Santé Junior Easy — raccourcis
#
# Chaque commande est écrite en clair : vous pouvez aussi la copier et la
# lancer vous-même. Tapez `make` pour voir la liste.

CONSOLE = docker compose exec app php bin/console

.DEFAULT_GOAL := help
.PHONY: help install start stop bash cc migration migrate fixtures reset-db tests lint

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

install: ## Première installation : conteneurs, dépendances, base, données de démo
	docker compose up -d --build
	docker compose exec app composer install
	$(CONSOLE) doctrine:database:create --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	@echo ""
	@echo "  Application prête : http://localhost:8081"

start: ## Démarre les conteneurs
	docker compose up -d

stop: ## Arrête les conteneurs (les données sont conservées)
	docker compose stop

bash: ## Ouvre un terminal dans le conteneur PHP
	docker compose exec app bash

cc: ## Vide le cache Symfony
	$(CONSOLE) cache:clear

migration: ## Crée une migration à partir des entités modifiées
	$(CONSOLE) make:migration

migrate: ## Applique les migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

fixtures: ## Recharge les données de démonstration (efface la base !)
	$(CONSOLE) doctrine:fixtures:load --no-interaction

reset-db: ## Supprime et recrée la base, puis recharge la démo
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction

tests: ## Prépare la base de test puis lance PHPUnit
	$(CONSOLE) --env=test doctrine:database:create --if-not-exists
	$(CONSOLE) --env=test doctrine:migrations:migrate --no-interaction
	$(CONSOLE) --env=test doctrine:fixtures:load --no-interaction
	docker compose exec app php bin/phpunit

lint: ## Vérifie Twig, YAML, le conteneur de services et le mapping Doctrine
	$(CONSOLE) lint:twig templates
	$(CONSOLE) lint:yaml config
	$(CONSOLE) lint:container
	$(CONSOLE) doctrine:schema:validate
