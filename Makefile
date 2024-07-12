# Makefile
# I created this makefile to streamline the usage of a few common tasks

ccyellow = $(shell echo "\033[33m")
ccend = $(shell tput op)
cvport = 8008
docsport = 8009

help:
	@echo "$(ccyellow)------------------------------------------$(ccend)"
	@echo "$(ccyellow)Qi Db Project Makefile$(ccend)"
	@echo "$(ccyellow)------------------------------------------$(ccend)"
	@echo "Usage:"
	@echo "  make deps : install php dependencies"
	@echo "  make docs : generate phpdoc docs"
	@echo "  make serve-docs: serve the docs on a local server"
	@echo

.PHONY: help deps test docs serve-docs
deps:
	@echo "$(ccyellow)> Installing dependencies...$(ccend)"
	composer install
	@echo

test:
	@echo "$(ccyellow)> Running tests...$(ccend)"
	mkdir -p .test-results
	cd tests; ../vendor/bin/phpunit --bootstrap=bootstrap.php --colors --coverage-clover=../.test-results/coverage.xml --coverage-html=../.test-results/coverage/
	@echo

serve-coverage:
	@echo "$(ccyellow)> Serving coverage report on local server, port $(cvport)...$(ccend)"
	php -S 0.0.0.0:$(cvport) -t .test-results/coverage
	@echo

docs: phpdoc
	@echo "$(ccyellow)> Generating phpdoc docs...$(ccend)"
	./phpdoc
	@echo

serve-docs:
	@echo "$(ccyellow)> Serving docs on local server, port $(docsport)...$(ccend)"
	php -S 0.0.0.0:$(docsport) -t docs
	@echo

phpdoc:
	@echo "$(ccyellow)> Installing phpdoc...$(ccend)"
	wget --quiet https://phpdoc.org/phpDocumentor.phar -O phpdoc
	chmod a+x phpdoc
	@echo
