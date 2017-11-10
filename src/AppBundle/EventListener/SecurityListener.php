<?php

namespace AppBundle\EventListener;

use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SecurityListener
{
    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var Ldap
     */
    private $ldap;

    /**
     * @var string
     */
    private $ldapBaseDn;

    /**
     * @var string
     */
    private $ldapSearchDn;

    /**
     * @var string
     */
    private $ldapSearchPassword;

    /**
     * @var string
     */
    private $ldapOrganisation;

    public function __construct(TokenStorage $tokenStorage, Ldap $ldap, $ldapSearchDn, $ldapSearchPassword, $ldapBaseDn, $ldapOrganisation)
    {
        $this->tokenStorage = $tokenStorage;
        $this->ldap = $ldap;
        $this->ldapSearchDn = $ldapSearchDn;
        $this->ldapSearchPassword = $ldapSearchPassword;
        $this->ldapBaseDn = $ldapBaseDn;
        $this->ldapOrganisation = $ldapOrganisation;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        // On initialise la connection au LDAP
        $this->ldap->bind($this->ldapSearchDn, $this->ldapSearchPassword);

        $token = $event->getAuthenticationToken();
        $user = $token->getUser();

        $query = $this->ldap->query($this->ldapBaseDn, sprintf('(sAMAccountName=%s)', $user->getUsername()) );
        $result = $query->execute();
        $entry = $result->offsetGet('0');

        // On vérifie que l'utilisateur existe bien dans le LDAP
        if (null === $entry) {
            throw new AuthenticationException(sprintf('Impossible de retrouver l\'utilisateur : %s dans le LDAP', $user->getUsername()));
        }

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

        // Set new token with LDAP ROLE
        $token  = new UsernamePasswordToken($user->getUsername(), $user->getPassword(), 'main', $roles);
        $this->tokenStorage->setToken($token);

    }
}