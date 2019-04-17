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
class relatorioVendasMensalTipoPagamentoController  extends RelatorioController
{
		
	/**
	 *
	 */
	public function pesquisaAction()
	{
		//self::validaAcessoGerente();
	
		$view = new ViewModel();
		$view->setTemplate("application/relatorio/vendasMensalTipoPagamento/pesquisa.phtml");
	
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
    	$sql 								= " SELECT 	ANO,
										                CD_TIPO_PAGAMENTO,
										                DS_TIPO_PAGAMENTO,
										                Vendas01 = Sum( MES01 ),
										                Vendas02 = Sum( MES02 ),
										                Vendas03 = Sum( MES03 ),
										                Vendas04 = Sum( MES04 ),
										                Vendas05 = Sum( MES05 ),
										                Vendas06 = Sum( MES06 ),
										                Vendas07 = Sum( MES07 ),
										                Vendas08 = Sum( MES08 ),
										                Vendas09 = Sum( MES09 ),
										                Vendas10 = Sum( MES10 ),
										                Vendas11 = Sum( MES11 ),
										                Vendas12 = Sum( MES12 ),
										                TotalAnual = Sum( Mes01 ) + Sum( Mes02 ) + Sum( Mes03 ) + Sum( Mes04 ) + Sum( Mes05 ) + Sum( Mes06 ) +
										                             Sum( Mes07 ) + Sum( Mes08 ) + Sum( Mes09 ) + Sum( Mes10 ) + Sum( Mes11 ) + Sum( Mes12 )  
										           FROM ( SELECT ANO = YEAR( cxp.DT_EMISSAO ),
										                         MES = MONTH( cxp.DT_EMISSAO ),
										                         CXP.CD_TIPO_PAGAMENTO,
										                         TPG.DS_Tipo_Pagamento,
										                         Mes01 = Sum( IsNull( case WHEN MONTH( cxp.DT_EMISSAO ) = 01 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes02 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 02 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes03 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 03 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes04 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 04 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes05 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 05 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes06 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 06 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes07 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 07 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes08 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 08 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes09 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 09 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes10 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 10 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes11 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 11 THEN VL_DOCUMENTO END, 0 )  ),
										                         Mes12 = Sum( IsNull( Case WHEN MONTH( cxp.DT_EMISSAO ) = 12 THEN VL_DOCUMENTO END, 0 )  ) 
										                    FROM TB_CAIXA_PAGAMENTO CXP
										                         LEFT JOIN TB_TIPO_PAGAMENTO TPG ON CXP.CD_TIPO_PAGAMENTO = TPG.CD_TIPO_PAGAMENTO
										                   WHERE 
																CXP.CD_LOJA 			= ?
																AND YEAR( DT_EMISSAO )  = ?
										                        AND CXP.CD_TIPO_PAGAMENTO IN ( VAR_TIPO_PAGAMENTO )
										                        AND IsNull( CXP.ST_CANCELADO, 'N' ) <> 'S'
										                  GROUP BY 
    															cxp.DT_EMISSAO, 
												    			TPG.DS_Tipo_Pagamento, 
												    			CXP.CD_TIPO_PAGAMENTO
										                 ) PAGAMENTOS
										         GROUP BY 
    													ANO, 
										    			CD_TIPO_PAGAMENTO, 
										    			DS_TIPO_PAGAMENTO
    			 									ORDER BY
    													ANO, 
										    			CD_TIPO_PAGAMENTO";
    	
    	
    	if($this->params()->fromQuery('pdf') != true)
    	{
    		// get post data
    		$post   	   		= $this->getRequest()->getPost();
    		$anoEmissao  		= $post->get('anoEmissao');
    		$tiposPagamento  	= $post->get('tiposPagamento');
    		$tiposPagamento     = $tiposPagamento == "" ? "1,2,3,4,5,6,7,8,9,10,11,12": trim($post->get('tiposPagamento'));
    		
    		$sql			 = str_replace("VAR_TIPO_PAGAMENTO", $tiposPagamento, $sql);
    		$statementList   = $dbAdapter->query($sql);
    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$results 			= $statementList->execute(array($session->cdLoja,$anoEmissao));
    		 
    		$viewModel = new ViewModel();
    		$viewModel->setTerminal(true);
    		$viewModel->setVariable('anoEmissao',$anoEmissao);
    		$viewModel->setVariable('tiposPagamento',$tiposPagamento);
    		$viewModel->setVariable('lista',$results);
    		$viewModel->setVariable('cdLoja',$session->cdLoja);
    		$viewModel->setVariable('dsLoja',$session->dsLoja);
    		$viewModel->setVariable('logo','<img src="/img/logo-orange-small.png" alt="logotipo"/>');
    		$viewModel->setVariable('dataAtual',date("d/m/Y"));
    		$viewModel->setVariable('horaAtual',date("h:i:s"));
    		$viewModel->setTemplate("application/relatorio/vendasMensalTipoPagamento/relatorio.phtml");
    		return $viewModel;
    	}
    	else
    	{
    		//get url data
    		$anoEmissao  		= $this->params()->fromQuery('anoEmissao');
    		$tiposPagamento  	= $this->params()->fromQuery('tiposPagamento');
    		$tiposPagamento  	= $this->params()->fromQuery('tiposPagamento');
    		$tiposPagamento  	= $tiposPagamento=="" ? "1,2,3,4,5,6,7,8,9,10,11,12" : trim($this->params()->fromQuery('tiposPagamento'));
    	
    		$sql			 = str_replace("VAR_TIPO_PAGAMENTO", $tiposPagamento, $sql);
    		$statementList   = $dbAdapter->query($sql);
    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$results 		= $statementList->execute(array($session->cdLoja,$anoEmissao));
    	
    		$pdf = new PdfModel();
    		$pdf->setOption('filename', 'relatorio-vendas-mensal-tipo-pagamento'); // Triggers PDF download, automatically appends ".pdf"
    		$pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
    		$pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"
    	
    		// To set view variables
    		$pdf->setVariables(array(
    				'dataAtual' => date("d/m/Y"),
    				'horaAtual' => date("h:i:s"),
    				'logo' => '<img src="'.realpath(__DIR__.'/../../../../../public/img').'/logo-orange-small.png" alt="logo"  />',
    				'anoEmissao'=>$anoEmissao,
    				'tiposPagamento'=>$tiposPagamento,
    				'lista'=>$results,
    				'cdLoja',$session->cdLoja,
    				'dsLoja',$session->dsLoja
    		));
    		
    		$pdf->setTemplate("application/relatorio/vendasMensalTipoPagamento/relatorio.phtml");
    	
    		return $pdf;
    	}
    	
    }
    /**
     *
     * @return \Zend\View\Model\ViewModel|\DOMPDFModule\View\Model\PdfModel
     */
    public function appAction()
    {
        die(json_encode("teste"));
    }
    
}