# Database settings
dbuser  ?= webdav_user
dbname  ?= webdav

wwwuser := www-data
pguser  := postgres
cacadbs := addressbooks calendars locks principals
filedbs := locks propertystorage
cacasql := $(cacadbs:%=sql/pgsql.%.sql)
filesql := $(filedbs:%=sql/sqlite.%.sql)
srcdir  := vendor/sabre/dav/examples/sql

port    := 8081

all: sql/pgsql.full.sql sql/sqlite.full.sql config.php dbstring.php | vendor

vendor: composer.lock composer.json
	composer install
	@touch $@

sql/pgsql.full.sql: $(cacasql)
	cat $^ > $@

sql/sqlite.full.sql: $(filesql)
	cat $^ > $@

$(cacasql) $(filesql): sql/%: $(srcdir)/% | sql
	sed -E $< \
	-e 's/(CREATE [A-Z ]+)/\1IF NOT EXISTS /' \
	-e '/INSERT/,/;$$/d' \
	-e 's/BYTEA/TEXT/' \
	> $@

$(srcdir)/%.sql: | vendor;

sql:
	mkdir $@

config.php:
	cp config.sample.php $@

dbstring.php:
	cp dbstring.sample.php $@
	chmod 640 $@
	-chgrp $(wwwuser) $@

setupdb: sql/pgsql.full.sql
	sudo -u $(pguser) createuser --pwprompt $(dbuser)
	sudo -u $(pguser) createdb $(dbname) --owner $(dbuser) --encoding UTF8
	sudo -u $(pguser) psql --host=localhost --user $(dbuser) -f sql/pgsql.full.sql $(dbname)

mostlyclean:
	rm -rf sql
	rm -rf vendor

clean: mostlyclean
	@echo -n "Delete config files? [y/N] " && read ans && [ $${ans:-N} = y ]
	rm -rf config.php
	rm -rf dbstring.php

debugfileserver: export XDEBUG_MODE=debug
debugfileserver: config.php | vendor
	php -d xdebug.start_with_request=yes -S localhost:$(port) fileserver.php

.PHONY: all setupdb mostlyclean clean debugfileserver
