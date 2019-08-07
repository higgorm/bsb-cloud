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
use Application\Model\FranquiaMacaTable;
use Application\Model\FranquiaMaca;
use Application\Form\FranquiaMacaForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

/**
 * 
 * @author HIGOR
 *
 */
class MacaController extends OrangeWebAbstractActionController {

    protected $franquiaMacaTable;

    public function getTable() {
        if (!$this->franquiaMacaTable) {
            // get the db adapter
            $sm = $this->getServiceLocator();
            $this->franquiaMacaTable = $sm->get("franquia_maca_table");
        }
        return $this->franquiaMacaTable;
    }

    /**
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
    public function indexAction() {
        //to do
    }

    public function cadastrarAction() {

        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');

        $session = new Container("orangeSessionContainer");
        if( @$session->cdBase ){
            $statement = $dbAdapter->query("USE BDGE_".$session->cdBase);
            $statement->execute();
        }


        try {
            $form = new FranquiaMacaForm($dbAdapter);
            $request = $this->getRequest();
            $terminal = $this->params()->fromQuery('modal') == 'show' ? true : false;

            if ($request->isPost()) {
                //instance of model
                $model = new FranquiaMaca();

                //get Post of request
                $data = $request->getPost();

                //set filter of model
                $form->setInputFilter($model->getInputFilter());
                $form->setData($data);

                if ($form->isValid()) {
                    $dbAdapter->getDriver()->getConnection()->beginTransaction();
                    $model->exchangeArray($data);
                    $this->getTable()->save($model);
                    $dbAdapter->getDriver()->getConnection()->commit();

                    return $this->redirect()->toUrl("/agenda/index");
                }
            }

            $view = new ViewModel(array(
                "form" => $form,
                "formAction" => "cadastrar",
            ));

            $view->setTerminal($terminal);
            $view->setTemplate("application/maca/form.phtml");

            return $view;
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }

}
