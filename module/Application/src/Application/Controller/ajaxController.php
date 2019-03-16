<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use DOMPDFModule\View\Model\PdfModel;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

/**
 *
 * @author Andre Luiz Geraldi
 *
 */
class AjaxController extends AbstractActionController
{

    /**
     *
     */
    public function getCidadePorUfAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        $arrCidade = $this->getServiceLocator()->get('cidade_table')->getCidadeByUf($this->getRequest()->getPost()->get('cd_uf'));
        echo json_encode($arrCidade);
        exit;
    }

    public function getHistoricoMovimentacaoAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        $arr = $this->getServiceLocator()->get('tipo_movimento_caixa_table')->listaTipoMovimentoCaixa($this->getRequest()->getPost()->get('stcredeb'));
        echo json_encode($arr);
        exit;
    }
    
    public function getClassificacaoFinanceiraAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        $arr = $this->getServiceLocator()->get('classificacao_financeira_table')->listaClassiFinanceira($this->getRequest()->getPost()->get('stcredeb'));
        echo json_encode($arr);
        exit;
    }

    public function getOperadorLojaAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        $arr = $this->getServiceLocator()->get('functionario')->getListaFuncionarioLoja($this->getRequest()->getPost()->get('cdLoja'));
        echo json_encode($arr);
        exit;
    }
}
