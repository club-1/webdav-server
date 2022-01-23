<?php

use Sabre\DAVACL\Plugin;

class AclPlugin extends Plugin {

    protected string $anonymous;

    public function __construct(string $anonymous = 'anonymous') {
        $this->anonymous = "principals/$anonymous";
    }


    /**
     * Returns a list of privileges the current user has
     * on a particular node.
     *
     * If the current user is anonymous, keep only read permissions.
     *
     * Either a uri or a DAV\INode may be passed.
     *
     * null will be returned if the node doesn't support ACLs.
     *
     * @param string|DAV\INode $node
     *
     * @return array
     */
    public function getCurrentUserPrivilegeSet($node)
    {
        $privileges = parent::getCurrentUserPrivilegeSet($node);
        $curr = $this->getCurrentUserPrincipal();
        if ($curr == $this->anonymous) {
            // error_log(var_export($privileges, true));
            return array_intersect($privileges, [
                '{DAV:}read',
                '{DAV:}read-acl',
                '{DAV:}read-current-user-privilege-set',
            ]);
        }
        return $privileges;
    }
}
