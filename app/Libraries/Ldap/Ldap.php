<?php

namespace App\Libraries\Ldap;


class Ldap
{
    public static function authenticate($username, $password)
    {
        $conn = ldap_connect(config('ninja.ldap_uri'));
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        $bindDn = sprintf(config('ninja.ldap_bind_dn'), $username);
        $success = @ldap_bind($conn, $bindDn, $password);
        ldap_close($conn);
        return $success;
    }

    public static function authenticateAndFind($username, $password)
    {
        $conn = ldap_connect(config('ninja.ldap_uri'));
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        $bindDn = sprintf(config('ninja.ldap_bind_dn'), $username);
        $success = @ldap_bind($conn, $bindDn, $password);
        if (!$success) {
            ldap_close($conn);
            return null;
        }

        $dn = config('ninja.ldap_user_search_dn');
        $filter=sprintf(config('ninja.ldap_user_search_filter'), $username);
        $fields = array("sn", "givenname", "mail");
        $result=ldap_search($conn, $dn, $filter, $fields);
        $results = ldap_get_entries($conn, $result);

        ldap_close($conn);

        $email = $results[0]["mail"][0] ?? '';
        nlog("email: ".print_r($email, true));
        $firstname = $results[0]["givenname"][0] ?? '';
        nlog("firstname: ".$firstname);
        $lastname = $results[0]["sn"][0] ?? '';
        nlog("lastname: ".$lastname);
        $random_password = base64_encode(random_bytes(20));

        $account = [
            'ldap_username' => $username,
            'email' => $email,
            'first_name' => $firstname,
            'last_name' => $lastname,
            'password' => $random_password
        ];
        return $account;
    }


}
