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


/**
 *
 * @author higgor.m@gmail.com
 *
 */
class UsuarioController extends AbstractActionController
{

    public function getTable($strService) {
        $sm = $this->getServiceLocator();
        $this->table = $sm->get($strService);

        return $this->table;
    }

    public function indexAction(){

        $view = new ViewModel(array());

        return $view;
    }

    public function cadastrarAction(){

        $view = new ViewModel(array());

        return $view;
    }

    public function editarAction(){

        $view = new ViewModel(array());

        return $view;
    }
}