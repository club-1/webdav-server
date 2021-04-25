<?php

/** @var string Only used for mails addresses for now. */
$host = 'club1.fr';
/** @var string The currently logged in user. */
$user = $_SERVER['AUTHENTICATE_UID'];
/** @var string Current user's home directory. */
$home = "/home/$user";
/** @var string PostgreSQL DSN. */
$dbstring = "pgsql:host=localhost;port=5432;dbname=webdav;user=webdav;password=mypass";
