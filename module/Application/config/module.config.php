<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
return array(
    'controllers' => array(
        'invokables' => array(
            'Application\Controller\Index' 			=> 'Application\Controller\indexController',
            'Application\Controller\Painel' 		=> 'Application\Controller\painelController',
            'Application\Controller\Pedido' 		=> 'Application\Controller\pedidoController',
			'Application\Controller\Nota'   		=> 'Application\Controller\notaController',
			'Application\Controller\Mercadoria' 	=> 'Application\Controller\mercadoriaController',
			'Application\Controller\Tabela' 		=> 'Application\Controller\tabelaController',

            'Application\Controller\Relatorio' => 'Application\Controller\relatorioController',
            'Application\Controller\RelatorioAgendamento' => 'Application\Controller\relatorioAgendamentoController',
            'Application\Controller\RelatorioAtendimento' => 'Application\Controller\relatorioAtendimentoController',
            'Application\Controller\RelatorioVendasMensalTipoPagamento' => 'Application\Controller\relatorioVendasMensalTipoPagamentoController',
            'Application\Controller\RelatorioVendasSecao' => 'Application\Controller\relatorioVendasSecaoController',
            'Application\Controller\RelatorioInventarioEstoque' => 'Application\Controller\relatorioInventarioEstoqueController',
            'Application\Controller\RelatorioCaixaResumido' => 'Application\Controller\relatorioCaixaResumidoController',
            'Application\Controller\RelatorioResumoCaixa' => 'Application\Controller\relatorioResumoCaixaController',
            'Application\Controller\RelatorioCaixa' => 'Application\Controller\relatorioCaixaController',
            'Application\Controller\RelatorioPedidoCompras' => 'Application\Controller\relatorioPedidosComprasController',
            'Application\Controller\RelatorioPedido' => 'Application\Controller\relatorioPedidoController',
			'Application\Controller\RelatorioNota' => 'Application\Controller\relatorioNotaController',
			            
            'Application\Controller\Cliente' => 'Application\Controller\clienteController',
            'Application\Controller\Maca' => 'Application\Controller\MacaController',
            'Application\Controller\Agenda' => 'Application\Controller\AgendaController',
            'Application\Controller\Caixa' => 'Application\Controller\CaixaController',
            'Application\Controller\Mail' => 'Application\Controller\MailController',
            'Application\Controller\ContasReceber' => 'Application\Controller\contasReceberController',
            'Application\Controller\ContasPagar' => 'Application\Controller\contasPagarController',
            'Application\Controller\Estoque' => 'Application\Controller\estoqueController',
            'Application\Controller\Ajax' => 'Application\Controller\AjaxController',

            //acl admin-user
            'Application\Controller\PerfilWeb' => 'Application\Controller\PerfilWebController',
            'Application\Controller\UsuarioWeb' => 'Application\Controller\UsuarioWebController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller' => 'Application\Controller\Index',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[:controller[/:action][/:id]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                                'id' => '0',
                            ),
                        ),
                    ),
                ),
            ),
            'painel' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/painel',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller' => 'Application\Controller\Painel',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
            ),
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            'translator' => 'Zend\I18n\Translator\TranslatorServiceFactory',
        ),
    ),
    'translator' => array(
        'locale' => 'pt_BR',
        'translation_file_patterns' => array(
            array(
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
            ),
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => array(
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'layout/login' => __DIR__ . '/../view/layout/login.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),

);
