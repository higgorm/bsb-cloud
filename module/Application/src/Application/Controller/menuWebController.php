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
use Application\Model;
use Zend\Db\Adapter\Driver\ConnectionInterface;

/**
 *
 * @author higgor.m@gmail.com
 *
 */
class MenuWebController extends OrangeWebAbstractActionController
{

    protected $menuWebTable;
    
    public function getTable()
    {
        if (!$this->menuWebTable) {
            // get the db adapter
            $sm = $this->getServiceLocator();
            $this->menuWebTable = $sm->get("menu_web_table");
        }

        return $this->menuWebTable;
    }

    public function getTables($table)
    {
        $sm = $this->getServiceLocator();
        return $sm->get($table);
    }

    /**
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::menuWebAction()
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

        $menus = $this->getTable()->fetchAll($param, $pageNumber);
        $util     = new \Util;
        $view = new ViewModel(array(
            "messages" => $messages,
            "menus"     => $menus,
            "util"     => $util,
        ));

        $view->setTemplate("application/menu/index.phtml");
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
                //save menu
                $this->getTable()->save($data);

                //commit transaction
                $dbAdapter->getDriver()->getConnection()->commit();

                $message = array("success" => "Cadastro efetuado com sucesso");
                $this->flashMessenger()->addMessage($message);
                return $this->redirect()->toUrl("/menu-web/index?pg=1");

            } catch (Exception $e) {
                $dbAdapter->getDriver()->getConnection()->rollback();
            }
        }

        $menu    = array('');
        $menuPai =  $this->getTable()->getMenuPai();

        $view = new ViewModel(array(
            "menu" 	  => $menu,
            "menuPai" => $menuPai
        ));
        $view->setTemplate("application/menu/cadastro.phtml");
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

                //save menu
                $this->getTable()->save($data);

                $dbAdapter->getDriver()->getConnection()->commit();

                $message = array("success" => "Alteração efetuada com sucesso");
                $this->flashMessenger()->addMessage($message);
                return $this->redirect()->toUrl("/menu-web/index?pg=1");

            } catch (Exception $e) {
                $dbAdapter->getDriver()->getConnection()->rollback();
            }
        }

        $id = (int) $this->params()->fromQuery('id');
        if( $id > 0 ){
            $menu    = $this->getTable()->getId($id);
        } else {
            $menu = array('');
        }


        $menuPai =  $this->getTable()->getMenuPai();

        $view = new ViewModel(array(
            "menu" 	  => $menu,
            "menuPai" => $menuPai
        ));

        $view->setTemplate("application/menu/edicao.phtml");
        $view->setTerminal($terminal);

        return $view;
    }

    public function resourcesAction()
    {
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
        $request = $this->getRequest();
        $post = $request->getPost();

        $cdMenu    = (int) $this->params()->fromQuery('id');
        $resources = array();
        $menu      = array('cd_menu'=>$cdMenu);

        if ($request->isPost()) {
            try {
                $dbAdapter->getDriver()->getConnection()->beginTransaction();
                $data = $request->getPost();
                //save resources
                $this->getTables('menu_web_resource_table')->save($data);

                //commit transaction
                $dbAdapter->getDriver()->getConnection()->commit();

                $message = array("success" => "Cadastro de rota efetuado com sucesso");
                $this->flashMessenger()->addMessage($message);
                return $this->redirect()->toUrl("/menu-web/resources?id=".$data->cd_menu);

            } catch (Exception $e) {
                $dbAdapter->getDriver()->getConnection()->rollback();
            }
        }

        try {
             //Recupera a lista de resources do menu
             if($cdMenu) {
                 $menu          = $this->getTable()->getId($cdMenu);
                 $resources     = $this->getTables('menu_web_resource_table')->fetchAll(array('CD_MENU'=>$cdMenu));
             }
        } catch (Exception $e) {
            throw new Exception("Erro ao recuperar a lista de resources deste item de menu");
        }


        $view = new ViewModel(array(
            "resources" => $resources,
            "menu" 	    => $menu
        ));
        $view->setTemplate("application/menu/resources.phtml");
        $view->setTerminal(false);

        return $view;
    }

    public function removerResourceAction()
    {

        $cdMenuWebResource    = (int) $this->params()->fromQuery('id');
        $resource             = $this->getTables('menu_web_resource_table')->getId($cdMenuWebResource);

        $this->getTables('menu_web_resource_table')->remove($cdMenuWebResource);

        return $this->redirect()->toUrl("/menu-web/resources?id=".$resource->cd_menu);

    }


}