<?php

namespace Club1\WebdavServer;

use Sabre\DAV;

class File extends DAV\FSExt\File implements IGetPath
{
    public function getPath(): string
    {
        return $this->path;
    }
}
