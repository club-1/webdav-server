<?php

use Sabre\DAV;

require_once 'vendor/autoload.php';
require_once 'PosixPropertiesPlugin.php';
require_once 'config.php';

/*************************** Setup ****************************/

// settings
if (!file_exists($home)) {
    throw new RuntimeException("Home does not exist: '$home'", 1);
}
if (!is_dir($home)) {
    throw new RuntimeException("Home exists but is not a directory: '$home'", 2);
}
date_default_timezone_set('Europe/Paris');
$tmpDir = "$home/var/tmp";
if (!file_exists($tmpDir)) {
    mkdir($tmpDir, 0775, true);
}

$pdo = new PDO("sqlite:$tmpDir/davlocks.sqlite");

// Backends
$authBackend = new DAV\Auth\Backend\Apache(); // Let apache manage the auth.
$lockBackend = new DAV\Locks\Backend\PDO($pdo);

$server = new DAV\Server([
    new DAV\SimpleCollection('files', [
        new DAV\FS\Directory($home),
    ]),
]);
$server->setBaseUri('/');

/********************** General Plugins ***********************/

// Auth plugin
$server->addPlugin(new DAV\Auth\Plugin($authBackend));

// The lock manager is reponsible for making sure users don't overwrite
// each others changes.
$server->addPlugin(new DAV\Locks\Plugin($lockBackend));

// WebDAV-Sync plugin
$server->addPlugin(new DAV\Sync\Plugin());

// Support for html frontend
$server->addPlugin(new DAV\Browser\Plugin());

/************************ File Plugins ************************/

// Automatically guess (some) contenttypes, based on extension
$mimePlugin = new DAV\Browser\GuessContentType();
$mimePlugin->extensionMap["mp4"] = "video/mp4";
$server->addPlugin($mimePlugin);

// Add Posix properties to files
$server->addPlugin(new PosixPropertiesPlugin($home, "files/$user"));

// Temporary file filter to store garbage OS files elsewhere
$server->addPlugin(new DAV\TemporaryFileFilterPlugin($tmpDir));

/************************ Start server ************************/

$server->start();
