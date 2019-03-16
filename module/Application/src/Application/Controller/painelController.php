<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Permissions\Acl\Acl;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model\LojaTable;
use Application\Model\loja;

/**
 * 
 * @author HIGOR
 *
 */
class PainelController extends AbstractActionController {

    protected $lojaTable;

    public function getTable() {
        if (!$this->lojaTable) {
            // get the db adapter
            $sm = $this->getServiceLocator();
            $this->lojaTable = $sm->get("loja_table");
        }

        return $this->lojaTable;
    }

    /**
    * (non-PHPdoc)
    * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
    */
    public function indexAction() {
        $auth = new AuthenticationService();
        $session = new Container("orangeSessionContainer");
			
        //$identity = null;
        if ((!$auth->hasIdentity()) && ($session->usuario != 'ORANGE')) {
            // redirect to user index page
            return $this->redirect()->toRoute('home');
        } else {

            $id = (int) $session->cdLoja;
            $loja = $this->getTable()->getLojaDefault($id);
            if (($session->usuario == 'ORANGE') || ($session->stGerente == true)) {
                $visualizarRelatorio = true;
            } else {
                $visualizarRelatorio = false;
            }

            $session->dsLoja = $loja->ds_fantasia;

            return array('cdLoja' => $loja->cd_loja,
                'nomeLoja' => $loja->ds_fantasia,
                'visualizaRelatorio' => $visualizarRelatorio);
        }
    }

}
