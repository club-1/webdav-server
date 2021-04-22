sqldbs  := calendars locks principals users
srcdir  := vendor/sabre/dav/examples/sql
srcsql  := $(sqldbs:%=$(srcdir)/sqlite.%.sql)
destsql := $(srcsql:$(srcdir)/%=sql/%)

all: vendor $(destsql)

vendor: composer.lock composer.json
	composer install
	touch $@

$(destsql): sql/%: $(srcdir)/% | sql vendor Makefile
	sed -E $< \
		-e 's/(CREATE [A-Z]+)/\1 IF NOT EXISTS/' \
		-e 's/(INSERT)/\1 OR IGNORE/' \
		> $@

sql:
	mkdir $@

clean:
	rm -rf sql
	rm -rf vendor

.PHONY: all clean
