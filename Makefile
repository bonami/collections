-include local/Makefile

all: deps test

deps:
	docker run -it --rm -v ${PWD}:/app -w /app composer update --prefer-dist --verbose --no-interaction --optimize-autoloader

test:
	docker run -it --rm -v ${PWD}:/app -w /app composer run-script test

coding-standards:
	docker run -it --rm -v ${PWD}:/app -w /app composer run-script phpcs

coding-standards-fix:
	docker run -it --rm -v ${PWD}:/app -w /app composer run-script phpcbf
