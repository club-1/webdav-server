# Database settings
dbuser  ?= webdav_user
dbname  ?= webdav

pguser  := postgres
sqldbs  := addressbooks calendars locks principals propertystorage
srcdir  := vendor/sabre/dav/examples/sql
srcsql  := $(sqldbs:%=$(srcdir)/pgsql.%.sql)
destsql := $(srcsql:$(srcdir)/%=sql/%)

all: vendor sql/pgsql.full.sql config.php

vendor: composer.lock composer.json
	composer install
	@touch $@

sql/pgsql.full.sql: $(destsql) | sql
	cat $^ > $@

$(destsql): sql/%: $(srcdir)/% | sql vendor
	sed -E $< \
	-e 's/(CREATE [A-Z ]+)/\1IF NOT EXISTS /' \
	-e '/INSERT/,/;$$/d' \
	-e 's/BYTEA/TEXT/' \
	> $@

sql:
	mkdir $@

config.php:
	cp config.sample.php $@

setupdb: sql/pgsql.full.sql
	sudo -u $(pguser) createuser --pwprompt $(dbuser)
	sudo -u $(pguser) createdb $(dbname) --owner $(dbuser) --encoding UTF8
	sudo -u $(pguser) psql --host=localhost --user $(dbuser) -f sql/pgsql.full.sql $(dbname)

clean:
	rm -rf sql
	rm -rf vendor

cleanall: clean
	rm -rf config.php

.PHONY: all setupdb clean cleanall
