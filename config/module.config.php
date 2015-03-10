<?php
// Awfull hack to tell to poedit to translate navigation labels
$translate = function ($message) { return $message; };
return [
    'router' => [
        'routes' => [
            'package-manager' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '[/env/:envId]/security-fix[/:node]',
                    'constraints' => [
                        'envId' => '[0-9]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Package',
                        'action' => 'availableUpgrade',
                        'envId' => '0',
                    ],
                ],
            ],
            'host-patch-list' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '[/env/:envId]/patchlist/:hostname',
                    'constraints' => [
                        'envId' => '[0-9]+',
                        'hostname' => '[0-9a-zA-Z\.\-]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Package',
                        'action' => 'hostList',
                        'envId' => '0',
                    ],
                ],
            ],
            'host-patch-all' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '[/env/:envId]/patch/:hostname/all',
                    'constraints' => [
                        'envId' => '[0-9]+',
                        'hostname' => '[0-9a-zA-Z\.\-]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Package',
                        'action' => 'hostFullPatch',
                        'envId' => '0',
                    ],
                ],
            ],
            'pkgmgr-translation' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/package-manager/translation',
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Package',
                        'action' => 'translation',
                    ],
                ],
            ],
            'package-manager-generic-prepatch' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/env/:envId/pre-patch/:patch[/server/:server]',
                    'constraints' => [
                        'envId' => '[0-9]+',
                        'server' => '[0-9a-zA-Z\.\-]+',
                        'patch' => '[0-9a-zA-Z\-]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Package',
                        'action' => 'prePatch',
                        'envId' => '0',
                    ],
                ],
            ],
            'package-manager-generic-patch' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/env/:envId/patch-apply/:patch[/servers/:server]',
                    'constraints' => [
                        'envId' => '[0-9]+',
                        'server' => '[0-9a-zA-Z\.\-]+',
                        'patch' => '[0-9a-zA-Z\-]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Package',
                        'action' => 'patch',
                        'envId' => '0',
                    ],
                ],
            ],
            'package-manager-patch-detail' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/env/:envId/patch/:patch',
                    'constraints' => [
                        'envId' => '[0-9]+',
                        'patch' => '[0-9a-zA-Z\.\-]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Patch',
                        'action' => 'show',
                        'envId' => '0',
                    ],
                ],
            ],
            'package-manager-security-logs' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '[/env/:envId]/security-logs',
                    'constraints' => [
                        'envId' => '[0-9]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Patch',
                        'action' => 'history',
                        'envId' => '0',
                    ],
                ],
            ]
        ],
    ],
    'mcollective' => [
         'translation' => [
             'patch' => [
                 // 'detail' => $translate('Package <strong>#name#</strong> update to version <i>#version#</i>'),
                 // 'icon' => 'glyphicon-export',
                 'formatter' => 'KmbPackageManager\Service\PatchFormatter'
             ]
         ],
         'handler' => [
             'PatchReplyHandler' => [
                 'factory' => 'KmbPackageManager\Handler\PatchReplyHandlerFactory',
             ]
         ],
         'blacklist' => [
             'puppet',
             'puppet-common',
             'mcollective',
             'mcollective-common'
         ],
    ],
    'navigation' => [
        'navbar' => [
            'servers' => [
                'label' => $translate('Servers'),
                'route' => 'servers',
                'tabindex' => 40,
                'pages' => [
                    [
                        'label' => $translate('Inventory'),
                        'route' => 'servers',
                        'action' => 'index',
                        'useRouteMatch' => true,
                        'tabindex' => 41,
                    ],
                    [
                        'label' => $translate('Security'),
                        'route' => 'package-manager',
                        'action' => 'availableUpgrade',
                        'useRouteMatch' => true,
                        'tabindex' => 42,
                    ],
                    [
                        'label' => $translate('Security Logs'),
                        'route' => 'package-manager-security-logs',
                        'action' => 'history',
                        'useRouteMatch' => true,
                        'tabindex' => 42,
                    ],
                ],
            ],
        ],
        'breadcrumb' => [
            'home' => [
                'pages' => [
                    'servers' => [
                        'label' => $translate('Inventory'),
                    ],
                    [
                        'label' => $translate('Security'),
                        'route' => 'package-manager',
                        'action' => 'availableUpgrade',
                        'useRouteMatch' => true,
                        'pages' => [
                            [
                                'id' => 'patch-detail',
                                'label' => $translate('Patch detail'),
                                'route' => 'package-manager-patch-detail',
                                'useRouteMatch' => true,
                            ]
                        ],
                    ],
                    [
                        'label' => $translate('Security Logs'),
                        'route' => 'package-manager-security-logs',
                        'action' => 'history',
                        'useRouteMatch' => true,
                    ],
                ],
            ],
        ],
    ],
    'zenddb_repositories' => [
        'PatchRepository' => [
            'aggregate_root_class' => 'KmbPackageManager\Model\Patch',
            'aggregate_root_hydrator_class' => 'KmbPackageManager\Hydrator\PatchHydrator',
            'table_name' => 'vulnerability_list',
            'table_sequence_name' => 'vulnerability_list_id_seq',
            'host_table_name' => 'registrationinventory',
            'join_table_name' => 'host_vulnerability',
            'factory' => 'KmbPackageManager\Service\PatchRepositoryFactory',
            'repository_class' => 'KmbPackageManager\Service\PatchRepository',
        ],
        'SecurityLogsRepository' => [
            'aggregate_root_class'          => 'KmbPackageManager\Model\SecurityLogs',
            'aggregate_root_hydrator_class' => 'KmbPackageManager\Hydrator\SecurityLogsHydrator',
            'table_name'                    => 'security_logs',
            'table_sequence_name'           => 'security_logs_id_seq',
            'repository_class'              => 'KmbPackageManager\Service\SecurityLogsRepository',
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'KmbPackageManager\Controller\Package' => 'KmbPackageManager\Controller\PackageController',
            'KmbPackageManager\Controller\Patch' => 'KmbPackageManager\Controller\PatchController',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'view_helper_config' => [
        'widget' => [
            'serverInfoBar' => [
                'partials' => [
                    'kmb-package-manager/server/kmbpkgmgr.serverinfobar.phtml',
                ],
            ],
            'serverTabTitle' => [
                'partials' => [
                    'kmb-package-manager/server/kmbpkgmgr.servertabtitle.phtml',
                ],
            ],
            'serverTabContent' => [
                'partials' => [
                    'kmb-package-manager/server/kmbpkgmgr.servertabcontent.phtml',
                ],
            ],
        ],
    ],
    'zfc_rbac' => [
        'guards' => [
            'ZfcRbac\Guard\ControllerGuard' => [
                [
                    'controller' => 'KmbPackageManager\Controller\Package',
                    'actions' => ['availableUpgrade'],
                    'roles' => ['admin']
                ]
            ]
        ],
    ],
    'datatables' => [
        'fixlist' => [
            'id' => 'fixlist',
            'classes' => ['table','table-striped','table-hover','table-condensed','bootstrap-datatable'],
            'collectorFactory' => 'KmbPackageManager\Service\AvailableFixCollectorFactory',
            'columns' => [
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\PatchNameDecorator',
                    'key'       => 'publicid',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\PackagesDecorator',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\CriticityDecorator',
                    'key'       => 'criticity',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\NbServersDecorator',
                ],
            ]
        ],
        'nodefixlist' => [
            'id' => 'nodefixlist',
            'classes' => ['table','table-striped','table-hover','table-condensed','bootstrap-datatable'],
            'collectorFactory' => 'KmbPackageManager\Service\AvailableFixCollectorFactory',
            'columns' => [
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\PatchNameDecorator',
                    'key'       => 'publicid',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\PackagesDecorator',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\CriticityDecorator',
                    'key'       => 'criticity',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\NodePatchDecorator',
                ],
            ]
        ],
        'securitylogs' => [
            'id' => 'securitylogs',
            'classes' => ['table','table-striped','table-hover','table-condensed','bootstrap-datatable'],
            'collectorFactory' => 'KmbPackageManager\Service\SecurityLogsCollectorFactory',
            'columns' => [
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\SecurityLogsDateDecorator',
                    'key'       => 'updated_at',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\SecurityLogsUserDecorator',
                    'key'       => 'username',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\SecurityLogsPackageDecorator',
                    'key'       => 'package',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\SecurityLogsFromVersionDecorator',
                    'key'       => 'from_version',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\SecurityLogsToVersionDecorator',
                    'key'       => 'to_version',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\SecurityLogsServerDecorator',
                    'key'       => 'to_version',
                ],
                [
                    'decorator' => 'KmbPackageManager\View\Decorator\SecurityLogsStatusDecorator',
                    'key'       => 'status',
                ],
            ]
        ]
    ],
    'asset_manager' => [
        'resolver_configs' => [
            'paths' => [
                __DIR__ . '/../public',
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'KmbPackageManager\Service\AvailableFix' => 'KmbPackageManager\Service\AvailableFixCollectorFactory',
        ],
    ],
];
