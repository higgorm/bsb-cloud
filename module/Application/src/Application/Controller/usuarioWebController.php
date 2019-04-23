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
class UsuarioWebController extends AbstractActionController
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

        if ($request->isPost()) {
            try {
                $dbAdapter->getDriver()->getConnection()->beginTransaction();
                $data = $request->getPost();

                $this->getTable()->save($data);
                $dbAdapter->getDriver()->getConnection()->commit();

                $message = array("success" => "Cadastro efetuado com sucesso");
                $this->flashMessenger()->addMessage($message);
                return $this->redirect()->toUrl("/usuario-web/index?pg=1");

            } catch (Exception $e) {
                $dbAdapter->getDriver()->getConnection()->rollback();
            }
        }

        $id = (int) $this->params()->fromQuery('id');
        if( $id > 0 ){
            $usuario   = $this->getTable()->getId($id);
            $loja      = $this->getTable()->getLojaUsuarioWebForSelectOptions();
            //$perfil  = $this->getServiceLocator()->get('cidade_table')->getCidade($cliente->cd_cidade);
        }else{
            $usuario = array('');
            $loja      = '';
            //$perfil  = '';
        }

        $view = new ViewModel(array(
            "usuario" 	  => $usuario,
            "lojaUsuario" => $loja,
            //"perfil" 	=> $perfil,
        ));
        $view->setTemplate("application/usuario/cadastro.phtml");
        $view->setTerminal($terminal);

        return $view;
    }

   /* public function editarAction()
    {
        $terminal = $this->params()->fromQuery('modal') == 'show' ? true : false;
        // get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        try {
                $id = (int) $this->params()->fromQuery('id');
                $usuario = $this->getTable()->getId($id);

                $form = new UsuarioWebForm($dbAdapter);
                $form->setBindOnValidate(false);
                $form->bind($usuario);
                $form->get('submit')->setLabel("Alterar");

                $request = $this->getRequest();

                if ($request->isPost()) {
                    //instance of model
                    $model = new UsuarioWeb();

                    //get Post of request
                    $data = $request->getPost();

                    //set filter of model
                    $form->setData($data);

                    if ($form->isValid()) {
                        $dbAdapter->getDriver()->getConnection()->beginTransaction();
                        $form->bindValues();

                        $model->exchangeArray($data);
                        $this->getTable()->save($model);

                        $dbAdapter->getDriver()->getConnection()->commit();
                        $message = array("success" => "Altera&ccedil;&atilde;o efetuada com sucesso");
                        $this->flashMessenger()->addMessage($message);
                        return $this->redirect()->toUrl("/usuario-web/index?pg=1");
                    }
                }


                $view = new ViewModel(array(
                    "form" => $form,
                    "formAction" => '/usuario-web/cadastrar'
                ));

                $view->setTemplate("application/usuario/form.phtml");
                $view->setTerminal($terminal);
                return $view;
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }*/

}
