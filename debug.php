<?php

// Always set CORS authorisation headers
header("access-control-allow-origin: *");
header("access-control-allow-methods: GET,POST,PUT,DELETE,OPTIONS,PROPFIND,MOVE,COPY,MKCOL,LOCK,UNLOCK");
header("access-control-allow-headers: authorization,depth");

// Spoof auth
$_SERVER['AUTHENTICATE_UID'] = getenv('USER');
$_SERVER['REMOTE_USER'] = getenv('USER');

// Include server to debug
include getenv('SERVER');
