.PHONY: up down logs reset-db shell-be

DOCKER_COMPOSE ?= docker compose

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

logs:
	$(DOCKER_COMPOSE) logs -f

reset-db:
	$(DOCKER_COMPOSE) down -v
	$(DOCKER_COMPOSE) up -d postgres

shell-be:
	cd backend && $${SHELL:-sh}
