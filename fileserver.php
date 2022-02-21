<?php

use Sabre\DAV;

require 'vendor/autoload.php';
require 'config.php';

ob_start();
require 'sql/sqlite.full.sql';
$sql = ob_get_clean();
ob_end_clean();

/*************************** Setup ****************************/

// settings
if (!file_exists($home)) {
    throw new RuntimeException("Home does not exist: '$home'", 1);
}
if (!is_dir($home)) {
    throw new RuntimeException("Home exists but is not a directory: '$home'", 2);
}
date_default_timezone_set('Europe/Paris');
$tmpDir = "$home/.local/share/dav";
if (!file_exists($tmpDir)) {
    mkdir($tmpDir, 0775, true);
}

$pdo = new PDO("sqlite:$tmpDir/dav.sqlite");
$pdo->exec($sql);

// Backends
$authBackend = new DAV\Auth\Backend\Apache(); // Let apache manage the auth.
$lockBackend = new DAV\Locks\Backend\PDO($pdo);
$storageBackend = new DAV\PropertyStorage\Backend\PDO($pdo);

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

// Custom properties storage plugin
$server->addPlugin(new DAV\PropertyStorage\Plugin($storageBackend));

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
