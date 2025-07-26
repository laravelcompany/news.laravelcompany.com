#!/bin/sh -l

# Define variables
IMAGE_PROD=izdrail/news.laravelcompany.com:latest
DOCKERFILE=Dockerfile
DOCKER_COMPOSE_FILE=docker-compose.yaml
DOCKER_COMPOSE_FILE_PROD=docker-compose.yaml
CODE=""

build:
	docker image rm -f $(IMAGE_PROD) || true
	docker build \
		-t $(IMAGE_PROD) \
		--no-cache \
		--build-arg CACHEBUST=$$(date +%s) \
		-f $(DOCKERFILE) \
		.  # <-- Build Context Docker file is located at root

dev:
	docker-compose -f $(DOCKER_COMPOSE_FILE_PROD) up --remove-orphans
prod:
	docker-compose -f $(DOCKER_COMPOSE_FILE_PROD) up --remove-orphans

down:
	docker-compose -f $(DOCKER_COMPOSE_FILE) down

ssh:
	docker exec -it news.laravelcompany.com /bin/bash

php:
	docker exec -it news.laravelcompany.com /bin/bash -c "php" $(CODE)

watch:
	docker exec -it news.laravelcompany.com /bin/bash -c "npm run dev"

publish-prod:
	docker push $(IMAGE_PROD)

# Additional functionality
test:
	docker exec news.laravelcompany.com php artisan test

migrate:
	docker exec news.laravelcompany.com php artisan migrate --force

seed:
	docker exec news.laravelcompany.com php artisan db:seed --force

clean-queue:
	docker exec news.laravelcompany.com php artisan horizon:clear
lint:
	docker exec news.laravelcompany.com ./vendor/bin/phpcs --standard=PSR12 app/

fix-lint:
	docker exec news.laravelcompany.com ./vendor/bin/phpcbf --standard=PSR12 app/

prune:
	docker system prune -f --volumes

logs:
	docker logs -f news.laravelcompany.com

restart:
	docker-compose -f $(DOCKER_COMPOSE_FILE) down
	docker-compose -f $(DOCKER_COMPOSE_FILE) up --remove-orphans -d

# Cleanup target
clean:
	-docker-compose -f $(DOCKER_COMPOSE_FILE) down --rmi all --volumes --remove-orphans
	-docker system prune -f --volumes
