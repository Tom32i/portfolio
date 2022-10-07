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
	# Composer
	composer install --verbose
	# Npm install
	npm install

## Start dev server
start:
	symfony server:start --no-tls

## Watch and build assets
watch:
	npx encore dev --watch

## Build static site
build: build-assets build-content optimize

build-assets:
	npx encore production

build-content: export APP_ENV = prod
build-content:
	symfony console cache:clear
	bin/console stenope:build

optimize:
	npx optimage-cli "build/**/*.@(png|jpg|jpeg|gif|webp)"

## Server static site
serve:
	php -S 0.0.0.0:8001 -t build

############
# Security #
############

## Run security checks
security:
	symfony check:security

security@test: export APP_ENV = test
security@test: security

########
# Lint #
########

## Run lint suite:
lint: lint-phpcsfixer lint-phpstan lint-twig lint-yaml lint-eslint

lint-phpcsfixer:
	vendor/bin/php-cs-fixer fix

lint-phpstan:
	vendor/bin/phpstan analyse src

lint-twig:
	bin/console lint:twig templates

lint-yaml:
	bin/console lint:yaml translations config

lint-eslint:
	npx eslint assets/js --ext .js,.json --fix

##########
# Deploy #
##########

deploy@staging: build
	rsync -arzv --delete build/* tom32i@deployer.vm:/home/tom32i/portfolio/

## Build and deploy to production
deploy@production: build
	rsync -arzv --delete build/* tom32i@tom32i.fr:/home/tom32i/portfolio/
