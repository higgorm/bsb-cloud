<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Application\Model\RelatorioAtendimentoTable;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use DOMPDFModule\View\Model\PdfModel;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;


/**
 *
 * @author HIGOR
 *
 */
class RelatorioAtendimentoController extends RelatorioController
{

	/**
	 *
	 */
	public function pesquisaAction()
	{
		self::validaAcessoGerente();

		$view = new ViewModel();
		$view->setTemplate("application/relatorio/atendimento/pesquisa.phtml");

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
    	$relatorio  = new RelatorioAtendimentoTable($dbAdapter);

    	if($this->params()->fromQuery('pdf') != true)
    	{
	    		// get post data
	    		$post   	   = $this->getRequest()->getPost();
	    		$dataDeInicio  = $post->get('dtInicio');
	    		$dataDeTermino = $post->get('dtTermino');

	    		/* @var $results Zend\Db\ResultSet\ResultSet */
	    		$results 		= $relatorio->getLista($session->cdLoja,$dataDeInicio,$dataDeTermino);
	    		$resultSummy	= $relatorio->getTotal($session->cdLoja,$dataDeInicio,$dataDeTermino);

	    		$viewModel = new ViewModel();
		    	$viewModel->setTerminal(true);
		    	$viewModel->setVariable('dataInicial',$dataDeInicio);
		    	$viewModel->setVariable('dataFinal',$dataDeTermino);
		    	$viewModel->setVariable('lista',$results);
		    	$viewModel->setVariable('total',$resultSummy->current());
		    	$viewModel->setVariable('dsLoja',$session->dsLoja);
		    	$viewModel->setVariable('logo','<img src="/img/logo-relatorio.png" alt="logotipo"/>');
		    	$viewModel->setVariable('dataAtual',date("d/m/Y"));
		    	$viewModel->setVariable('horaAtual',date("h:i:s"));
		    	$viewModel->setTemplate("application/relatorio/atendimento/relatorio.phtml");
		    	return $viewModel;
    	}
    	else
    	{
	    		// get url data
	    		$dataDeInicio  = $this->params()->fromQuery('dtInicio');
	    		$dataDeTermino = $this->params()->fromQuery('dtTermino');

    	 		$pdf = new PdfModel();
		    	$pdf->setOption('filename', 'relatorio'); // Triggers PDF download, automatically appends ".pdf"
		    	$pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
		    	$pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"

		    	/* @var $results Zend\Db\ResultSet\ResultSet */
	    		$results 		= $relatorio->getLista($session->cdLoja,$dataDeInicio,$dataDeTermino);
	    		$resultSummy	= $relatorio->getTotal($session->cdLoja,$dataDeInicio,$dataDeTermino);

		    	// To set view variables
		    	$pdf->setVariables(array(
		    			'dsLoja'=>$session->dsLoja,
		    			'dataAtual' => date("d/m/Y"),
		    			'horaAtual' => date("h:i:s"),
		    			'logo' => '<img src="'.realpath(__DIR__.'/../../../../../public/img').'/logo-relatorio.png" alt="logo"  />',
		    			'dataInicial'=>$dataDeInicio,
		    			'dataFinal'=>$dataDeTermino,
		    			'lista'=>$results,
		    			'total'=>$resultSummy->current()
		    	));

		    	$pdf->setTemplate("application/relatorio/atendimento/relatorio.phtml");

		    	return $pdf;

    	}
    }

}