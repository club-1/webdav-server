<?php

namespace Club1\WebdavServer;

use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class PosixPropertiesPlugin extends ServerPlugin
{
    const NS_POSIXPROPS = 'http://club1.fr/posixprops/';

    /** @var array<int,string> $ids Map associating user ids with their username*/
    protected array $ids;

    public function __construct(array $ids = [])
    {
        $this->ids = $ids;
    }

    protected function id2name(int $id): string
    {
        if (isset($this->ids[$id])) {
            return $this->ids[$id];
        }
        if (!function_exists(('posix_getpwuid'))) {
            throw new \RuntimeException("Missing PHP Posix extension");
        }
        $user = posix_getpwuid($id);
        if (!$user) {
            return "";
        }
        $this->ids[$id] = $user['name'];
        return $user['name'];
    }

    public function getName(): string
    {
        return 'posix';
    }

    public function initialize(Server $server): void
    {
        $server->on('propFind', [$this, 'propFind']);
        $server->xml->namespaceMap[self::NS_POSIXPROPS] = 'p';
    }

    public function propFind(PropFind $propFind, INode $node): void
    {
        if (!($node instanceof IGetPath)) {
            return;
        }
        $path = $node->getPath();
        $stat = stat($path);
        if (!$stat) {
            return;
        }
        $ns = '{' . self::NS_POSIXPROPS . '}';
        $propFind->set($ns . 'mode', $stat['mode']);
        $propFind->set($ns . 'user', $this->id2name($stat['uid']));
        $propFind->set($ns . 'group', $this->id2name($stat['uid']));
        $propFind->set($ns . 'atime', $stat['atime']);
        $propFind->set($ns . 'ctime', $stat['ctime']);
        if (is_link($path)) {
            $propFind->set($ns . 'link', readlink($path));
        }
    }
}
