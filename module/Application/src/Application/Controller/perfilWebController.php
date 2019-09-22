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
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model\PerfilWeb;
use Application\Model\PerfilWebTable;
use Zend\Db\Adapter\Driver\ConnectionInterface;

/**
 *
 * @author higgor.m@gmail.com
 *
 */
class PerfilWebController extends OrangeWebAbstractActionController
{

    protected $perfilWebTable;
    
    public function getTable()
    {
        if (!$this->perfilWebTable) {
            // get the db adapter
            $sm = $this->getServiceLocator();
            $this->perfilWebTable = $sm->get("perfil_web_table");
        }

        return $this->perfilWebTable;
    }

    public function getTables($table)
    {
        $sm = $this->getServiceLocator();
        return $sm->get($table);
    }

    /**
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::perfilWebAction()
     */
    public function indexAction()
    {

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
        }

        $perfis = $this->getTable()->fetchAll($param, $pageNumber);
        $util     = new \Util;

        $view = new ViewModel(array(
            "messages" => $messages,
            "perfis" => $perfis,
            "util"     => $util,
        ));

        $view->setTemplate("application/perfil/index.phtml");
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
                //save perfil
                $data->cd_perfil_web = $this->getTable()->save($data);
                //save menus of perfil
                $this->getTables('perfil_web_menu_table')->save($data);

                //commit transaction
                $dbAdapter->getDriver()->getConnection()->commit();

                $message = array("success" => "Cadastro efetuado com sucesso");
                $this->flashMessenger()->addMessage($message);
                return $this->redirect()->toUrl("/perfil-web/index?pg=1");

            } catch (Exception $e) {
                $dbAdapter->getDriver()->getConnection()->rollback();
            }
        }

        $perfil = array('');

        $view = new ViewModel(array(
            "perfil" 	  => $perfil,
        ));
        $view->setTemplate("application/perfil/cadastro.phtml");
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

        if ($request->isPost()) {
            try {
                $dbAdapter->getDriver()->getConnection()->beginTransaction();
                $data = $request->getPost();

                //save perfil
                $this->getTable()->save($data);

                //save menus of perfil
                $this->getTables('perfil_web_menu_table')->save($data);

                $dbAdapter->getDriver()->getConnection()->commit();

                $message = array("success" => "Alteração efetuada com sucesso");
                $this->flashMessenger()->addMessage($message);
                return $this->redirect()->toUrl("/perfil-web/index?pg=1");

            } catch (Exception $e) {
                $dbAdapter->getDriver()->getConnection()->rollback();
            }
        }

        $id = (int) $this->params()->fromQuery('id');
        if( $id > 0 ){
            $perfil   = $this->getTable()->getId($id);
            $menus     = $this->getTables('perfil_web_menu_table')->buscarMenusPerfil($id);

        }else{
            $perfil = array('');
            $menus = array('');
        }


        $view = new ViewModel(array(
            "perfil" 	=> $perfil,
            "menus" 	=> $menus,
        ));

        $view->setTemplate("application/perfil/edicao.phtml");
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

    public function meusDadosAction(){

        $session = new Container("orangeSessionContainer");

        $view = new ViewModel(array(
                'cdBase'    => $session->cdBase,
                'usuario'   => $session->usuario,
                'perfil'    => $session->dsPerfilWeb,
                'cdLoja'    => $session->cdLoja,
                'dsLoja'    => $session->dsLoja,
                'email'     => $session->email
            )
        );
        $view->setTemplate("application/perfil/meusDados.phtml");
        $view->setTerminal(false);

        return $view;
    }


}