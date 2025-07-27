<?php

namespace Club1\WebdavServer;

use Sabre\DAV;

/**
 * IGetPath interface.
 *
 * Implement this interface to allow plugins to acces the real path of a node
 * on the underlying filesystem.
 */
interface IGetPath extends DAV\INode
{
    /**
     * Returns the path of this node.
     */
    public function getPath(): string;
}
