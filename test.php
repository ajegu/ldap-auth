<?php
/**
 * Created by PhpStorm.
 * User: prestasic10
 * Date: 08/11/2017
 * Time: 14:46
 */

define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);


echo "LDAP query test" . chr(10);
echo "Connecting ..." . chr(10);

$ldapConnect = ldap_connect('vm-parkes.domaine.local', 389);

if ($ldapConnect) {
    echo "Connecting OK" . chr(10);
}

ldap_set_option($ldapConnect, LDAP_OPT_REFERRALS, 0);
ldap_set_option($ldapConnect, LDAP_OPT_PROTOCOL_VERSION, 3);

echo "Binding ..." . chr(10);
$bind = ldap_bind($ldapConnect, 'AnonymeAD', 'AnonymeAD');

if (!$bind) {
    if (ldap_get_option($ldapConnect, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
        echo "Error Binding to LDAP: $extended_error"  . chr(10);
    } else {
        echo "Error Binding to LDAP: No additional information is available."  . chr(10);
    }
    exit;
} else {
    echo "Connexion LDAP réussi ..." . chr(10);
}
echo "Search ..." . chr(10);
$filter = '(sAMAccountName=prestasic10)';
$sr = ldap_search($ldapConnect, 'dc=domaine,dc=local', $filter);
$info = ldap_get_entries($ldapConnect, $sr);

print_r($info);