<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Api\Controller\NotaFiscal' => 'Api\Controller\NotaFiscalRestController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'api' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/api/nota-fiscal[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Api\Controller\NotaFiscal',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
);