<?php

use Sabre\CalDAV;
use Sabre\DAV;
use Sabre\DAVACL;

require_once 'vendor/autoload.php';

/************************* Parameters *************************/

$user = $_SERVER['AUTHENTICATE_UID'];
$home = "/home/$user";
$vardir = "$home/var";
$sqlitedb = "$vardir/webdav.sqlite";
$sqlfiles = 'sql/*';

/*************************** Setup ****************************/

if (!file_exists($home)) {
    throw new RuntimeException("Home does not exist: '$home'", 1);
}
if (!is_dir($home)) {
    throw new RuntimeException("Home exists but is not a directory: '$home'", 2);
}
if (!file_exists($vardir)) {
    mkdir($vardir);
} elseif (!is_dir($vardir)) {
    throw new RuntimeException("Var exists but is not a directory: '$vardir'", 3);
}

// settings
date_default_timezone_set('Europe/Paris');

$pdo = new PDO("sqlite:$sqlitedb");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
foreach (glob($sqlfiles) as $file) {
    $pdo->exec(file_get_contents($file));
}

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

/********************** General Plugins ***********************/

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
$lockBackend = new DAV\Locks\Backend\PDO($pdo);
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
