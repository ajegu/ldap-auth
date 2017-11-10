<?php


namespace AppBundle\Security;

use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Role\Role;
use \Symfony\Component\Security\Core\User\LdapUserProvider as SymfonyLdapUserProvider;
use Symfony\Component\Security\Core\User\User;

class LdapUserProvider extends SymfonyLdapUserProvider
{
    private $ldapOrganisation;

    public function __construct(LdapInterface $ldap, $baseDn, $searchDn = null, $searchPassword = null, array $defaultRoles = array(), $uidKey = 'sAMAccountName', $filter = '({uid_key}={username})', $passwordAttribute = null, $ldapOrganisation)
    {
        $this->ldapOrganisation = $ldapOrganisation;
        parent::__construct($ldap, $baseDn, $searchDn, $searchPassword, $defaultRoles, $uidKey, $filter, $passwordAttribute);
    }

    protected function loadUser($username, Entry $entry)
    {
        // On recherche les groupes AD associés à l'organisation voulue
        $roles = [];
        foreach ($entry->getAttribute('memberOf') as $memberOf) {
            $attributes = explode(',', $memberOf);
            $groupDn = null;
            foreach ($attributes as $attribute) {
                if (strstr($attribute, 'CN=') !== false) {
                    $groupDn = trim(substr($attribute, strlen('CN=')));
                }

                if ($attribute === 'OU=' . $this->ldapOrganisation) {
                    if ($groupDn !== null) {
                        $role = new Role(sprintf('ROLE_%s', $groupDn));
                        $roles[] = $role;
                    }
                }

            }
        }

        return new User($username, '', $roles);
    }
}