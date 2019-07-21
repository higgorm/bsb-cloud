<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Http\Response as HttpResponse;
use Zend\Mvc\Exception;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ConsoleModel;
use Zend\View\Model\ViewModel;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * OrangeWebAbstract controller
 *
 * Convenience methods for pre-built plugins (@see __call):
 * @method \Zend\Mvc\Controller\Plugin\Redirect redirect()
 * @method \Zend\Mvc\Controller\Plugin\Url url()
 */

abstract class OrangeWebAbstractActionController extends AbstractActionController {

    public function __construct() {
        $session = isset($_SESSION['orangeSessionContainer']->cdLoja);
        $server = $_SERVER['REQUEST_URI'];
        $arrUri = array('/', '/index/index', '/index/login', '/index/logout');

        if (!$session && !in_array($server, $arrUri)) { //sessão encerrada

            if ((strpos($_SERVER['REQUEST_URI'], 'modal') !== false)
             || ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) { //Se é modal ou requisição ajax

                echo '<div class="alert alert-danger"> 
                            Sua sess&atilde;o foi encerrada!<br>
                             Clique <a href="/index"><b>aqui</b></a> para efetuar o login novamente.
                      </div>';
            } else {
                header('Location: /index/index');
                $this->flashMessenger()->addMessage("Sua sess&atilde;o foi encerrada!");
            }
            exit();
        }
    }

}
