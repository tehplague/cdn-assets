<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'CDN URL postprocessor',
    'description' => 'CDN URL postprocessor capable of resolving Webpack asset manifests',
    'category' => 'plugin',
    'author' => 'Christian Spoo',
    'author_company' => '',
    'author_email' => 'mail@christian-spoo.info',
    'dependencies' => '',
    'state' => 'beta',
    'clearCacheOnLoad' => '1',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'php' => '7.2.2-7.99.99',
            'typo3' => '8.7.10-8.99.99'
        ]
    ],
    'autoload' => [
        'psr-4' => [
            'Tehplague\\CdnAssets\\' => 'Classes/'
        ]
    ]
];
