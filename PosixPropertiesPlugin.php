<?php

use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class PosixPropertiesPlugin extends ServerPlugin
{
    const NS_POSIXPROPS = 'http://club1.fr/posixprops/';

    public function initialize(Server $server)
    {
        $server->on('propFind', [$this, 'propFind']);
        $server->xml->namespaceMap[self::NS_POSIXPROPS] = 'p';
    }
    public function propFind(PropFind $propFind, INode $node)
    {
        $ns = '{' . self::NS_POSIXPROPS . '}';
        // Hide all the things!
        $propFind->set($ns . 'links', '1');
        $propFind->set($ns . 'test', '1');
    }
}
