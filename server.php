<?php

use Sabre\CalDAV;
use Sabre\CardDAV;
use Sabre\DAV;
use Sabre\DAVACL;

require_once 'vendor/autoload.php';

/************************* Parameters *************************/

$host     = 'club1.fr';
$user     = $_SERVER['AUTHENTICATE_UID'];
$home     = "/home/$user";
$vardir   = "$home/var";
$sqlitedb = "$vardir/webdav.sqlite";
$dbsql    = 'sql/sqlite.full.sql';

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
// Init database
$pdo->exec(file_get_contents($dbsql));
$pdo->exec("INSERT OR IGNORE INTO principals (uri,email,displayname) VALUES ('principals/$user', '$user@$host','$user');");
$pdo->exec("INSERT OR IGNORE INTO principals (uri,email,displayname) VALUES ('principals/$user/calendar-proxy-read', null, null);");
$pdo->exec("INSERT OR IGNORE INTO principals (uri,email,displayname) VALUES ('principals/$user/calendar-proxy-write', null, null);");
$pdo->exec("INSERT OR IGNORE INTO users (username,digesta1) VALUES ('$user', '87fd274b7b6c01e48d7c2f965da8ddf7');");

// Backends
$authBackend = new DAV\Auth\Backend\Apache(); // Let apache manage the auth.
$lockBackend = new DAV\Locks\Backend\PDO($pdo);
$principalBackend = new DAVACL\PrincipalBackend\PDO($pdo);
$calendarBackend = new CalDAV\Backend\PDO($pdo);
$carddavBackend = new CardDAV\Backend\PDO($pdo);

// default entries
if (count($calendarBackend->getCalendarsForUser("principals/$user")) == 0) {
    $calendarBackend->createCalendar("principals/$user", 'default', []);
}
if (count($carddavBackend->getAddressBooksForUser("principals/$user")) == 0) {
    $carddavBackend->createAddressBook("principals/$user", 'default', []);
}

// Directory structure
$tree = [
    new CalDAV\Principal\Collection($principalBackend),
    new CalDAV\CalendarRoot($principalBackend, $calendarBackend),
    new CardDAV\AddressBookRoot($principalBackend, $carddavBackend),
    new DAV\FS\Directory($home, 'files'),
];

$server = new DAV\Server($tree);
$server->setBaseUri('/');

/********************** General Plugins ***********************/

// Auth plugin
$server->addPlugin(new DAV\Auth\Plugin($authBackend));

// WebDAV-Sync plugin
$server->addPlugin(new DAV\Sync\Plugin());

// Sharing
$server->addPlugin(new DAV\Sharing\Plugin());

// Access control list plugin
$server->addPlugin(new DAVACL\Plugin());

// Support for html frontend
$server->addPlugin(new DAV\Browser\Plugin());

/************************ File Plugins ************************/

// The lock manager is reponsible for making sure users don't overwrite
// each others changes.
$server->addPlugin(new DAV\Locks\Plugin($lockBackend));

// Automatically guess (some) contenttypes, based on extension
$server->addPlugin(new DAV\Browser\GuessContentType());

// Temporary file filter
$server->addPlugin(new DAV\TemporaryFileFilterPlugin($vardir));

/********************** Calendar Plugins **********************/

// CalDAV support
$server->addPlugin(new CalDAV\Plugin());

// Calendar subscription support
$server->addPlugin(new CalDAV\Subscriptions\Plugin());

// Calendar scheduling support
$server->addPlugin(new CalDAV\Schedule\Plugin());

// CalDAV Sharing support
$server->addPlugin(new CalDAV\SharingPlugin());

// Export
$server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());

/******************** Addressbook Plugins *********************/

// CardDAV support
$server->addPlugin(new \Sabre\CardDAV\Plugin());

// Export
$server->addPlugin(new \Sabre\CardDAV\VCFExportPlugin());

/************************ Start server ************************/

$server->start();
