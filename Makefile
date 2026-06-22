# Prefer the Symfony CLI (auto-injects the Docker DB host/port). Fall back to
# plain PHP if it isn't installed.
HAS_SYMFONY := $(shell command -v symfony 2>/dev/null)

ifdef HAS_SYMFONY
CONSOLE = symfony console
else
CONSOLE = php bin/console
endif

.DEFAULT_GOAL := help
.PHONY: help install up down schema fixtures db setup serve stop cc reset

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

install: ## Install Composer dependencies
	composer install

up: ## Start PostgreSQL (and Mailpit) via Docker, wait until healthy
	docker compose up -d --wait

down: ## Stop the Docker services
	docker compose down

schema: ## Sync the database schema with the entities (no migrations)
	$(CONSOLE) doctrine:schema:update --force --complete

fixtures: ## Load the two-tenant demo data
	$(CONSOLE) doctrine:fixtures:load --no-interaction

db: schema fixtures ## Rebuild schema and reload fixtures

setup: install up db ## One-shot: deps + containers + schema + data
	@echo "Ready. Run 'make serve' and open /dashboard."

serve: ## Start the local web server
ifdef HAS_SYMFONY
	symfony server:start -d
else
	@echo "Symfony CLI not found - using PHP's built-in server (Ctrl+C to stop)."
	php -S 127.0.0.1:8000 -t public
endif

stop: ## Stop the local web server (Symfony CLI only)
ifdef HAS_SYMFONY
	symfony server:stop
else
	@echo "PHP built-in server: stop it with Ctrl+C in its terminal."
endif

cc: ## Clear the Symfony cache
	$(CONSOLE) cache:clear

reset: down up db ## Recreate containers and reload data from scratch
