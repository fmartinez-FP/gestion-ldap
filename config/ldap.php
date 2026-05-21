<?php

return [
    'default' => env('LDAP_CONNECTION', 'default'),

    'logging' => [
        'enabled'  => env('LDAP_LOGGING', false),
        'channel'  => env('LOG_CHANNEL', 'stack'),
        'level'    => 'info',
    ],

    'cache' => [
        'enabled'  => false,
        'driver'   => 'file',
        'lifetime' => 600,
    ],

    'connections' => [
        'default' => [
            'hosts'            => [env('LDAP_HOST', '127.0.0.1')],
            'username'         => env('LDAP_USERNAME'),
            'password'         => env('LDAP_PASSWORD'),
            'port'             => env('LDAP_PORT', 389),
            'base_dn'          => env('LDAP_BASE_DN', env('LDAP_BASE_DN', 'dc=example,dc=es')),
            'timeout'          => env('LDAP_TIMEOUT', 5),
            'use_tls'          => false,
            'use_starttls'     => false,
            'use_sasl'         => false,
            'version'          => 3,
            'follow_referrals' => false,
        ],
    ],


    // TLS para comunicación LDAP (activar en multi-servidor)
    'tls'    => env('LDAP_TLS', false),
    'tls_ca' => env('LDAP_TLS_CA', '/etc/ldap/tls/ca.crt'),

];
