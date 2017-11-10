<?php


namespace AppBundle\DependencyInjection\Compiler;


use AppBundle\Security\LdapUserProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LdapCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('security.user.provider.ldap');
        $definition->setClass(LdapUserProvider::class);
        $definition->addArgument($container->getParameter('ldap_organisation'));
    }
}