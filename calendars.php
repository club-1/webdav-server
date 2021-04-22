<?php

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAVACL;

require_once 'base.php';


// settings
date_default_timezone_set('Europe/Paris');


// Database
$pdo = new PDO("sqlite:$vardir/calendars.sqlite");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Backends
$authBackend = new DAV\Auth\Backend\PDO($pdo);
$calendarBackend = new CalDAV\Backend\PDO($pdo);
$principalBackend = new DAVACL\PrincipalBackend\PDO($pdo);

// Directory structure
$tree = [
    new CalDAV\Principal\Collection($principalBackend),
    new CalDAV\CalendarRoot($principalBackend, $calendarBackend),
];

$server = new DAV\Server($tree);

$server->setBaseUri('/calendars');

// Server Plugins
$server->addPlugin(new DAV\Auth\Plugin($authBackend));

$server->addPlugin(new DAVACL\Plugin());

// CalDAV support
$server->addPlugin(new CalDAV\Plugin());

// Calendar subscription support
$server->addPlugin(new CalDAV\Subscriptions\Plugin());

// Calendar scheduling support
$server->addPlugin(new CalDAV\Schedule\Plugin());

// WebDAV-Sync plugin
$server->addPlugin(new DAV\Sync\Plugin());

// CalDAV Sharing support
$server->addPlugin(new DAV\Sharing\Plugin());
$server->addPlugin(new CalDAV\SharingPlugin());

// Support for html frontend
$browser = new DAV\Browser\Plugin();
$server->addPlugin($browser);

// And off we go!
$server->start();
