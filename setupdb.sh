#!/bin/bash

USER=webdav
DBNAME=webdav

sudo -u postgres bash
createuser --pwprompt $USER
createdb $DBNAME --owner $USER
psql -f sql/pgsql.full.sql $DBNAME
