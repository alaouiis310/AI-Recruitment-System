# ---------------------------------------------------------------------------
# AI Recruitment System — raccourcis de commandes
# Usage : make <cible>   (ex. make up, make migrate)
# ---------------------------------------------------------------------------

.PHONY: help build up down restart logs shell migrate fresh test clean

help:            ## Affiche la liste des commandes disponibles
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

build:           ## Construit les images Docker
	docker compose build

up:              ## Démarre tous les conteneurs en arrière-plan
	docker compose up -d

down:            ## Arrête et supprime les conteneurs
	docker compose down

restart:         ## Redémarre les conteneurs
	docker compose restart

logs:            ## Affiche les logs en temps réel
	docker compose logs -f

shell:           ## Ouvre un terminal dans le conteneur applicatif
	docker compose exec app bash

migrate:         ## Exécute les migrations
	docker compose exec app php artisan migrate

fresh:           ## Réinitialise la base et rejoue les seeders
	docker compose exec app php artisan migrate:fresh --seed

test:            ## Lance la suite de tests
	docker compose exec app php artisan test

clean:           ## Supprime conteneurs, volumes et images du projet
	docker compose down -v --rmi local