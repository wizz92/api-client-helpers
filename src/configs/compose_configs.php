<?php

return [
    'composeTestModeEnabled' => false,
    'testProjectsNames' => [
        'writepapersforme.online'
    ],
    'exceptionPages' => [
        'OneSignalSDKWorker.js',
        '/essays/*',
    ],
    'composeConditions' => [
        'styles' => true,
        'scripts' => false
    ],
    'selectors' => [
        'styles' => [
            'head' => [
                'head > link.outer-link' => [
                    'use_http' => false
                ],
                'head > link.styles-section' => [
                    'use_http' => true
                ]
            ],
            'body' => [
                'body > link.bottom-styles-section' => [
                    'use_http' => true
                ]
            ]
        ],
        'scripts' => [
            'head' => [

            ],
            'body' => [
                'body > script.js-scripts-section' => [
                    'use_http' => false
                ]
            ]
        ]
    ]
];
