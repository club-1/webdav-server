<?php

use Sabre\DAV;

require_once 'base.php';

// Directory structure
$root = new DAV\FS\Directory($home);

$server = new DAV\Server($root);
$server->setBaseUri('/files');

/************************ General Plugins ************************/
$server->addPlugin($authPlugin);

/* WebDAV-Sync plugin */
$server->addPlugin(new DAV\Sync\Plugin());

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

/************************ Start server ************************/

$server->start();
