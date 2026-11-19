<?php

/**
 * Metadata version
 */
$sMetadataVersion = '2.1';

$aModule = [
    'id' => 'ock3',
    'title' => 'Objectcode K3',
    'description' => [
        'de' => 'Integration von Objectcode K3',
    ],
    'version' => '1.3.0',
    'author' => 'ObjectCode GmbH',
    'email' => 'info@objectcode.de',
    'url' => 'www.objectcode.de',
    'thumbnail' => 'logo.webp',
    'extend' => [],
    'controllers' => [
        'oc_ock3_productexportcontroller' => \ObjectCode\K3\Application\Controller\ProductExportController::class,
        'oc_ock3_connectorcontroller' => \ObjectCode\K3\Application\Controller\ConnectorController::class,
        'oc_ock3_basketcontroller' => \ObjectCode\K3\Application\Controller\BasketController::class,
    ],
    'templates' => [],
    'settings' => [
        [
            'group' => 'ock3',
            'name' => 'blOcK3Active',
            'type' => 'bool',
            'value' => true,
            'position' => 1,
        ],
        [
            'group' => 'ock3',
            'name' => 'blOcK3TestMode',
            'type' => 'bool',
            'value' => false,
            'position' => 10,
        ],
        [
            'group' => 'ock3',
            'name' => 'blOcK3CombineArticles',
            'type' => 'bool',
            'value' => false,
            'position' => 20,
        ],
        [
            'group' => 'ock3',
            'name' => 'sOcK3AuthSecret',
            'type' => 'str',
            'value' => '',
            'position' => 30,
        ],
        [
            'group' => 'ock3',
            'name' => 'sOcK3AuthToken',
            'type' => 'str',
            'value' => '',
            'position' => 40,
        ],
    ],
    'blocks' => [],
    'events' => [
        'onActivate' => '\ObjectCode\K3\Core\Events::onActivate',
        'onDeactivate' => '\ObjectCode\K3\Core\Events::onDeactivate',
    ],
];
