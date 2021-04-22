sqldbs  := calendars locks principals users
srcdir  := vendor/sabre/dav/examples/sql
srcsql  := $(sqldbs:%=$(srcdir)/sqlite.%.sql)
destsql := $(srcsql:$(srcdir)/%=sql/%)

all: vendor sqlitedb.sql

vendor: composer.lock composer.json
	composer install
	@touch $@

sqlitedb.sql: $(destsql)
	cat $^ > $@

$(destsql): sql/%: $(srcdir)/% | sql vendor
	sed -E $< \
	-e 's/(CREATE [A-Z]+)/\1 IF NOT EXISTS/' \
	-e '/INSERT/,/;$$/d' \
	> $@

sql:
	mkdir $@

clean:
	rm -rf sql
	rm -rf vendor

.PHONY: all clean
