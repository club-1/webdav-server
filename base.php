<?php

use Sabre\DAV;

require_once 'vendor/autoload.php';

$user = $_SERVER['AUTHENTICATE_UID'];
$home = "/home/$user";
$vardir = "$home/var/";

// Let apache manage the auth.
$authBackend = new DAV\Auth\Backend\Apache();
$authPlugin = new DAV\Auth\Plugin($authBackend);
