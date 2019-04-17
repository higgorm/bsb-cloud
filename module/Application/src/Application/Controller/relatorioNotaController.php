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
 * @author HIGOR
 *
 */
class relatorioNotaController  extends RelatorioController
{

	/**
	 *
	 */
	public function pesquisaAction()
	{
		//self::validaAcessoGerente();

		$view = new ViewModel();
		$view->setTemplate("application/relatorio/nota/pesquisa.phtml");

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

    	//query
    	$sql 		= " SELECT * FROM TB_NFE WHERE 1 = 1 ";


    	if($this->params()->fromQuery('pdf') != true)
    	{
			$post   	   		= $this->getRequest()->getPost();
			$dataInicio			= $post->get('dt_inicio');
			$dataFim			= $post->get('dt_fim');
			$infNfe				= $post->get('infNfe');
			$status				= $post->get('status');

			if( $dataInicio	!= '' && $dataFim != '' )
				$sql = $sql." AND dEmi BETWEEN '".date('Ymd',strtotime($dataInicio))."' AND '".date('Ymd',strtotime($dataFim))."'";
			if( $infNfe != '' )
				$sql = $sql.' AND infNFE = '.$infNfe;
			if( $status == 'E' )
				$sql = $sql." AND DS_PROTOCOLO IS NOT NULL AND DS_CANCELA_PROTOCOLO IS NULL ";
			if( $status == 'N' )
				$sql = $sql." AND DS_PROTOCOLO IS NULL ";
			if( $status == 'C' )
				$sql = $sql." AND DS_PROTOCOLO IS NOT NULL AND DS_CANCELA_PROTOCOLO IS NOT NULL ";

    		$statementList   = $dbAdapter->query($sql);
    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$results 			= $statementList->execute();

    		$viewModel = new ViewModel();
    		$viewModel->setTerminal(true);
    		$viewModel->setVariable('lista',$results);
            $viewModel->setVariable('logo','<img src="/img/logo-orange-small.png" alt="logotipo"/>');
            $viewModel->setVariable('dataAtual',date("d/m/Y"));
            $viewModel->setVariable('horaAtual',date("h:i:s"));
    		$viewModel->setTemplate("application/relatorio/nota/relatorio.phtml");
    		return $viewModel;
    	}
    	else
    	{
			$dataInicio  		= $this->params()->fromQuery('dt_inicio');
			$dataFim			= $this->params()->fromQuery('dt_fim');
			$nrPedido			= $this->params()->fromQuery('nr_pedido');
			$status				= $this->params()->fromQuery('status');

			if( $dataInicio	!= '' && $dataFim != '' )
				$sql = $sql." AND dEmi BETWEEN '".date('Ymd',strtotime($dataInicio))."' AND '".date('Ymd',strtotime($dataFim))."'";
			if( $infNfe != '' )
				$sql = $sql.' AND infNFE = '.$infNfe;
			if( $status == 'E' )
				$sql = $sql." AND DS_PROTOCOLO IS NOT NULL AND DS_CANCELA_PROTOCOLO IS NULL ";
			if( $status == 'N' )
				$sql = $sql." AND DS_PROTOCOLO IS NULL ";
			if( $status == 'C' )
				$sql = $sql." AND DS_PROTOCOLO IS NOT NULL AND DS_CANCELA_PROTOCOLO IS NOT NULL ";

    		$statementList   = $dbAdapter->query($sql);
    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$results 		= $statementList->execute();

    		$pdf = new PdfModel();
    		$pdf->setOption('filename', 'lista_notas'); // Triggers PDF download, automatically appends ".pdf"
    		$pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
    		$pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"

    		// To set view variables
    		$pdf->setVariables(array(
                'dataAtual' => date("d/m/Y"),
                'horaAtual' => date("h:i:s"),
                'logo' => '<img src="'.realpath(__DIR__.'/../../../../../public/img').'/logo-orange-small.png" alt="logo"  />',
                'lista'=>$results
    		));

    		$pdf->setTemplate("application/relatorio/nota/relatorio.phtml");

    		return $pdf;
    	}

    }


}
