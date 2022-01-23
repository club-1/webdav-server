<?php

use Sabre\CalDAV;
use Sabre\CardDAV;
use Sabre\DAV;
use Sabre\DAVACL;

require_once 'vendor/autoload.php';
require_once 'AclPlugin.php';
require_once 'config.php';
require_once 'dbstring.php';

/*************************** Setup ****************************/

// settings
date_default_timezone_set('Europe/Paris');

$pdo = new PDO($dbstring);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Backends
$authBackend = new DAV\Auth\Backend\Apache(); // Let apache manage the auth.
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
    // Empty files collection as it is served by fileserver.php
    new DAV\SimpleCollection('files'),
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
$server->addPlugin(new AclPlugin($anonymous));

// Support for html frontend
$server->addPlugin(new DAV\Browser\Plugin());

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
