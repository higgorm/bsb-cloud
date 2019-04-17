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
class RelatorioInventarioEstoqueController  extends RelatorioController
{

	
	/**
	 *
	 */
	public function pesquisaAction()
	{
		
		self::validaAcessoGerente();
		
		// get the db adapter
		$sm 		= $this->getServiceLocator();
		$dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');
		
		//get session
		$session 	= new Container("orangeSessionContainer");
		
		$statementListFornecedor  		= $dbAdapter->query("SELECT CD_FORNECEDOR
															      	,DS_FANTASIA
															      	,SG_FORNECEDOR
															  FROM 
																TB_FORNECEDOR");
		
		$statementListTipoMercadoria  	= $dbAdapter->query("SELECT 
															       MS.CD_TIPO_MERCADORIA,
															       MS.DS_TIPO_MERCADORIA
															FROM
																TB_TIPO_MERCADORIA_SECAO MS");
		
		$statementListLivroPreco  		= $dbAdapter->query("SELECT 
														       CD_LIVRO
														   FROM 
															 RL_LIVRO_LOJA
														    WHERE 
																CD_LOJA = ?
																AND ST_ATIVO='S'");
		
		$resultFornecedor				= $statementListFornecedor->execute();
		$resultTipoMercadoria			= $statementListTipoMercadoria->execute();
		$resultLivroPreco				= $statementListLivroPreco->execute(array($session->cdLoja));
		 
		$viewModel = new ViewModel();
		$viewModel->setVariable('listaFornecedor',$resultFornecedor);
		$viewModel->setVariable('listaTipoMercadoria',$resultTipoMercadoria);
		$viewModel->setVariable('listaLivroPreco',$resultLivroPreco);
		$viewModel->setTemplate("application/relatorio/inventarioEstoque/pesquisa.phtml");

		return $viewModel;
	}
	
	
    
    /**
     *
     * @return \Zend\View\Model\ViewModel|\DOMPDFModule\View\Model\PdfModel
     */
    public function relatorioInventarioEstoqueAction()
    {
    	 
    	//get session
    	$session 	= new Container("orangeSessionContainer");
    	 
    	// get the db adapter
    	$sm 		= $this->getServiceLocator();
    	$dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');
    	 
 		//query
    	$sql 								= "Select 
    											 A.Cd_Mercadoria,
											     DescricaoMercadoria = A.Ds_Mercadoria + ' ' + IsNull( A.Ds_Referencia, ' ' ) + ' '  + IsNull( A.Ds_Modelo_Cor, ' ' ) ,
											     Ds_Grupo            = A.Cd_Grupo + ' - ' + B.Ds_Grupo,
											     Ds_SubGrupo         = A.Cd_SubGrupo + ' - ' + C.Ds_SubGrupo,
											     Ds_Unidade_Compra   = D.Ds_Unidade_Medida,
											     F.Ds_Tipo_Mercadoria,
											     NR_QTDE_ESTOQUE = ISNULL( CASE WHEN ( CD_MERCADORIA_PAI IS NULL OR CD_MERCADORIA_PAI = 0 )
											                                    THEN H.NR_QTDE_ESTOQUE
											                                    ELSE 0
											                               END, 0 ),
											     G.Ds_Unidade_Medida Ds_Unidade_Venda,
											     I.VL_Custo_Aquisicao, 
    											 I.VL_Preco_Venda, 
								    			 I.VL_Preco_Compra, 
								    			 I.VL_IPI, 
								    			 I.VL_FRETE,
											     I.VL_DESCONTO_COMPRA,
											     PRECO_CUSTO = I.VL_Preco_Compra + (I.VL_Preco_Compra*((I.VL_IPI+I.VL_FRETE - I.VL_DESCONTO_COMPRA)/100 ))
											From Tb_Mercadoria A
											     LEFT JOIN Tb_Grupo B                 ON     B.Cd_Grupo       = A.Cd_Grupo
											     LEFT JOIN Tb_SubGrupo C              ON     C.Cd_Grupo       = A.Cd_Grupo
											                                             AND C.Cd_SubGrupo    = A.Cd_SubGrupo
											     LEFT JOIN Tb_Unidade_Medida D        ON D.Cd_Unidade_Medida  = A.Cd_Unidade_Compra
											     LEFT JOIN Tb_Tipo_Mercadoria_Secao F ON A.Cd_Tipo_Mercadoria = F.Cd_Tipo_Mercadoria
											     LEFT JOIN Tb_Unidade_Medida G        ON G.Cd_Unidade_Medida  = A.Cd_Unidade_Venda
											     LEFT JOIN Tb_estoque H               ON     H.CD_Loja  = ?
											                                             AND H.CD_Mercadoria      = A.CD_Mercadoria
											     LEFT JOIN Tb_Livro_Precos I          ON     I.CD_Livro = ?
											                                             AND I.CD_Mercadoria      = A.CD_Mercadoria
											     LEFT JOIN TB_ESTOQUE E1              ON E1.CD_MERCADORIA = A.CD_MERCADORIA_PAI AND
											                                             E1.CD_LOJA = H.CD_LOJA 
    										WHERE 1=1
    												";

    	 
    	if($this->params()->fromQuery('pdf') != true)
    	{
    		// get post data
    		$post   	   	= $this->getRequest()->getPost();
    		$cdFornecedor  		= $post->get('cdFornecedor');
    		$cdTipoMercadoria  	= $post->get('cdTipoMercadoria');
    		$estoquePositivo  	= $post->get('estoquePositivo');
    		$dtInicio  			= $post->get('dtInicio');
    		$dtTermino  		= $post->get('dtTermino');
    		$nrLivroPreco  		= $post->get('nrLivroPreco');
    		$stOrdem  			= $post->get('stOrdem');
    		$estoquePositivo	= $post->get('estoquePositivo');
    		
 
    		if((int)$cdTipoMercadoria >= 1)
    		{
    			$sql .= "	AND  A.Cd_Tipo_Mercadoria =".$cdTipoMercadoria;
    		}
    		
    		if((int)$estoquePositivo == 1)
    		{
    			$sql .= "	AND  H.NR_QTDE_ESTOQUE >= 1 ";
    		}
    		
    		if($stOrdem=="G"){
    			$dsOrdem = " B.Ds_Grupo,C.Ds_SubGrupo ";
    			$dsLabelOrdem = "Grupo / SubGrupo ";
    			$sql .= "	ORDER BY ".$dsOrdem;
    		}else {
    			$dsOrdem = " DescricaoMercadoria ";
    			$dsLabelOrdem = "Produto / Serviço ";
    			$sql .= "	ORDER BY ".$dsOrdem;
    		}
    		
    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$statementList   = $dbAdapter->query($sql);
    		$results 		= $statementList->execute(array($session->cdLoja,$nrLivroPreco));
    		 
    		$viewModel = new ViewModel();
    		$viewModel->setTerminal(true);
    		$viewModel->setVariable('nrLivroPreco',$nrLivroPreco);
    		$viewModel->setVariable('cdTipoMercadoria',$cdTipoMercadoria);
    		$viewModel->setVariable('ordem',$dsLabelOrdem);
    		$viewModel->setVariable('lista',$results);
    		$viewModel->setVariable('estoquePositivo',$estoquePositivo);
    		$viewModel->setVariable('cdLoja',$session->cdLoja);
    		$viewModel->setVariable('dsLoja',$session->dsLoja);
    		$viewModel->setVariable('logo','<img src="/img/logo-orange-small.png" alt="logotipo"/>');
    		$viewModel->setVariable('dataAtual',date("d/m/Y"));
    		$viewModel->setVariable('horaAtual',date("h:i:s"));
    		$viewModel->setTemplate("application/relatorio/inventarioEstoque/relatorio.phtml");
    		return $viewModel;
    	}
    	else
    	{
    		//get url data
    		$cdFornecedor  		= $this->params()->fromQuery('cdFornecedor');
    		$cdTipoMercadoria  	= $this->params()->fromQuery('cdTipoMercadoria');
    		$estoquePositivo  	= $this->params()->fromQuery('estoquePositivo');
    		$dtInicio  			= $this->params()->fromQuery('dtInicio');
    		$dtTermino  		= $this->params()->fromQuery('dtTermino');
    		$nrLivroPreco  		= $this->params()->fromQuery('nrLivroPreco');
    		$stOrdem  			= $this->params()->fromQuery('stOrdem');
    		$estoquePositivo	= $this->params()->fromQuery('estoquePositivo');
    		
    		
    		if((int)$cdTipoMercadoria >= 1){
    			$sql .= "	AND  A.Cd_Tipo_Mercadoria =".$cdTipoMercadoria;
    		}
    		
    		if((int)$estoquePositivo == 1)
    		{
    			$sql .= "	AND  H.NR_QTDE_ESTOQUE >= 1 ";
    		}
    		
    		if($stOrdem=="G"){
    			$sql .= "	ORDER BY Ds_Grupo,Ds_SubGrupo ";
    		}else {
    			$sql .= " ORDER BY  DescricaoMercadoria ";
    		}
    		
    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$statementList   = $dbAdapter->query($sql);
    		$results 		= $statementList->execute(array($session->cdLoja,
    														$nrLivroPreco,
    														// $cdFornecedor,
    														// $estoquePositivo,
										    				// $dtInicio,
										    				// $dtTermino,
    														));
    
    		$pdf = new PdfModel();
    		$pdf->setOption('filename', 'relatorio-resumo-caixa'); // Triggers PDF download, automatically appends ".pdf"
    		$pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
    		$pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"
    
    		// To set view variables
    		$pdf->setVariables(array(
    				'dataAtual' => date("d/m/Y"),
    				'horaAtual' => date("h:i:s"),
    				'logo' => '<img src="'.realpath(__DIR__.'/../../../../../public/img').'/logo-orange-small.png" alt="logo"  />',
    				'nrLivroPreco'=>$nrLivroPreco,
    				'lista'=>$results,
    				'estoquePositivo'=>$estoquePositivo,
    				'cdLoja',$session->cdLoja,
    				'dsLoja',$session->dsLoja
    		));
    		
    		$pdf->setTemplate("application/relatorio/inventarioEstoque/relatorio.phtml");
    
    		return $pdf;
    	}
    	 
    	 
    }
    
}