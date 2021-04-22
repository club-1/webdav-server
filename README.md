# Club1 webdav server

## Setup

### Dependencies

1. Debian: `sudo apt install apache2 php-fpm composer`
2. PHP: `composer install`

### Apache vhost

1. copy and edit vhost file: `sudo cp apache.conf /etc/apache2/sites-available/vhost.conf`
2. enable ldap modules: `sudo a2enmod ldap authnz_ldap`
3. enable vhost: `sudo a2ensite vhost.conf`
3. reload apache: `sudo systemctl reload apache2`
