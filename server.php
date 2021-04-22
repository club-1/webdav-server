<?php

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAVACL;

require_once 'vendor/autoload.php';

$user = $_SERVER['AUTHENTICATE_UID'];
$home = "/home/$user";
$vardir = "$home/var/";

// settings
date_default_timezone_set('Europe/Paris');

// Database
$pdo = new PDO("sqlite:$vardir/calendars.sqlite");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Backends
$calendarBackend = new CalDAV\Backend\PDO($pdo);
$principalBackend = new DAVACL\PrincipalBackend\PDO($pdo);

// Directory structure
$tree = [
    new CalDAV\Principal\Collection($principalBackend),
    new CalDAV\CalendarRoot($principalBackend, $calendarBackend),
	$root = new DAV\FS\Directory($home, 'files'),
];

$server = new DAV\Server($tree);
$server->setBaseUri('/');

/************************ General Plugins ************************/

// Let apache manage the auth.
$authBackend = new DAV\Auth\Backend\Apache();
$authPlugin = new DAV\Auth\Plugin($authBackend);
$server->addPlugin($authPlugin);

// WebDAV-Sync plugin
$server->addPlugin(new DAV\Sync\Plugin());

// Access control list plugin
$server->addPlugin(new DAVACL\Plugin());

// Support for html frontend
$browser = new DAV\Browser\Plugin();
$server->addPlugin($browser);

/************************ File Plugins ************************/

// The lock manager is reponsible for making sure users don't overwrite
// each others changes.
$lockBackend = new DAV\Locks\Backend\File("$vardir/locks");
$lockPlugin = new DAV\Locks\Plugin($lockBackend);
$server->addPlugin($lockPlugin);

// Automatically guess (some) contenttypes, based on extension
$server->addPlugin(new DAV\Browser\GuessContentType());

// Temporary file filter
$tempFF = new DAV\TemporaryFileFilterPlugin($vardir);
$server->addPlugin($tempFF);

/********************** Calendar Plugins **********************/

// CalDAV support
$server->addPlugin(new CalDAV\Plugin());

// Calendar subscription support
$server->addPlugin(new CalDAV\Subscriptions\Plugin());

// Calendar scheduling support
$server->addPlugin(new CalDAV\Schedule\Plugin());

// CalDAV Sharing support
$server->addPlugin(new DAV\Sharing\Plugin());
$server->addPlugin(new CalDAV\SharingPlugin());

/************************ Start server ************************/

$server->start();
