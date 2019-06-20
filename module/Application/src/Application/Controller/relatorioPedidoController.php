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
class relatorioPedidoController  extends RelatorioController
{
		
	/**
	 *
	 */
	public function pesquisaAction()
	{
		//self::validaAcessoGerente();
	
		$view = new ViewModel();
		$view->setTemplate("application/relatorio/pedido/pesquisa.phtml");
	
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
    	$sql 		= " SELECT P.* ,
                               C.DS_NOME_RAZAO_SOCIAL
                        FROM TB_PEDIDO P 
                        LEFT JOIN TB_CLIENTE C ON P.CD_CLIENTE = C.CD_CLIENTE
                        WHERE P.DT_PEDIDO 
                        BETWEEN ? AND ? ";
    	
    	if($this->params()->fromQuery('pdf') != true)
    	{
			$post   	   		= $this->getRequest()->getPost();
			$dataInicio			= $post->get('dt_inicio');
			$dataFim			= $post->get('dt_fim');
			$nrPedido			= $post->get('nr_pedido');
			$status				= $post->get('status');

			if( $nrPedido != '' )
				$sql = $sql.' AND P.NR_PEDIDO = '.$nrPedido;
            if( $status != '' )
				$sql = $sql." AND P.ST_PEDIDO = '".$status."'";

    		$statementList   = $dbAdapter->query($sql);
    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$results 			= $statementList->execute(array(date(FORMATO_ESCRITA_DATA,strtotime($dataInicio)),date(FORMATO_ESCRITA_DATA,strtotime($dataFim))));
    		 
    		$viewModel = new ViewModel();
    		$viewModel->setTerminal(true);
    		$viewModel->setVariable('lista',$results);
            $viewModel->setVariable('logo','<img src="/img/logo-orange-small.png" alt="logotipo"/>');
            $viewModel->setVariable('dataAtual',date("d/m/Y"));
            $viewModel->setVariable('horaAtual',date("h:i:s"));
            $viewModel->setVariable('dsLoja',$session->dsLoja);
    		$viewModel->setTemplate("application/relatorio/pedido/relatorio.phtml");
    		return $viewModel;
    	}
    	else
    	{
			$dataInicio  		= $this->params()->fromQuery('dt_inicio');
			$dataFim			= $this->params()->fromQuery('dt_fim');
			$nrPedido			= $this->params()->fromQuery('nr_pedido');
			$status				= $this->params()->fromQuery('status');

            if( $nrPedido != '' )
                $sql = $sql.' AND P.NR_PEDIDO = '.$nrPedido;
            if( $status != '' )
                $sql = $sql." AND P.ST_PEDIDO = '".$status."'";
			
    		$statementList   = $dbAdapter->query($sql);
    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$results 		= $statementList->execute(array(date(FORMATO_ESCRITA_DATA,strtotime($dataInicio)),date(FORMATO_ESCRITA_DATA,strtotime($dataFim))));
    	
    		$pdf = new PdfModel();
    		$pdf->setOption('filename', 'relatorio_pedido'); // Triggers PDF download, automatically appends ".pdf"
    		$pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
    		$pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"
    	
    		// To set view variables
    		$pdf->setVariables(array(
                'dataAtual' =>  date("d/m/Y"),
                'horaAtual' =>  date("h:i:s"),
                'logo'      =>  '<img src="'.realpath(__DIR__.'/../../../../../public/img').'/logo-orange-small.png" alt="logo"  />',
                'lista'     =>  $results,
                'dsLoja'    =>  $session->dsLoja
    		));

            $pdf->setTemplate("application/relatorio/pedido/relatorio.phtml");
    		return $pdf;
    	}
    	
    }

    public function pesquisaMultiLojaAction()
    {
		//get session
    	$session 	= new Container("orangeSessionContainer");
    	
    	// get the db adapter
    	$sm 		= $this->getServiceLocator();
    	$dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');
    	
		$post  	= $this->getRequest()->getPost();
		$ano 	= ($post->get('ano') ? $post->get('ano') : date('Y') );
		$mes 	= ($post->get('mes') ? $post->get('mes') : date('m') );
    	//query
    	$sql 		= " SELECT	LOJA    = P.CD_LOJA,
								DIA		= CONCAT(DAY(P.DT_PEDIDO),'/',MONTH(P.DT_PEDIDO)),
								DATA	= P.DT_PEDIDO,
								TOTAL	= SUM(P.VL_TOTAL_LIQUIDO )
						FROM TB_PEDIDO P
						WHERE 
						  YEAR(P.DT_PEDIDO) = $ano AND MONTH(P.DT_PEDIDO) = '".($mes)."' 
						GROUP BY P.CD_LOJA, P.DT_PEDIDO
						ORDER BY P.CD_LOJA, P.DT_PEDIDO ASC ";
    	$statementList   	= $dbAdapter->query($sql);
    	$results 			= $statementList->execute();
		
		$sql 		= "	SELECT	TOTAL	= SUM(TOTAL),
								MEDIA	= AVG(TOTAL)
						FROM (
							SELECT	LOJA    = P.CD_LOJA,
									DIA		= P.DT_PEDIDO,
									TOTAL	= SUM(P.VL_TOTAL_LIQUIDO )
							FROM TB_PEDIDO P
							WHERE 
							  YEAR(P.DT_PEDIDO) = $ano AND MONTH(P.DT_PEDIDO) = '".($mes)."' 
							GROUP BY P.CD_LOJA, P.DT_PEDIDO
						) AS QUERY";
		$statementList   	= $dbAdapter->query($sql);
		$totais				= $statementList->execute();

        $sql 		= "	SELECT	DISTINCT  P.CD_LOJA		
							FROM TB_PEDIDO P
							WHERE 
							  YEAR(P.DT_PEDIDO) = $ano AND MONTH(P.DT_PEDIDO) = '".($mes)."' 
							GROUP BY P.CD_LOJA";
        $statementList   	= $dbAdapter->query($sql);
        $lojas				= $statementList->execute();

        //query
        $sql 		= " SELECT	LOJA    = CONCAT('Loja ',P.CD_LOJA),
								VALOR	= SUM(P.VL_TOTAL_LIQUIDO )
						FROM TB_PEDIDO P
						WHERE 
						  YEAR(P.DT_PEDIDO) = $ano AND MONTH(P.DT_PEDIDO) = '".($mes)."' 
						GROUP BY P.CD_LOJA
						ORDER BY P.CD_LOJA DESC ";
        $statementList   	= $dbAdapter->query($sql);
        $graphics			= $statementList->execute();
    		 
    	$viewModel = new ViewModel();
    	$viewModel->setVariable('lista',iterator_to_array($results));
		$viewModel->setVariable('ano',$ano);
		$viewModel->setVariable('mes',$mes);
		$viewModel->setVariable('totais',$totais);
        $viewModel->setVariable('lojas',iterator_to_array($lojas));
        $viewModel->setVariable('graficos',iterator_to_array($graphics));
    	$viewModel->setTemplate("application/relatorio/pedido/pesquisa-multi-loja.phtml");
    		
		return $viewModel;
    }

    public function detalheMultiLojaAction()
    {
        //self::validaAcessoGerente();
    
        $view = new ViewModel();
        $view->setTemplate("application/relatorio/pedido/detalhe-multi-loja.phtml");
    
        return $view;
    }
    
}