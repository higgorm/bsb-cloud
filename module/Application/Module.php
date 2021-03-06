<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Application\Model;
use Zend\Session\Container;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module {

    public function onBootstrap(MvcEvent $e) {

        $this->initAcl($e);
        $e->getApplication()->getEventManager()->attach('route', array($this, 'checkAcl'));
        $e->getApplication()->getServiceManager()->get('translator');
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function initAcl(MvcEvent $e) {

        $acl = new \Zend\Permissions\Acl\Acl();

        //$roles = $this->getFileRoles($e);
        $roles = $this->getDbRoles($e);

        $allResources = array();
        foreach ($roles as $role => $resources) {

            $role = new \Zend\Permissions\Acl\Role\GenericRole($role);
            $acl->addRole($role);

            //adding resources
            foreach ($resources as $resource) {
                // Edit 4
                if (!$acl->hasResource($resource))
                    $acl->addResource(new \Zend\Permissions\Acl\Resource\GenericResource($resource));

                $acl->allow($role, $resource);
            }
        }
        //setting to view
        $e->getViewModel()->acl = $acl;
    }

    public function checkAcl(MvcEvent $e) {
        $nameRoute = $e -> getRouteMatch() -> getMatchedRouteName();
        $params    = $e->getRouteMatch()->getParams();
        $route     = $params['controller'] . "/" . $params['action'];

        //you set your role
        $session = new Container("orangeSessionContainer");
        $session->userRole = $session->cdPerfilWeb;


        if (isset($session->userRole) && !$e -> getViewModel() -> acl ->hasResource($route)) {
            $response = $e->getResponse();
            $response->getHeaders()->addHeaderLine('Location', $e->getRequest()->getBaseUrl() . '/404');
            $response->setStatusCode(404);
        } else if(isset($session->userRole) && !$e -> getViewModel() -> acl -> isAllowed($session->userRole, $route)) {
            $response = $e->getResponse();
            $response->getHeaders()->addHeaderLine('Location', $e->getRequest()->getBaseUrl() . '/404');
            $response->setStatusCode(404);
        }
    }

    public function getFileRoles(MvcEvent $e){
        $roles = include __DIR__ . '/config/module.acl.roles.php';
        return $roles;
    }

    public function getDbRoles(MvcEvent $e){
        // I take it that your adapter is already configured
        $dbAdapter  = $e->getApplication()->getServiceManager()->get('Zend\Db\Adapter\Adapter');;
        $resultset  = $dbAdapter->query('SELECT PWM.CD_PERFIL_WEB , MWR.DS_MENU_RESOURCE 
                                            FROM LOGIN.DBO.TB_MENU_WEB_RESOURCE MWR
                                                INNER JOIN LOGIN.DBO.TB_PERFIL_WEB_MENUS PWM ON PWM.CD_MENU = MWR.CD_MENU
                                                    ORDER BY 1');
        $results    = $resultset->execute();
        // making the roles array
        $roles = array();

        foreach($results as $result){
            $roles[$result['CD_PERFIL_WEB']][] = $result['DS_MENU_RESOURCE'];
        }

        return $roles;
    }

    public function setPhpSettings(MvcEvent $e) {
        $app = $e->getApplication()->getServiceManager()->get('config');
        $phpSettings = $app['phpSettings'];

        if ($phpSettings) {
            foreach ($phpSettings as $key => $value) {
                ini_set($key, $value);
            }
        }
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig() {
        return array(
            "factories" => array(
                //Services negociais
                'Application\Service\NotaFiscalService' => function ($sm) {
                    return new \Application\Service\NotaFiscalService($sm);
                },

                //Services de banco de ddos
                "loja_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\LojaTable($adapter);
                    return $table;
                },
                "cliente_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\ClienteTable($adapter);
                    return $table;
                },
               /* "tabela_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new TabelaTable($adapter);
                    return $table;
                },*/
				"cargo_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\CargoTable($adapter);
                    return $table;
                },
                "franquia_maca_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\FranquiaMacaTable($adapter);
                    return $table;
                },
                "servicos_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\ServicosTable($adapter);
                    return $table;
                },
                "cliente_rapido_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\ClienteRapidoTable($adapter);
                    return $table;
                },
                "functionario" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\FuncionarioTable($adapter);
                    return $table;
                },
                "agendamento_franquia" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\AgendamentoFranquiaTable($adapter);
                    return $table;
                },
                "agendamento_franquia_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\AgendamentoFranquiaTable($adapter);
                    return $table;
                },
                "agendamento_franquia_servicos" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\AgendamentoFranquiaServicosTable($adapter);
                    return $table;
                },
                "mercadoria_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\MercadoriaTable($adapter);
                    return $table;
                },
                "funcionario_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\FuncionarioTable($adapter);
                    return $table;
                },
                "pedido_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\PedidoTable($adapter);
                    return $table;
                },
                "caixa_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\CaixaTable($adapter);
                    return $table;
                },
                "caixa_funcionario_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\CaixaFuncionarioTable($adapter);
                    return $table;
                },
                "mail_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\MailTable($adapter);
                    return $table;
                },
                "uf_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\UfTable($adapter);
                    return $table;
                },
                "cidade_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\CidadeTable($adapter);
                    return $table;
                },
                "tipo_pedido_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\TipoPedidoTable($adapter);
                    return $table;
                },
                "tipo_movimento_caixa_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\TipoMovimentoCaixaTable($adapter);
                    return $table;
                },
                "classificacao_financeira_table" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\ClassificacaoFinanceiraTable($adapter);
                        return $table;
                },
                "contas_receber_table" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\ContasReceberTable($adapter);
                        return $table;
                },
                "contas_pagar" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\ContasPagarTable($adapter);
                        return $table;
                },
                "contas_pagar_pagamento" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\ContasPagarPagamentoTable($adapter);
                        return $table;
                },
                "estoque" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\EstoqueTable($adapter);
                        return $table;
                },
                "tipo_movimentacao_estoque" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\TipoMovimentacaoEstoqueTable($adapter);
                        return $table;
                },
                "movimentacao_estoque" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\MovimentacaoEstoqueTable($adapter);
                        return $table;
                },
                "banco" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\BancoTable($adapter);
                        return $table;
                },
                "conta_corrente" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\ContaCorrenteTable($adapter);
                        return $table;
                },
                "cartao" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\CartaoTable($adapter);
                        return $table;
                },
                "centro_custo" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\CentroCustoTable($adapter);
                        return $table;
                },
                "fornecedor" => function($sm) {
                        $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                        $table = new Model\FornecedorTable($adapter);
                        return $table;
                },
                "usuario_web_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\UsuarioWebTable($adapter);
                    return $table;
                },
                "perfil_web_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\PerfilWebTable($adapter);
                    return $table;
                },
                 "perfil_web_menu_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\PerfilWebMenuTable($adapter);
                    return $table;
                },
                "menu_web_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\MenuWebTable($adapter);
                    return $table;
                },
                "menu_web_resource_table" => function($sm) {
                    $adapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $table = new Model\MenuWebResourceTable($adapter);
                    return $table;
                },
            ),
        );
    }

}
