<?php

use Sabre\DAV;

class PosixPropertiesPlugin extends DAV\ServerPlugin
{
    public function initialize(DAV\Server $server)
    {
        $server->on('propFind', [$this, 'propFind']);
    }
    public function propFind(DAV\PropFind $propFind, DAV\INode $node)
    {
        // Hide all the things!
        $propFind->set('{DAV:}ishidden', '1');
    }
}
