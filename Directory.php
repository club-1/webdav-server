<?php

namespace Club1\WebdavServer;

use Sabre\DAV;

class Directory extends DAV\FSExt\Directory implements IGetPath
{
    /**
     * @inheritdoc
     *
     * Override the parent implementation to return a File class from the
     * current namespace instead of an FSExt one.
     */
    public function getChild($name)
    {
        $node = parent::getChild($name);
        if ($node instanceof DAV\FSExt\File) {
            return new File($node->path);
        }
        return $node;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
