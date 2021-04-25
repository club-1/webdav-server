# Unix webdav server

_Part of the [unix-cloud](https://github.com/club-1/unix-cloud) project._

A fully featured webdav server integrated with unix users and mainly based on [Sabre/DAV](https://github.com/sabre-io/dav).

It is also dependant on [PHP-FPM](https://www.php.net/manual/en/install.fpm.php) which allows to easily have one process per user with the correct ownership.

## Setup

_Using Apache and LDAP auth for now._

### Prepare

1. Debian dependencies: `sudo apt install apache2 php-fpm php-pgsql composer`
2. Build all: `make`

### PosgreSQL database

Run `sudo make setupdb`

### Apache vhost

1. copy and edit vhost file: `sudo cp apache.conf /etc/apache2/sites-available/vhost.conf`
2. enable ldap modules: `sudo a2enmod ldap authnz_ldap`
3. enable vhost: `sudo a2ensite vhost.conf`
3. reload apache: `sudo systemctl reload apache2`

### PHP FPM pools

1. for each user, create a pool based on `fpm-pool.conf`
2. reload fpm: `sudo systemctl reload php7.4-fpm`
