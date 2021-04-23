sqldbs  := addressbooks calendars locks principals propertystorage
srcdir  := vendor/sabre/dav/examples/sql
srcsql  := $(sqldbs:%=$(srcdir)/sqlite.%.sql)
destsql := $(srcsql:$(srcdir)/%=sql/%)

all: vendor sql/sqlite.full.sql

vendor: composer.lock composer.json
	composer install
	@touch $@

sql/sqlite.full.sql: $(destsql) | sql
	cat $^ > $@

$(destsql): sql/%: $(srcdir)/% | sql vendor
	sed -E $< \
	-e 's/(CREATE [A-Z ]+)/\1IF NOT EXISTS /' \
	-e '/INSERT/,/;$$/d' \
	> $@

sql:
	mkdir $@

clean:
	rm -rf sql

cleanall: clean
	rm -rf vendor

.PHONY: all clean cleanall
