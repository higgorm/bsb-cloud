<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Application\Model\RelatorioAgendamentoTable;
use Application\Model\RelatorioAtendimentoTable;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use DOMPDFModule\View\Model\PdfModel;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;


/**
 *
 * @author ANDRE LUIZ GERALDI
 *
 */
class RelatorioAgendamentoController extends RelatorioController
{

	/**
	 *
	 */
	public function pesquisaAction()
	{
		self::validaAcessoGerente();

		$view = new ViewModel();
		$view->setTemplate("application/relatorio/agendamento/pesquisa.phtml");

		return $view;
	}

	    /**
     *
     * @return \Zend\View\Model\ViewModel|\DOMPDFModule\View\Model\PdfModel
     */
    public function relatorioAction()
    {
    	//get session
    	$session 	= new Container("orangeSessionContainer");

    	// get the db adapter
    	$sm 		= $this->getServiceLocator();
    	$dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');
    	$relatorio  = new RelatorioAgendamentoTable($dbAdapter);

    	if($this->params()->fromQuery('pdf') != true)
    	{
	    		// get post data
	    		$post   	   = $this->getRequest()->getPost();
	    		$dataDeInicio  = $post->get('dtInicio');
	    		$dataDeTermino = $post->get('dtTermino');
	    		$situacao      = $post->get('situacao');
                $cliente       = $post->get('cd_cliente');

	    		$return 		= $relatorio->pesquisa($session->cdLoja,$dataDeInicio,$dataDeTermino,$situacao,$cliente);

	    		$viewModel = new ViewModel();
		    	$viewModel->setTerminal(true);
		    	$viewModel->setVariable('dataInicial',$dataDeInicio);
		    	$viewModel->setVariable('dataFinal',$dataDeTermino);
		    	$viewModel->setVariable('rdbSituacao',$situacao);
		    	$viewModel->setVariable('lista',$return);
		    	$viewModel->setVariable('dsLoja',$session->dsLoja);
		    	$viewModel->setVariable('logo','<img src="/img/logo-orange-small.png" alt="logotipo"/>');
		    	$viewModel->setVariable('dataAtual',date("d/m/Y"));
		    	$viewModel->setVariable('horaAtual',date("h:i:s"));
		    	$viewModel->setTemplate("application/relatorio/agendamento/relatorio.phtml");
		    	return $viewModel;
    	}
    	else
    	{
	    		// get url data
	    		$dataDeInicio  = $this->params()->fromQuery('dtInicio');
	    		$dataDeTermino = $this->params()->fromQuery('dtTermino');
	    		$situacao      = $this->params()->fromQuery('situacao');
                $cliente       = $this->params()->fromQuery('cd_cliente');

    	 		$pdf = new PdfModel();
		    	$pdf->setOption('filename', 'relatorio'); // Triggers PDF download, automatically appends ".pdf"
		    	$pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
		    	$pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"

		    	/* @var $results Zend\Db\ResultSet\ResultSet */
                $return 		= $relatorio->pesquisa($session->cdLoja,$dataDeInicio,$dataDeTermino,$situacao,$cliente);
		    	// To set view variables
		    	$pdf->setVariables(array(
		    			'dsLoja'=>$session->dsLoja,
		    			'dataAtual' => date("d/m/Y"),
		    			'horaAtual' => date("h:i:s"),
		    			'logo' => '<img src="'.realpath(__DIR__.'/../../../../../public/img').'/logo-orange-small.png" alt="logo"  />',
		    			'dataInicial'=>$dataDeInicio,
		    			'dataFinal'=>$dataDeTermino,
		    			'rdbSituacao'=>$situacao,
		    			'lista'=>$return
		    	));

		    	$pdf->setTemplate("application/relatorio/agendamento/relatorio.phtml");

		    	return $pdf;

    	}
    }

}