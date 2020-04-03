-include local/Makefile

all: deps test

deps:
	docker run -it --rm -v ${PWD}:/app -w /app composer update --prefer-dist --verbose --no-interaction --optimize-autoloader

test:
	docker run -it --rm -v ${PWD}:/app -w /app composer run-script test

fmt-check:
	docker run -it --rm -v ${PWD}:/app -w /app composer run-script phpcs

fmt:
	docker run -it --rm -v ${PWD}:/app -w /app composer run-script phpcbf
