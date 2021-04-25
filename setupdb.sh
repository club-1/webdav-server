#!/bin/bash

USER=webdav
DBNAME=webdav

sudo -u postgres createuser --pwprompt $USER
sudo -u postgres createdb $DBNAME --owner $USER
sudo -u postgres psql --host=localhost -U $USER -f sql/pgsql.full.sql $DBNAME
