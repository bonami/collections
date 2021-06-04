-include local/Makefile

all: deps test

deps:
	docker run -it --rm -v ${PWD}:/app -w /app composer update --prefer-dist --verbose --no-interaction --optimize-autoloader

test:
	$(MAKE) phpunit
	$(MAKE) phpstan
	$(MAKE) fmt-check

phpstan:
	docker run -it --rm -v ${PWD}:/app -w /app composer run-script phpstan

phpunit:
	docker run -it --rm -v ${PWD}:/app -w /app composer run-script phpunit

fmt-check:
	docker run -it --rm -v ${PWD}:/app -w /app composer run-script phpcs

fmt:
	docker run -it --rm -v ${PWD}:/app -w /app composer run-script phpcbf
