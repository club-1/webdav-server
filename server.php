<?php

use Sabre\CalDAV;
use Sabre\CardDAV;
use Sabre\DAV;
use Sabre\DAVACL;

require_once 'vendor/autoload.php';
require_once 'PosixPropertiesPlugin.php';
require_once 'config.php';

/*************************** Setup ****************************/

if (!file_exists($home)) {
    throw new RuntimeException("Home does not exist: '$home'", 1);
}
if (!is_dir($home)) {
    throw new RuntimeException("Home exists but is not a directory: '$home'", 2);
}

// settings
date_default_timezone_set('Europe/Paris');

$pdo = new PDO($dbstring);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Backends
$authBackend = new DAV\Auth\Backend\Apache(); // Let apache manage the auth.
$lockBackend = new DAV\Locks\Backend\PDO($pdo);
$principalBackend = new DAVACL\PrincipalBackend\PDO($pdo);
$calendarBackend = new CalDAV\Backend\PDO($pdo);
$carddavBackend = new CardDAV\Backend\PDO($pdo);

// Default entries
$pdo->exec("INSERT INTO principals (uri,email,displayname) VALUES ('principals/$user', '$user@$host','$user') ON CONFLICT DO NOTHING;");
$pdo->exec("INSERT INTO principals (uri,email,displayname) VALUES ('principals/$user/calendar-proxy-read', null, null) ON CONFLICT DO NOTHING;");
$pdo->exec("INSERT INTO principals (uri,email,displayname) VALUES ('principals/$user/calendar-proxy-write', null, null) ON CONFLICT DO NOTHING;");
if (count($calendarBackend->getCalendarsForUser("principals/$user")) == 0) {
    $calendarBackend->createCalendar("principals/$user", 'default', ['{DAV:}displayname' => 'Default']);
}
if (count($carddavBackend->getAddressBooksForUser("principals/$user")) == 0) {
    $carddavBackend->createAddressBook("principals/$user", 'default', ['{DAV:}displayname' => 'Default']);
}

// Directory structure
$tree = [
    new CalDAV\Principal\Collection($principalBackend),
    new CalDAV\CalendarRoot($principalBackend, $calendarBackend),
    new CardDAV\AddressBookRoot($principalBackend, $carddavBackend),
    new DAV\SimpleCollection('files', [
        new DAV\FS\Directory($home),
    ]),
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
$mimePlugin = new DAV\Browser\GuessContentType();
$mimePlugin->extensionMap["mp4"] = "video/mp4";
$server->addPlugin($mimePlugin);

// Add Posix properties to files
$server->addPlugin(new PosixPropertiesPlugin($home, "files/$user"));
// Temporary file filter
//$server->addPlugin(new DAV\TemporaryFileFilterPlugin($vardir));

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
