# Club1 webdav server

## Setup

### Prepare

1. Debian dependencies: `sudo apt install apache2 php-fpm composer`
2. Build all: `make`

### Apache vhost

1. copy and edit vhost file: `sudo cp apache.conf /etc/apache2/sites-available/vhost.conf`
2. enable ldap modules: `sudo a2enmod ldap authnz_ldap`
3. enable vhost: `sudo a2ensite vhost.conf`
3. reload apache: `sudo systemctl reload apache2`

### PHP FPM pools

1. for each user, create a pool based on `fpm-pool.conf`
2. reload fpm: `sudo systemctl reload php7.4-fpm`
