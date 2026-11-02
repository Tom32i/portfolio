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
build: build-assets stats build-content optimize

build-assets:
	npx encore production

build-content: export APP_ENV = prod
build-content:
	symfony console cache:clear
	bin/console stenope:build

optimize:
	npx optimage-cli "build/**/*.@(png|jpg|jpeg|gif|webp)"

## Fetch GitHub stars and npm/Packagist downloads into content/tool/*.yaml (a failed fetch keeps the current value)
stats:
	bin/console app:refresh-tool-stats

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
	npx eslint assets/js --fix

##########
# Deploy #
##########

deploy@staging: build
	rsync -arzv --mkpath --delete-after build/* tom32i@deployer.vm:/home/tom32i/portfolio/

## Build and deploy to production
deploy@production: build
	rsync -arzv --mkpath --delete-after build/* tom32i@tom32i.fr:/home/tom32i/portfolio/

##########
# Covers #
##########

## Screenshot the cover pages (needs `make start`): public/cover.png (link preview) and public/linkedin.png (banner)
cover:
	npx encore dev
	npx -y playwright-core screenshot --channel chrome --viewport-size=1200,630 --wait-for-selector=".hero-scene:not(.is-loading)" --wait-for-timeout=500 http://127.0.0.1:8000/cover/ public/cover.png
	npx -y playwright-core screenshot --channel chrome --viewport-size=1584,396 --wait-for-selector=".hero-scene:not(.is-loading)" --wait-for-timeout=500 "http://127.0.0.1:8000/cover/?version=linkedin" public/linkedin.png

######
# CV #
######

## Print the resume page /cv/ (needs `make start`) to public/CV_Thomas_Jarrand.pdf
cv:
	npx encore dev
	"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless --disable-gpu --no-pdf-header-footer --virtual-time-budget=5000 --print-to-pdf=public/CV_Thomas_Jarrand.pdf http://127.0.0.1:8000/cv/
