SHELL := /bin/bash

.PHONY: help up down stop restart build shell artisan migrate fresh test pint npm-dev npm-build

help:
	@echo "Available commands:"
	@echo "  make up         - Start Laravel Sail containers"
	@echo "  make down       - Stop and remove Sail containers"
	@echo "  make stop       - Stop Sail containers"
	@echo "  make restart    - Restart Sail containers"
	@echo "  make build      - Build Sail containers"
	@echo "  make shell      - Open shell in the app container"
	@echo "  make artisan c='about' - Run artisan command"
	@echo "  make migrate    - Run database migrations"
	@echo "  make fresh      - Fresh migrate with seed"
	@echo "  make test       - Run test suite"
	@echo "  make pint       - Run Laravel Pint"
	@echo "  make npm-dev    - Start Vite dev server via Sail"
	@echo "  make npm-build  - Build frontend assets via Sail"
	@echo "  make fetch-rates - Fetch latest currency rates from freecurrencyapi.com"

up:
	./vendor/bin/sail up -d

down:
	./vendor/bin/sail down

stop:
	./vendor/bin/sail stop

restart: down up

build:
	./vendor/bin/sail build

shell:
	./vendor/bin/sail shell

artisan:
	./vendor/bin/sail artisan $(c)

migrate:
	./vendor/bin/sail artisan migrate

fresh:
	./vendor/bin/sail artisan migrate:fresh --seed

test:
	./vendor/bin/sail test

pint:
	./vendor/bin/sail artisan pint

npm-dev:
	./vendor/bin/sail npm run dev

npm-build:
	./vendor/bin/sail npm run build

fetch-rates:
	./vendor/bin/sail artisan app:fetch-currency-rates
