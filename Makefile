.SILENT:
.PHONY: build

## Colors
COLOR_RESET   = \033[0m
COLOR_INFO    = \033[32m
COLOR_COMMENT = \033[33m

## Help
help:
	printf "${COLOR_COMMENT}Usage:${COLOR_RESET}\n"
	printf " make [target]\n\n"
	printf "${COLOR_COMMENT}Available targets:${COLOR_RESET}\n"
	awk '/^[a-zA-Z\-\_0-9\.@]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf " ${COLOR_INFO}%-16s${COLOR_RESET} %s\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)

## Install dependencies
install:
	composer install
	npm install

## Start dev server
start:
	bin/console server:run

## Watch and build assets
watch:
	./node_modules/.bin/encore dev --watch

## Build static site
build: build-assets build-content

build-assets:
	./node_modules/.bin/encore production

build-content:
	bin/console content:build

## Server static site
serve:
	php -S 0.0.0.0:8000 -t build