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
    	$sql 		= " SELECT * FROM
                        (
                            SELECT  
                               A.* ,
                              INUTILIZADA= CASE WHEN (A.infNfe BETWEEN NR_FAIXA_INICIAL AND NR_FAIXA_FINAL ) THEN 'S' ELSE 'N' END 
                            FROM TB_NFE A 
                            LEFT JOIN TB_NFE_INUTILIZADAS B ON A.infNfe BETWEEN NR_FAIXA_INICIAL AND NR_FAIXA_FINAL
                            WHERE 1 = 1 
                    ";


    	if($this->params()->fromQuery('pdf') != true)
    	{
			$post   	   		= $this->getRequest()->getPost();
			$dataInicio			= $post->get('dt_inicio');
			$dataFim			= $post->get('dt_fim');
			$infNfe				= $post->get('infNfe');
			$status				= $post->get('status');

			if( $dataInicio	!= '' && $dataFim != '' ) {
                $dataInicio .= " 00:00:00";
                $dataFim    .= " 23:59:59";
                $sql = $sql." AND A.dEmi BETWEEN '".date(FORMATO_ESCRITA_DATA_HORA,strtotime($dataInicio))."' AND '".date(FORMATO_ESCRITA_DATA_HORA,strtotime($dataFim))."'";
            }

			if( $infNfe != '' )
				$sql = $sql.' AND A.infNFE = '.$infNfe;
			if( $status == 'E' )
				$sql = $sql." AND A.DS_PROTOCOLO IS NOT NULL AND A.DS_CANCELA_PROTOCOLO IS NULL ";
            if( $status == 'N' )
                $sql = $sql." AND A.DS_PROTOCOLO IS NULL  ";
            if( $status == 'I' )
                $sql = $sql." AND A.infNfe BETWEEN B.NR_FAIXA_INICIAL AND B.NR_FAIXA_FINAL ";
			if( $status == 'C' )
				$sql = $sql." AND A.DS_PROTOCOLO IS NOT NULL AND A.DS_CANCELA_PROTOCOLO IS NOT NULL ";


            $sql = $sql." ) RESULTADO ";
            if ( $status == 'N' ) {
                $sql = $sql." WHERE INUTILIZADA = 'N' ";
            }

    		$statementList   = $dbAdapter->query($sql);
    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$results 			= $statementList->execute();

    		$viewModel = new ViewModel();
    		$viewModel->setTerminal(true);
    		$viewModel->setVariable('lista',$results);
            $viewModel->setVariable('logo','<img src="/img/logo-orange-small.png" alt="logotipo"/>');
            $viewModel->setVariable('dataAtual',date("d/m/Y"));
            $viewModel->setVariable('horaAtual',date("h:i:s"));
            $viewModel->setVariable('dsLoja',$session->dsLoja);
            $viewModel->setVariable('cdLoja',$session->cdLoja);
    		$viewModel->setTemplate("application/relatorio/nota/relatorio.phtml");
    		return $viewModel;
    	}
    	else
    	{
			$dataInicio  		= $this->params()->fromQuery('dt_inicio');
			$dataFim			= $this->params()->fromQuery('dt_fim');
            $infNfe			    = $this->params()->fromQuery('infNfe');
			$nrPedido			= $this->params()->fromQuery('nr_pedido');
			$status				= $this->params()->fromQuery('status');

			if( $dataInicio	!= '' && $dataFim != '' )
				$sql = $sql." AND A.dEmi BETWEEN '".date(FORMATO_ESCRITA_DATA,strtotime($dataInicio))."' AND '".date(FORMATO_ESCRITA_DATA,strtotime($dataFim))."'";
			if( $infNfe != '' )
				$sql = $sql.' AND A.infNFE = '.$infNfe;
			if( $status == 'E' )
				$sql = $sql." AND A.DS_PROTOCOLO IS NOT NULL AND A.DS_CANCELA_PROTOCOLO IS NULL ";
			if( $status == 'N' )
                $sql = $sql." AND  A.DS_PROTOCOLO IS NULL ";
			if( $status == 'C' )
				$sql = $sql." AND A.DS_PROTOCOLO IS NOT NULL AND A.DS_CANCELA_PROTOCOLO IS NOT NULL ";
            if( $status == 'I' )
                $sql = $sql." AND A.infNfe BETWEEN B.NR_FAIXA_INICIAL AND B.NR_FAIXA_FINAL ";

            $sql = $sql." ) RESULTADO ";
            if ( $status == 'N' ) {
                $sql = $sql." WHERE INUTILIZADA = 'N' ";
            }

    		$statementList   = $dbAdapter->query($sql);
    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$results 		= $statementList->execute();

    		$pdf = new PdfModel();
    		$pdf->setOption('filename', 'relatorio_notas_'.date("dmYhis")); // Triggers PDF download, automatically appends ".pdf"
    		$pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
    		$pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"

    		// To set view variables
    		$pdf->setVariables(array(
                'dataAtual' =>  date("d/m/Y"),
                'horaAtual' =>  date("h:i:s"),
                'logo'      =>  '<img src="'.realpath(__DIR__.'/../../../../../public/img').'/logo-orange-small.png" alt="logo"  />',
                'lista'     =>  $results,
                'dsLoja'    =>  $session->dsLoja,
                'cdLoja'    =>  $session->cdLoja,
    		));

    		$pdf->setTemplate("application/relatorio/nota/relatorio.phtml");

    		return $pdf;
    	}

    }


}
