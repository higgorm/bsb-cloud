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
class MenuWebController extends AbstractActionController
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

}