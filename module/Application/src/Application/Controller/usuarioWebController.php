<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\I18n\View\Helper\DateFormat;
use Application\Form\UsuarioWebForm;
use Application\Form\UsuarioWebSearchForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model\UsuarioWeb;
use Application\Model\UsuarioWebTable;
use Zend\Db\Adapter\Driver\ConnectionInterface;
/**
 *
 * @author HIGOR
 *
 */
class UsuarioWebController extends OrangeWebAbstractActionController
{

    protected $usuarioWebTable;

    public function getTable()
    {
        if (!$this->usuarioWebTable) {
            // get the db adapter
            $sm = $this->getServiceLocator();
            $this->usuarioWebTable = $sm->get("usuario_web_table");
        }

        return $this->usuarioWebTable;
    }

    public function getTables($table)
    {
        $sm = $this->getServiceLocator();
        return $sm->get($table);
    }

    /**
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::usuarioWebAction()
     */
    public function indexAction()
    {
        $searchForm = new UsuarioWebSearchForm();
        $request = $this->getRequest();
        $messages = $this->flashMessenger()->getMessages();
        $parametros = array();
        $pageNumber = (int) $this->params()->fromQuery('pg');
        $terminal = $this->params()->fromQuery('modal') == 'show' ? true : false;
        $param = array();

        if ($pageNumber == 0) {
            $pageNumber = 1;
        }

        if ($request->isPost()) {
            $post = $request->getPost();

            foreach ($post as $key => $value) {
                if (!empty($value)) {
                    $param[$key] = trim($value);
                }
            }

            $searchForm->setData($post);
        }
		
        $usuarios = $this->getTable()->fetchAll($param, $pageNumber);
		$util     = new \Util;

        $view = new ViewModel(array(
            "form"    => $searchForm,
            "messages" => $messages,
            "usuarios" => $usuarios,
			"util"     => $util,
        ));

        $view->setTemplate("application/usuario/index.phtml");
        $view->setTerminal($terminal);
        return $view;
    }


    public function cadastroAction()
    {
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
        $request = $this->getRequest();
        $post = $request->getPost();
        $terminal = $this->params()->fromQuery('modal') == 'show' ? true : false;
        $salvar = true;
        $messages = $this->flashMessenger()->getMessages();

        if ($request->isPost()) {
            try {
                $dbAdapter->getDriver()->getConnection()->beginTransaction();
                $data = $request->getPost();

                if(empty($data->ds_senha)) {
                    $message = array("danger" => "Campo Senha é obrigatório!");
                    $salvar = false;
                } else if (empty($data->ds_confirmacao_senha)) {
                    $message = array("danger" => "Campo de Confirmação da senha é obrigatório!");
                    $salvar = false;
                } else if ($data->ds_confirmacao_senha !== $data->ds_senha) {
                    $message = array("danger" => "A Senha e a Confirmação da senha são diferentes!");
                    $salvar = false;
                }


                if ($salvar) {
                    $this->getTable()->save($data,true);
                    $dbAdapter->getDriver()->getConnection()->commit();
                    $message = array("success" => "Cadastro efetuado com sucesso");
                    $redirect = "/usuario-web/index?pg=1";
                } else {
                    $redirect = "/usuario-web/cadastro";
                }


                $this->flashMessenger()->addMessage($message);
                return $this->redirect()->toUrl($redirect);

            } catch (Exception $e) {
                $dbAdapter->getDriver()->getConnection()->rollback();
            }
        }

        $usuario    = array('');
        $loja       = $this->getTable()->getLojaUsuarioWebForSelectOptions();
        $perfil     = $this->getTables('perfil_web_table')->getPerfilUsuarioWebForSelectOptions();

        $view = new ViewModel(array(
            "usuario" 	    => $usuario,
            "lojaUsuario"   => $loja,
            "perfilUsuario" => $perfil,
            "messages" => $messages,
        ));
        $view->setTemplate("application/usuario/cadastro.phtml");
        $view->setTerminal($terminal);

        return $view;
    }

    public function edicaoAction()
    {
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
        $request = $this->getRequest();
        $post = $request->getPost();
        $terminal = $this->params()->fromQuery('modal') == 'show' ? true : false;
        $salvar = true;
        $salvarNovaSenha = false;
        $id = (int) $this->params()->fromQuery('id');
        $messages = $this->flashMessenger()->getMessages();

        if ($request->isPost()) {
            try {
                $data = $request->getPost();

                if(!empty($data->ds_confirmacao_senha) && empty($data->ds_senha)) {
                    $message = array("danger" => "Campo senha é obrigatório!");
                    $salvar = false;
                } else if (empty($data->ds_confirmacao_senha) && !empty($data->ds_senha)) {
                    $message = array("danger" => "Campo de Confirmação da senha é obrigatório!");
                    $salvar = false;
                } else if ((!empty($data->ds_senha)) && ($data->ds_confirmacao_senha !== $data->ds_senha)) {
                    $message = array("danger" => "A senha e a Confirmação da senha são diferentes!");
                    $salvar = false;
                } else if (!empty($data->ds_senha) && !empty($data->ds_confirmacao_senha)) {
                    $salvarNovaSenha = true;
                }

                if ($salvar) {
                    $dbAdapter->getDriver()->getConnection()->beginTransaction();
                    $this->getTable()->save($data,$salvarNovaSenha);
                    $dbAdapter->getDriver()->getConnection()->commit();
                    $message = array("success" => "Alteração efetuada com sucesso");
                    $redirect = "/usuario-web/index?pg=1";
                } else {
                    $redirect = "/usuario-web/edicao?id=".$data->cd_usuario_web;
                }

                $this->flashMessenger()->addMessage($message);
                return $this->redirect()->toUrl($redirect);

            } catch (Exception $e) {
                $dbAdapter->getDriver()->getConnection()->rollback();
            }
        }


        if( $id > 0 ){
            $usuario    = $this->getTable()->getId($id);
        } else {
            $usuario    = array('');
        }

        $loja       = $this->getTable()->getLojaUsuarioWebForSelectOptions();
        $perfil     = $this->getTables('perfil_web_table')->getPerfilUsuarioWebForSelectOptions();

        $view = new ViewModel(array(
            "usuario" 	        => $usuario,
            "lojaUsuario"       => $loja,
            "perfilUsuario" 	=> $perfil,
            "messages"          => $messages,
        ));
        $view->setTemplate("application/usuario/edicao.phtml");
        $view->setTerminal($terminal);

        return $view;
    }

    public function inativarAction()
    {
        // get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        try {
            $dbAdapter->getDriver()->getConnection()->beginTransaction();
            $id = (int) $this->params()->fromQuery('id');

            if ($this->getTable()->inativar($id)) {
                $message = array("success" => "Inativado com sucesso");
            } else {
                $message = array("error" => "Não foi possível, este registro está em uso!");
            }

            $dbAdapter->getDriver()->getConnection()->commit();

            $this->flashMessenger()->addMessage($message);
            return $this->redirect()->toUrl("index?pg=1");
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }

    public function ativarAction()
    {
        // get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        try {
            $dbAdapter->getDriver()->getConnection()->beginTransaction();
            $id = (int) $this->params()->fromQuery('id');

            if ($this->getTable()->ativar($id)) {
                $message = array("success" => "Ativado com sucesso");
            } else {
                $message = array("error" => "Não foi possível, este registro está em uso!");
            }

            $dbAdapter->getDriver()->getConnection()->commit();

            $this->flashMessenger()->addMessage($message);
            return $this->redirect()->toUrl("index?pg=1");
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }


}
