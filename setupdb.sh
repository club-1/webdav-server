#!/bin/bash

USER=webdav
DBNAME=webdav

sudo -u postgres createuser --pwprompt $USER
sudo -u postgres createdb $DBNAME --owner $USER --encoding UTF8
sudo -u postgres psql --host=localhost --user $USER -f sql/pgsql.full.sql $DBNAME
