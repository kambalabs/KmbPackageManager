<?php
// Awfull hack to tell to poedit to translate navigation labels
$translate = function ($message) { return $message; };
return [
    'router' => [
        'routes' => [
            'package-manager' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/env/:envId/security-fix',
                    'constraints' => [
                        'envId' => '[0-9]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Package',
                        'action' => 'availableUpgrade',
                    ],
                ],
            ],
            'package-manager-prepatch' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/env/:envId/servers/:server/pre-patch',
                    'constraints' => [
                        'envId' => '[0-9]+',
                        'server' => '[0-9a-zA-Z\.\-]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Package',
                        'action' => 'prePatchHost',
                    ],
                ],
            ],
            'package-manager-patch' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/env/:envId/servers/:server/patch',
                    'constraints' => [
                        'envId' => '[0-9]+',
                        'server' => '[0-9a-zA-Z\.\-]+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'KmbPackageManager\Controller',
                        'controller' => 'Package',
                        'action' => 'patchHost',
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
                    ],
                ],
            ]
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
        ]
    ],
    'asset_manager' => [
        'resolver_configs' => [
            'paths' => [
                __DIR__ . '/../public',
            ],
        ],
    ],
    # 'service_manager' => [
    #     'factories' => [
    #         'KmbMcollective\Service\McollectiveLog' => 'KmbMcollective\Service\McollectiveLogFactory',
    #     ],
    # ],
];
