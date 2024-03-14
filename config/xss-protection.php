<?php

return [
    'blade_echo_tags' => [
        ['{!!', '!!}'],
        ['{{', '}}'],
        ['{{{', '}}}'],
    ],

    'middleware' => [
        'allow_file_uploads' => true,

        'allow_blade_echoes' => false,

        'completely_replace_malicious_input' => true,

        'terminate_request_on_malicious_input' => false,

        'dispatch_event_on_malicious_input' => false,
    ],

    // Additional configuration for the underlying voku/anti-xss package
    // See: https://github.com/GrahamCampbell/Laravel-Security/blob/11.1/config/security.php
    'anti_xss' => [
        'evil' => [
            'attributes' => null,
            'tags' => null,
        ],

        'replacement' => null,
    ],
];
