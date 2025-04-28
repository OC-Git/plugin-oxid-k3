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
    'version' => '1.1.0',
    'author' => 'ObjectCode GmbH',
    'email' => 'info@objectcode.de',
    'url' => 'www.objectcode.de',
    'thumbnail' => 'logo.webp',
    'extend' => [
       // \OxidEsales\Eshop\Application\Model\BasketItem::class => \ObjectCode\K3\Extend\Application\Model\BasketItem::class,
        //\OxidEsales\Eshop\Application\Model\Order::class => \ObjectCode\K3\Extend\Application\Model\Order::class,
    ],
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
    'blocks' => [
        [
            'template' => 'page/checkout/inc/basketcontents_list.tpl',
            'block' => 'checkout_basketcontents_basketitem_persparams',
            'file' => 'Application/views/tpl/blocks/checkout_basketcontents_basketitem_persparams.tpl'
        ],
        [
            'template' => 'page/checkout/inc/basketcontents_table.tpl',
            'block' => 'checkout_basketcontents_basketitem_persparams',
            'file' => 'Application/views/tpl/blocks/checkout_basketcontents_basketitem_persparams.tpl'
        ],
        [
            'template' => 'email/html/order_cust.tpl',
            'block' => 'email_html_order_cust_basketitem_persparams',
            'file' => 'Application/views/tpl/blocks/email_html_order_cust_basketitem_persparams.tpl'
        ],
        [
            'template' => 'email/plain/order_cust.tpl',
            'block' => 'email_plain_order_cust_persparams',
            'file' => 'Application/views/tpl/blocks/email_plain_order_cust_persparams.tpl'
        ],
    ],
    'events' => [
        'onActivate' => '\ObjectCode\K3\Core\Events::onActivate',
        'onDeactivate' => '\ObjectCode\K3\Core\Events::onDeactivate',
    ],
];
