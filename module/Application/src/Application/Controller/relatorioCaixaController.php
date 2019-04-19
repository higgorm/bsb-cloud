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
 * @author André Luiz Geraldi <andregeraldi@gmail.com>
 */
class RelatorioCaixaController extends RelatorioController
{

    public function getTable($strService)
    {
        $sm = $this->getServiceLocator();
        $this->table = $sm->get($strService);

        return $this->table;
    }

	public function pesquisaAction()
	{
		// get the db adapter
		$sm 		= $this->getServiceLocator();
		$dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');

		//get session
		$session 	= new Container("orangeSessionContainer");

		//$this->validaAcessoGerente();

		$statement = $dbAdapter->query("SELECT DISTINCT B.CD_FUNCIONARIO,
										       B.DS_FUNCIONARIO
										  FROM TB_CAIXA_FUNCIONARIO A
										       INNER JOIN TB_FUNCIONARIO B  ON ( A.CD_LOJA = B.CD_LOJA AND A.CD_FUNCIONARIO = B.CD_FUNCIONARIO )
										       INNER JOIN TB_PARAMEMPRESA C ON A.CD_LOJA = C.CD_LOJA
										       INNER JOIN TB_LOJA D         ON A.CD_LOJA = D.CD_LOJA
										 WHERE A.CD_LOJA    = ?
										    ");

		/* @var $resultOperador Zend\Db\ResultSet\ResultSet */
		$resultOperador	= $statement->execute(array($session->cdLoja));

		$viewModel = new ViewModel();
		$viewModel->setVariable('listaOperador',$resultOperador);
		$viewModel->setTemplate("application/relatorio/caixa/pesquisa.phtml");
		return $viewModel;
	}

	/**
	 *
	 */
	public function filtroAction()
	{
		$post   	   = $this->getRequest()->getPost();
		$dataDeInicio  = $post->get('dtCaixaInicial');
		$dataDeTermino = $post->get('dtCaixaFinal');
		$stCaixa 	   = $post->get('stCaixa');
		$cdOperador	   = $post->get('cdFuncionario');

		// get the db adapter
		$sm 		= $this->getServiceLocator();
		$dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');

		//get session
		$session 	= new Container("orangeSessionContainer");

		//$this->validaAcessoGerente();

		$sqlSummy = "SELECT COUNT(*) AS NR_MOVIMENTOS
		                  FROM TB_CAIXA_FUNCIONARIO A
		                       INNER JOIN TB_FUNCIONARIO B  ON ( A.CD_LOJA = B.CD_LOJA AND A.CD_FUNCIONARIO = B.CD_FUNCIONARIO )
		                       INNER JOIN TB_PARAMEMPRESA C ON A.CD_LOJA = C.CD_LOJA
		                       INNER JOIN TB_LOJA D         ON A.CD_LOJA = D.CD_LOJA
		                 WHERE A.CD_LOJA    = ".$session->cdLoja." ";

		$sqlList = "SELECT A.*,
						   B.CD_FUNCIONARIO,
	                       B.DS_FUNCIONARIO,
	                       D.DS_RAZAO_SOCIAL,
						   DT_HORA_ENTRADA_FTD  = CONVERT(VARCHAR(10),A.DT_HORA_ENTRADA,103),
				 		   DT_HORA_SAIDA_FTD    = CONVERT(VARCHAR(10),A.DT_HORA_SAIDA,103),
				 		   HORA_ENTRADA_FTD 	= CONVERT(VARCHAR(10),A.DT_HORA_ENTRADA,108),
				 		   HORA_SAIDA_FTD   	= CONVERT(VARCHAR(10),A.DT_HORA_SAIDA,108),
				 		   DT_ENTRADA_FTD  		= CONVERT(VARCHAR(10),A.DT_ENTRADA,103)
	                  FROM TB_CAIXA_FUNCIONARIO A
	                       INNER JOIN TB_FUNCIONARIO B  ON ( A.CD_LOJA = B.CD_LOJA AND A.CD_FUNCIONARIO = B.CD_FUNCIONARIO )
	                       INNER JOIN TB_PARAMEMPRESA C ON A.CD_LOJA = C.CD_LOJA
	                       INNER JOIN TB_LOJA D         ON A.CD_LOJA = D.CD_LOJA
	                 WHERE A.CD_LOJA    = ".$session->cdLoja." ";


		$sqlSummy .=" AND A.DT_ENTRADA  BETWEEN '".date('Ymd', strtotime($dataDeInicio))."' AND '".date('Ymd', strtotime($dataDeTermino))."' ";
		$sqlList  .=" AND A.DT_ENTRADA  BETWEEN '".date('Ymd', strtotime($dataDeInicio))."' AND '".date('Ymd', strtotime($dataDeTermino))."' ";


		if($stCaixa=="A")
		{
			$sqlSummy .=" AND A.DT_SAIDA IS NULL ";
			$sqlList  .=" AND A.DT_SAIDA IS NULL ";
		}
		else
		{
			$sqlSummy .=" AND A.DT_SAIDA IS NOT NULL ";
			$sqlList  .=" AND A.DT_SAIDA IS NOT NULL ";
		}

		if((int)$cdOperador>=1)
		{
			$sqlSummy .=" AND A.CD_FUNCIONARIO=".$cdOperador;
			$sqlList  .=" AND A.CD_FUNCIONARIO=".$cdOperador;
		}

		$statementSummy = $dbAdapter->query($sqlSummy);
		$statement 		= $dbAdapter->query($sqlList);

		/* @var $summy Zend\Db\ResultSet\ResultSet */
		$totalMovimentos = $statementSummy->execute();

		/* @var $resultOperador Zend\Db\ResultSet\ResultSet */
		$result	= $statement->execute();

		$viewModel = new ViewModel();
		$viewModel->setTerminal(true);
		$viewModel->setVariable('listaCaixaMovimento',$result);
		$viewModel->setVariable('total',$totalMovimentos->current());
		$viewModel->setTemplate("application/relatorio/caixa/filtro.phtml");
		return $viewModel;
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


    	//Calcula pedido posterior recebido
    	$sqlSummyPedidoPosterior="SELECT
    							TOTAL           = ISNULL( SUM( VL_TOTAL_LIQUIDO), 0 ),
						    	TOTALFRETE      = ISNULL( SUM( VL_FRETE + NR_SUBSTITUICAO ), 0 ),
						    	TOTALP1RECEBIDO = ISNULL( SUM( TOTALP1RECEBIDO ), 0 ),
						    	FRETEP1         = ISNULL( SUM( CASE
													    	WHEN ISNULL( VL_TOTAL_LIQUIDO, 0 ) = 0 THEN 0
													    	ELSE VL_FRETE * TOTALP1RECEBIDO / VL_TOTAL_LIQUIDO
													    	END ), 0 ),
						    	TOTALFECHAMENTO = ISNULL( SUM( TOTALFECHAMENTO ), 0 ),
						    	FRETEFECHAMENTO = ISNULL( SUM( CASE
														    	WHEN ISNULL( VL_TOTAL_LIQUIDO, 0 ) = 0 THEN 0
														    	ELSE VL_FRETE * TOTALFECHAMENTO / VL_TOTAL_LIQUIDO
														    	END ), 0 )
    						FROM (
							    	SELECT 	NR_PEDIDO,
    									  	VL_TOTAL_LIQUIDO,
    									  	VL_FRETE, NR_SUBSTITUICAO,
								    		TotalP1Recebido = Sum( IsNull( TotalP1Recebido, 0 ) ),
								    		TotalFechamento = Sum( IsNull( TotalFechamento, 0 ) )
								    from (
									    	SELECT distinct P.NR_PEDIDO, P.VL_TOTAL_LIQUIDO, P.VL_FRETE, P.NR_SUBSTITUICAO,
									    	TOTALP1RECEBIDO = ( SELECT Sum( pg.VL_DOCUMENTO )
														    	FROM TB_PEDIDO_PAGAMENTO PG
														    	INNER JOIN TB_CAIXA_PAGAMENTO CXP
														    	ON PG.CD_LOJA = CXP.CD_LOJA AND
														    	PG.NR_PARCELA = CXP.NR_PARCELA AND
														    	PG.CD_TIPO_PAGAMENTO = CXP.CD_TIPO_PAGAMENTO AND
														    	CXP.NR_CAIXA = CP.NR_CAIXA AND
														    	CXP.NR_LANCAMENTO_CAIXA = CP.NR_LANCAMENTO_CAIXA
														    	WHERE     PG.CD_LOJA = P.CD_LOJA
														    	AND PG.NR_PEDIDO = P.NR_PEDIDO
														    	AND PG.ST_ACORDOP1 = '0' ),
    									TOTALFECHAMENTO = ( SELECT Sum( pg.VL_DOCUMENTO )
													    	FROM TB_PEDIDO_PAGAMENTO PG
													    	INNER JOIN TB_CAIXA_PAGAMENTO CXP
													    	ON PG.CD_LOJA = CXP.CD_LOJA AND
													    	PG.NR_PARCELA = CXP.NR_PARCELA AND
													    	PG.CD_TIPO_PAGAMENTO = CXP.CD_TIPO_PAGAMENTO AND
													    	CXP.NR_CAIXA = CP.NR_CAIXA AND
													    	CXP.NR_LANCAMENTO_CAIXA = CP.NR_LANCAMENTO_CAIXA
													    	WHERE     PG.CD_LOJA = P.CD_LOJA
													    	AND PG.NR_PEDIDO = P.NR_PEDIDO
													    	AND PG.ST_ACORDOP1 = '1' )
    					FROM TB_PEDIDO P
    					INNER JOIN RL_CAIXA_PEDIDO CP
											    	ON     CP.CD_LOJA   = P.CD_LOJA
											    	AND CP.NR_PEDIDO = P.NR_PEDIDO
    					INNER JOIN TB_CAIXA A
											    	ON     A.CD_LOJA  = CP.CD_LOJA
											    	AND A.NR_CAIXA = CP.NR_CAIXA
											    	AND A.NR_LANCAMENTO_CAIXA = CP.NR_LANCAMENTO_CAIXA
											    	AND ( A.ST_CANCELADO <> 'S' )
    					WHERE     ( A.CD_LOJA   = ? )
							    	AND ( A.NR_CAIXA  = ? )
							    	AND ( P.ST_PEDIDO = 'F')
							    	AND ( P.CD_TIPO_PEDIDO = 2 )
							    	AND ( Convert( varchar( 10 ), P.DT_RECEBIMENTO, 103 ) = CONVERT(VARCHAR(10),?,103) )
							    	AND ( A.DT_MOVIMENTO = CONVERT(VARCHAR(10),?,103))
    				) GRUPO
    			GROUP BY
    				NR_PEDIDO, VL_TOTAL_LIQUIDO, VL_FRETE, NR_SUBSTITUICAO
    	) A";

    	// Calculo detalhamento de cart�o de cr�dito
    	$sqlSummyDetCartaoCredito=" SELECT VALOR = SUM (VENDAS)
									FROM (
										SELECT CD_TIPO_PAGAMENTO, DS_TIPO_PAGAMENTO, CD_CARTAO, DS_CARTAO, ST_DEBITO_CREDITO, VENDAS = CAST (SUM (ISNULL (VL_DOCUMENTO, 0)) AS NUMERIC (20, 2))
										FROM (
											SELECT	CXP.CD_TIPO_PAGAMENTO,
													TP.DS_TIPO_PAGAMENTO,
													CXP.CD_CARTAO,
													DS_CARTAO = CASE WHEN C.CD_CARTAO IS NULL THEN 'Nao Especificado' ELSE C.DS_CARTAO END ,
													ST_DEBITO_CREDITO = ISNULL (ST_DEBITO_CREDITO, 'C'),
													VL_DOCUMENTO
											FROM TB_CAIXA_PAGAMENTO CXP
											LEFT JOIN TB_TIPO_PAGAMENTO TP ON CXP.CD_TIPO_PAGAMENTO = TP.CD_TIPO_PAGAMENTO
											LEFT JOIN TB_CARTAO C ON CXP.CD_CARTAO = C.CD_CARTAO
											WHERE CD_LOJA = ?
												AND NR_CAIXA =?
												AND ISNULL (ST_CANCELADO, 'N') <> 'S'
												AND CXP.CD_TIPO_PAGAMENTO IN (5, 11, 12)
												AND DT_EMISSAO =  CONVERT(VARCHAR(10),?,103)
											) RESUMOCARTAO
										GROUP BY CD_TIPO_PAGAMENTO, DS_TIPO_PAGAMENTO, CD_CARTAO, DS_CARTAO, ST_DEBITO_CREDITO
										) AS RESUMO
									WHERE ST_DEBITO_CREDITO = 'C'
									GROUP BY ST_DEBITO_CREDITO
    							";

    	// Calculo detalhamento de cart�o de d�bito
    	$sqlSummyDetCartaoDebito=" SELECT VALOR = SUM (VENDAS)
									FROM (
										SELECT CD_TIPO_PAGAMENTO, DS_TIPO_PAGAMENTO, CD_CARTAO, DS_CARTAO, ST_DEBITO_CREDITO, VENDAS = CAST (SUM (ISNULL (VL_DOCUMENTO, 0)) AS NUMERIC (20, 2))
										FROM (
											SELECT	CXP.CD_TIPO_PAGAMENTO,
													TP.DS_TIPO_PAGAMENTO,
													CXP.CD_CARTAO,
													DS_CARTAO = CASE WHEN C.CD_CARTAO IS NULL THEN 'Nao Especificado' ELSE C.DS_CARTAO END ,
													ST_DEBITO_CREDITO = ISNULL (ST_DEBITO_CREDITO, 'C'),
													VL_DOCUMENTO
											FROM TB_CAIXA_PAGAMENTO CXP
											LEFT JOIN TB_TIPO_PAGAMENTO TP ON CXP.CD_TIPO_PAGAMENTO = TP.CD_TIPO_PAGAMENTO
											LEFT JOIN TB_CARTAO C ON CXP.CD_CARTAO = C.CD_CARTAO
											WHERE CD_LOJA = ?
												AND NR_CAIXA =?
												AND ISNULL (ST_CANCELADO, 'N') <> 'S'
												AND CXP.CD_TIPO_PAGAMENTO IN (5, 11, 12)
												AND DT_EMISSAO =  CONVERT(VARCHAR(10),?,103)
											) RESUMOCARTAO
										GROUP BY CD_TIPO_PAGAMENTO, DS_TIPO_PAGAMENTO, CD_CARTAO, DS_CARTAO, ST_DEBITO_CREDITO
										) AS RESUMO
									WHERE ST_DEBITO_CREDITO = 'D'
									GROUP BY ST_DEBITO_CREDITO
    							";

    	$sqlList   = "    SELECT DISTINCT
							     CX.CD_LOJA,
							     LJ.DS_RAZAO_SOCIAL,
							     CX.NR_CAIXA,
							     CFN.CD_FUNCIONARIO,
							     FUN.DS_FUNCIONARIO,
							     CFN.DT_ENTRADA,
							     CFN.DT_SAIDA,
							     CFN.dt_hora_entrada,
							     CFN.dt_hora_saida,
							     CX.CD_TIPO_MOVIMENTO_CAIXA,
							     TMV.DS_TIPO_MOVIMENTO_CAIXA,
							     CX.DS_COMPL_MOVIMENTO,
							     VL_TOTAL_BRUTO   = CAST( CX.VL_TOTAL_BRUTO   AS NUMERIC( 17, 2 ) ),
							     VL_TOTAL_LIQUIDO = CAST( CX.VL_TOTAL_LIQUIDO AS NUMERIC( 17, 2 ) ),
							     CX.DT_MOVIMENTO,
							     CX.NR_LANCAMENTO_CAIXA,
						     TemPedidoOuOS = CASE WHEN RCP.NR_PEDIDO IS NULL AND ROP.CD_ORDEM_SERVICO IS NULL THEN 0 ELSE 1 END
						FROM TB_TIPO_MOVIMENTO_CAIXA TMV
						LEFT JOIN TB_CAIXA CX            ON CX.CD_TIPO_MOVIMENTO_CAIXA = TMV.CD_TIPO_MOVIMENTO_CAIXA
						LEFT JOIN TB_LOJA            LJ  ON LJ.CD_LOJA             = CX.CD_LOJA
						INNER JOIN TB_CAIXA_FUNCIONARIO CFN ON CFN.CD_LOJA      = CX.CD_LOJA
						                                   AND CFN.NR_CAIXA = CX.NR_CAIXA
						                                   AND CX.DT_MOVIMENTO BETWEEN CFN.DT_ENTRADA AND ( ISNULL( CFN.DT_SAIDA, CFN.DT_ENTRADA ) )
						LEFT JOIN TB_FUNCIONARIO       FUN ON FUN.CD_LOJA = CFN.CD_LOJA
						                                   AND FUN.CD_FUNCIONARIO = CFN.CD_FUNCIONARIO
						LEFT JOIN RL_CAIXA_PEDIDO      RCP ON     RCP.CD_LOJA  = CX.CD_LOJA
						                                   AND RCP.NR_CAIXA = CX.NR_CAIXA
						                                   AND RCP.NR_LANCAMENTO_CAIXA = CX.NR_LANCAMENTO_CAIXA
						LEFT JOIN RL_OS_CAIXA          ROP ON     ROP.CD_LOJA  = CX.CD_LOJA
						                                   AND ROP.NR_CAIXA = CX.NR_CAIXA
						                                   AND ROP.NR_LANCAMENTO_CAIXA = CX.NR_LANCAMENTO_CAIXA
						WHERE
								 CX.CD_LOJA		= ?
    			  			 AND CFN.DT_ENTRADA = CONVERT(VARCHAR(10),?,103)
							 AND CX.NR_CAIXA	= ?
							 AND CX.ST_CANCELADO <> 'S'

    			";

    	$sqlSummyCabecalho  = "SELECT DISTINCT
										     CFN.dt_hora_entrada,
    										 DT_ENTRADA	  = CONVERT(VARCHAR(10),CFN.DT_ENTRADA,103),
    										 HORA_ENTRADA = CONVERT(VARCHAR(10),cfn.dt_hora_entrada,108),
										     CFN.dt_hora_saida,
    										 HORA_SAIDA    = CONVERT(VARCHAR(10),cfn.dt_hora_saida,108),
										     CFN.CD_FUNCIONARIO,
										     FUN.DS_FUNCIONARIO,
										     VL_TOTAL_BRUTO   = SUM(CAST( CX.VL_TOTAL_BRUTO   AS NUMERIC( 17, 2 ))),
										     VL_TOTAL_LIQUIDO = SUM(CAST( CX.VL_TOTAL_LIQUIDO AS NUMERIC( 17, 2 )))
										FROM TB_TIPO_MOVIMENTO_CAIXA TMV
										LEFT JOIN TB_CAIXA CX            ON CX.CD_TIPO_MOVIMENTO_CAIXA = TMV.CD_TIPO_MOVIMENTO_CAIXA
										LEFT JOIN TB_LOJA            LJ  ON LJ.CD_LOJA             = CX.CD_LOJA
										INNER JOIN TB_CAIXA_FUNCIONARIO CFN ON CFN.CD_LOJA      = CX.CD_LOJA
										                                   AND CFN.NR_CAIXA = CX.NR_CAIXA
										                                   AND CX.DT_MOVIMENTO BETWEEN CFN.DT_ENTRADA AND ( ISNULL( CFN.DT_SAIDA, CFN.DT_ENTRADA ) )
										LEFT JOIN TB_FUNCIONARIO       FUN ON FUN.CD_LOJA = CFN.CD_LOJA
										                                   AND FUN.CD_FUNCIONARIO = CFN.CD_FUNCIONARIO
										LEFT JOIN RL_CAIXA_PEDIDO      RCP ON     RCP.CD_LOJA  = CX.CD_LOJA
										                                   AND RCP.NR_CAIXA = CX.NR_CAIXA
										                                   AND RCP.NR_LANCAMENTO_CAIXA = CX.NR_LANCAMENTO_CAIXA
										LEFT JOIN RL_OS_CAIXA          ROP ON     ROP.CD_LOJA  = CX.CD_LOJA
										                                   AND ROP.NR_CAIXA = CX.NR_CAIXA
										                                   AND ROP.NR_LANCAMENTO_CAIXA = CX.NR_LANCAMENTO_CAIXA
										WHERE
												 CX.CD_LOJA		= ?
										     AND CFN.DT_ENTRADA = CONVERT(VARCHAR(10),?,103)
    			 							 AND CX.NR_CAIXA	= ?
										     AND CX.ST_CANCELADO <> 'S'
										";

    	if((int)$cdFuncionario >=1)
    	{
    		$sqlList 		  .=" AND CFN.CD_FUNCIONARIO = $cdFuncionario ";
    		$sqlSummyCabecalho.=" AND CFN.CD_FUNCIONARIO = $cdFuncionario ";
    	}


    	$sqlList .=" ORDER BY
				    	CASE WHEN RCP.NR_PEDIDO IS NULL AND ROP.CD_ORDEM_SERVICO IS NULL THEN 0 ELSE 1 END,
				    	CX.CD_TIPO_MOVIMENTO_CAIXA,
				    	CX.NR_LANCAMENTO_CAIXA";

    	$sqlSummyCabecalho.="GROUP BY
    						CFN.DT_ENTRADA,
							CFN.dt_hora_entrada,
							CFN.dt_hora_saida,
							CFN.CD_FUNCIONARIO,
							FUN.DS_FUNCIONARIO ";

    	if($this->params()->fromQuery('pdf') != true)
    	{
    		// get post data
    		$post   	   		= $this->getRequest()->getPost();
    		$dataCaixa  		= $post->get('dtEntrada');
    		$dataRecebimento 	= $dataCaixa;
    		$numeroCaixa 		= $post->get('cdCaixa');
    		$cdLoja 			= $post->get('cdLoja');
    		$cdFuncionario 		= $post->get('cdFuncionario');
    		$dsFuncionario 		= $post->get('dsFuncionario');
    		$stCaixa			= $post->get('stCaixa');

    		$stList	 			 			= $dbAdapter->query($sqlList);
    		$stSummy  			 			= $dbAdapter->query($sqlSummyCabecalho);
    		$stSummyPosteriorRecebido		= $dbAdapter->query($sqlSummyPedidoPosterior);
    		$stSummyDetCartaoCredito		= $dbAdapter->query($sqlSummyDetCartaoCredito);
    		$stSummyDetCartaoDebito			= $dbAdapter->query($sqlSummyDetCartaoDebito);


    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		//$results 						= $stList->execute(array($session->cdLoja,$dataCaixa,$numeroCaixa));
    		$resultSummy					= $stSummy->execute(array($session->cdLoja,$dataCaixa,$numeroCaixa));
            $resultDetCartaoCredito			= $stSummyDetCartaoCredito->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa));
            $resultDetCartaoDebito			= $stSummyDetCartaoDebito->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa));

    		$resultSummyCheque				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyChequeAvista		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque-avista', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyChequePreDatado		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque-pre', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyEspecie				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('dinheiro', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyBoleto				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('boleto', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyFinanceira			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('financeira', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyCartao				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyCartaoParcelado		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao-parcelado', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyCartaoManual		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao-manual', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyCartaCredito		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('carta-credito', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyDevolucao			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('devolucao', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyTicket				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('ticket', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyDeposito			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('deposito', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummyEntrada				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('entrada', $session->cdLoja, $numeroCaixa,$dataCaixa);
    		$resultSummySaida				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('saida', $session->cdLoja, $numeroCaixa,$dataCaixa);


    		//INFO ADICIONAIS
    		$resultSummyPosteriorRecebido   = $stSummyPosteriorRecebido->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa,$dataRecebimento));


    		$viewModel = new ViewModel();
    		$viewModel->setTerminal(true);
    		$viewModel->setVariable('dataCaixa',$dataCaixa);
    		$viewModel->setVariable('numeroCaixa',$numeroCaixa);
    		//$viewModel->setVariable('lista',$results);
    		$viewModel->setVariable('total',$resultSummy->current());
    		$viewModel->setVariable('totalCheque',$resultSummyCheque->current());
    		$viewModel->setVariable('totalChequeAVista',$resultSummyChequeAvista->current());
    		$viewModel->setVariable('totalChequePreDatado',$resultSummyChequePreDatado->current());
    		$viewModel->setVariable('totalEspecie',$resultSummyEspecie->current());
    		$viewModel->setVariable('totalBoleto',$resultSummyBoleto->current());
    		$viewModel->setVariable('totalFinanceira',$resultSummyFinanceira->current());
    		$viewModel->setVariable('totalCartao',$resultSummyCartao->current());
    		$viewModel->setVariable('totalCartaoParcelado',$resultSummyCartaoParcelado->current());
    		$viewModel->setVariable('totalCartaoManual',$resultSummyCartaoManual->current());
    		$viewModel->setVariable('totalCartaCredito',$resultSummyCartaCredito->current());
    		$viewModel->setVariable('totalDevolucao',$resultSummyDevolucao->current());
    		$viewModel->setVariable('totalTicket',$resultSummyTicket->current());
    		$viewModel->setVariable('totalDeposito',$resultSummyDeposito->current());
    		$viewModel->setVariable('totalEntrada',$resultSummyEntrada->current());
    		$viewModel->setVariable('totalSaida',$resultSummySaida->current());
    		$viewModel->setVariable('totalPosteriorRecebido',$resultSummyPosteriorRecebido->current());
    		$viewModel->setVariable('totalCartaoCredito',$resultDetCartaoCredito->current());
    		$viewModel->setVariable('totalCartaoDebito',$resultDetCartaoDebito->current());
    		$viewModel->setVariable('cdLoja',$session->cdLoja);
    		$viewModel->setVariable('dsLoja',$session->dsLoja);
    		$viewModel->setVariable('logo','<img src="/img/logo-orange-small.png" alt="logotipo"/>');
    		$viewModel->setVariable('dataAtual',date("d/m/Y"));
    		$viewModel->setVariable('horaAtual',date("h:i:s"));
    		$viewModel->setTemplate("application/relatorio/caixa/relatorio.phtml");

    		return $viewModel;
    	}
    	else
    	{
    		// get url data
    		$dataCaixa  		= $this->params()->fromQuery('pdf_dtEntrada');
    		$dataRecebimento 	= $dataCaixa;
    		$numeroCaixa 		= $this->params()->fromQuery('pdf_cdCaixa');
    		$cdLoja 			= $this->params()->fromQuery('pdf_cdLoja');
    		$cdFuncionario 		= $this->params()->fromQuery('pdf_cdFuncionario');
    		$dsFuncionario 		= $this->params()->fromQuery('pdf_dsFuncionario');
    		$stCaixa			= $this->params()->fromQuery('pdf_stCaixa');

    		$pdf = new PdfModel();
    		$pdf->setOption('filename', 'relatorio-caixa-diario'); // Triggers PDF download, automatically appends ".pdf"
    		$pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
    		$pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"

    		$stList	 			 			= $dbAdapter->query($sqlList);
    		$stSummy  			 			= $dbAdapter->query($sqlSummyCabecalho);
    		$stSummyPosteriorRecebido		= $dbAdapter->query($sqlSummyPedidoPosterior);
    		$stSummyDetCartaoCredito		= $dbAdapter->query($sqlSummyDetCartaoCredito);
    		$stSummyDetCartaoDebito			= $dbAdapter->query($sqlSummyDetCartaoDebito);


    		/* @var $results Zend\Db\ResultSet\ResultSet */
    		$results 						= $stList->execute(array($session->cdLoja,$dataCaixa,$numeroCaixa));
    		$resultSummy					= $stSummy->execute(array($session->cdLoja,$dataCaixa,$numeroCaixa));
            $resultSummyCheque				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyChequeAvista		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque-avista', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyChequePreDatado		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque-pre', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyEspecie				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('dinheiro', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyBoleto				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('boleto', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyFinanceira			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('financeira', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartao				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartaoParcelado		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao-parcelado', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartaoManual		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao-manual', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartaCredito		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('carta-credito', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyDevolucao			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('devolucao', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyTicket				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('ticket', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyDeposito			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('deposito', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyEntrada				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('entrada', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummySaida				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('saida', $session->cdLoja, $numeroCaixa,$dataCaixa);

    		//INFO ADICIONAIS
    		$resultSummyPosteriorRecebido   = $stSummyPosteriorRecebido->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa,$dataRecebimento));

    		$resultDetCartaoCredito			= $stSummyDetCartaoCredito->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa));
    		$resultDetCartaoDebito			= $stSummyDetCartaoDebito->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa));

    		// To set view variables
            $pdf->setVariable('dataCaixa',$dataCaixa);
            $pdf->setVariable('numeroCaixa',$numeroCaixa);
            $pdf->setVariable('lista',$results);
            $pdf->setVariable('total',$resultSummy->current());
            $pdf->setVariable('totalCheque',$resultSummyCheque->current());
            $pdf->setVariable('totalChequeAVista',$resultSummyChequeAvista->current());
            $pdf->setVariable('totalChequePreDatado',$resultSummyChequePreDatado->current());
            $pdf->setVariable('totalEspecie',$resultSummyEspecie->current());
            $pdf->setVariable('totalBoleto',$resultSummyBoleto->current());
            $pdf->setVariable('totalFinanceira',$resultSummyFinanceira->current());
            $pdf->setVariable('totalCartao',$resultSummyCartao->current());
            $pdf->setVariable('totalCartaoParcelado',$resultSummyCartaoParcelado->current());
            $pdf->setVariable('totalCartaoManual',$resultSummyCartaoManual->current());
            $pdf->setVariable('totalCartaCredito',$resultSummyCartaCredito->current());
            $pdf->setVariable('totalDevolucao',$resultSummyDevolucao->current());
            $pdf->setVariable('totalTicket',$resultSummyTicket->current());
            $pdf->setVariable('totalDeposito',$resultSummyDeposito->current());
            $pdf->setVariable('totalEntrada',$resultSummyEntrada->current());
            $pdf->setVariable('totalSaida',$resultSummySaida->current());
            $pdf->setVariable('totalPosteriorRecebido',$resultSummyPosteriorRecebido->current());
            $pdf->setVariable('totalCartaoCredito',$resultDetCartaoCredito->current());
            $pdf->setVariable('totalCartaoDebito',$resultDetCartaoDebito->current());
            $pdf->setVariable('cdLoja',$session->cdLoja);
            $pdf->setVariable('dsLoja',$session->dsLoja);
            $pdf->setVariable('logo','<img src="/img/logo-orange-small.png" alt="logotipo"/>');
            $pdf->setVariable('dataAtual',date("d/m/Y"));
            $pdf->setVariable('horaAtual',date("h:i:s"));

    		$pdf->setTemplate("application/relatorio/caixa/relatorio.phtml");

    		return $pdf;
    	}


    }
    public function relatorioDetalhadoAction()
    {
        //get session
        $session 	= new Container("orangeSessionContainer");

        // get the db adapter
        $sm 		= $this->getServiceLocator();
        $dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');

        //Calcula pedido posterior recebido
        $sqlSummyPedidoPosterior="SELECT
    							TOTAL           = ISNULL( SUM( VL_TOTAL_LIQUIDO), 0 ),
						    	TOTALFRETE      = ISNULL( SUM( VL_FRETE + NR_SUBSTITUICAO ), 0 ),
						    	TOTALP1RECEBIDO = ISNULL( SUM( TOTALP1RECEBIDO ), 0 ),
						    	FRETEP1         = ISNULL( SUM( CASE
													    	WHEN ISNULL( VL_TOTAL_LIQUIDO, 0 ) = 0 THEN 0
													    	ELSE VL_FRETE * TOTALP1RECEBIDO / VL_TOTAL_LIQUIDO
													    	END ), 0 ),
						    	TOTALFECHAMENTO = ISNULL( SUM( TOTALFECHAMENTO ), 0 ),
						    	FRETEFECHAMENTO = ISNULL( SUM( CASE
														    	WHEN ISNULL( VL_TOTAL_LIQUIDO, 0 ) = 0 THEN 0
														    	ELSE VL_FRETE * TOTALFECHAMENTO / VL_TOTAL_LIQUIDO
														    	END ), 0 )
    						FROM (
							    	SELECT 	NR_PEDIDO,
    									  	VL_TOTAL_LIQUIDO,
    									  	VL_FRETE, NR_SUBSTITUICAO,
								    		TotalP1Recebido = Sum( IsNull( TotalP1Recebido, 0 ) ),
								    		TotalFechamento = Sum( IsNull( TotalFechamento, 0 ) )
								    from (
									    	SELECT distinct P.NR_PEDIDO, P.VL_TOTAL_LIQUIDO, P.VL_FRETE, P.NR_SUBSTITUICAO,
									    	TOTALP1RECEBIDO = ( SELECT Sum( pg.VL_DOCUMENTO )
														    	FROM TB_PEDIDO_PAGAMENTO PG
														    	INNER JOIN TB_CAIXA_PAGAMENTO CXP
														    	ON PG.CD_LOJA = CXP.CD_LOJA AND
														    	PG.NR_PARCELA = CXP.NR_PARCELA AND
														    	PG.CD_TIPO_PAGAMENTO = CXP.CD_TIPO_PAGAMENTO AND
														    	CXP.NR_CAIXA = CP.NR_CAIXA AND
														    	CXP.NR_LANCAMENTO_CAIXA = CP.NR_LANCAMENTO_CAIXA
														    	WHERE     PG.CD_LOJA = P.CD_LOJA
														    	AND PG.NR_PEDIDO = P.NR_PEDIDO
														    	AND PG.ST_ACORDOP1 = '0' ),
    									TOTALFECHAMENTO = ( SELECT Sum( pg.VL_DOCUMENTO )
													    	FROM TB_PEDIDO_PAGAMENTO PG
													    	INNER JOIN TB_CAIXA_PAGAMENTO CXP
													    	ON PG.CD_LOJA = CXP.CD_LOJA AND
													    	PG.NR_PARCELA = CXP.NR_PARCELA AND
													    	PG.CD_TIPO_PAGAMENTO = CXP.CD_TIPO_PAGAMENTO AND
													    	CXP.NR_CAIXA = CP.NR_CAIXA AND
													    	CXP.NR_LANCAMENTO_CAIXA = CP.NR_LANCAMENTO_CAIXA
													    	WHERE     PG.CD_LOJA = P.CD_LOJA
													    	AND PG.NR_PEDIDO = P.NR_PEDIDO
													    	AND PG.ST_ACORDOP1 = '1' )
    					FROM TB_PEDIDO P
    					INNER JOIN RL_CAIXA_PEDIDO CP
											    	ON     CP.CD_LOJA   = P.CD_LOJA
											    	AND CP.NR_PEDIDO = P.NR_PEDIDO
    					INNER JOIN TB_CAIXA A
											    	ON     A.CD_LOJA  = CP.CD_LOJA
											    	AND A.NR_CAIXA = CP.NR_CAIXA
											    	AND A.NR_LANCAMENTO_CAIXA = CP.NR_LANCAMENTO_CAIXA
											    	AND ( A.ST_CANCELADO <> 'S' )
    					WHERE     ( A.CD_LOJA   = ? )
							    	AND ( A.NR_CAIXA  = ? )
							    	AND ( P.ST_PEDIDO = 'F')
							    	AND ( P.CD_TIPO_PEDIDO = 2 )
							    	AND ( Convert( varchar( 10 ), P.DT_RECEBIMENTO, 103 ) = CONVERT(VARCHAR(10),?,103) )
							    	AND ( A.DT_MOVIMENTO = CONVERT(VARCHAR(10),?,103))
    				) GRUPO
    			GROUP BY
    				NR_PEDIDO, VL_TOTAL_LIQUIDO, VL_FRETE, NR_SUBSTITUICAO
    	) A";

        // Calculo detalhamento de cart�o de cr�dito
        $sqlSummyDetCartaoCredito=" SELECT VALOR = SUM (VENDAS)
									FROM (
										SELECT CD_TIPO_PAGAMENTO, DS_TIPO_PAGAMENTO, CD_CARTAO, DS_CARTAO, ST_DEBITO_CREDITO, VENDAS = CAST (SUM (ISNULL (VL_DOCUMENTO, 0)) AS NUMERIC (20, 2))
										FROM (
											SELECT	CXP.CD_TIPO_PAGAMENTO,
													TP.DS_TIPO_PAGAMENTO,
													CXP.CD_CARTAO,
													DS_CARTAO = CASE WHEN C.CD_CARTAO IS NULL THEN 'Nao Especificado' ELSE C.DS_CARTAO END ,
													ST_DEBITO_CREDITO = ISNULL (ST_DEBITO_CREDITO, 'C'),
													VL_DOCUMENTO
											FROM TB_CAIXA_PAGAMENTO CXP
											LEFT JOIN TB_TIPO_PAGAMENTO TP ON CXP.CD_TIPO_PAGAMENTO = TP.CD_TIPO_PAGAMENTO
											LEFT JOIN TB_CARTAO C ON CXP.CD_CARTAO = C.CD_CARTAO
											WHERE CD_LOJA = ?
												AND NR_CAIXA =?
												AND ISNULL (ST_CANCELADO, 'N') <> 'S'
												AND CXP.CD_TIPO_PAGAMENTO IN (5, 11, 12)
												AND DT_EMISSAO =  CONVERT(VARCHAR(10),?,103)
											) RESUMOCARTAO
										GROUP BY CD_TIPO_PAGAMENTO, DS_TIPO_PAGAMENTO, CD_CARTAO, DS_CARTAO, ST_DEBITO_CREDITO
										) AS RESUMO
									WHERE ST_DEBITO_CREDITO = 'C'
									GROUP BY ST_DEBITO_CREDITO
    							";

        // Calculo detalhamento de cart�o de d�bito
        $sqlSummyDetCartaoDebito=" SELECT VALOR = SUM (VENDAS)
									FROM (
										SELECT CD_TIPO_PAGAMENTO, DS_TIPO_PAGAMENTO, CD_CARTAO, DS_CARTAO, ST_DEBITO_CREDITO, VENDAS = CAST (SUM (ISNULL (VL_DOCUMENTO, 0)) AS NUMERIC (20, 2))
										FROM (
											SELECT	CXP.CD_TIPO_PAGAMENTO,
													TP.DS_TIPO_PAGAMENTO,
													CXP.CD_CARTAO,
													DS_CARTAO = CASE WHEN C.CD_CARTAO IS NULL THEN 'Nao Especificado' ELSE C.DS_CARTAO END ,
													ST_DEBITO_CREDITO = ISNULL (ST_DEBITO_CREDITO, 'C'),
													VL_DOCUMENTO
											FROM TB_CAIXA_PAGAMENTO CXP
											LEFT JOIN TB_TIPO_PAGAMENTO TP ON CXP.CD_TIPO_PAGAMENTO = TP.CD_TIPO_PAGAMENTO
											LEFT JOIN TB_CARTAO C ON CXP.CD_CARTAO = C.CD_CARTAO
											WHERE CD_LOJA = ?
												AND NR_CAIXA =?
												AND ISNULL (ST_CANCELADO, 'N') <> 'S'
												AND CXP.CD_TIPO_PAGAMENTO IN (5, 11, 12)
												AND DT_EMISSAO =  CONVERT(VARCHAR(10),?,103)
											) RESUMOCARTAO
										GROUP BY CD_TIPO_PAGAMENTO, DS_TIPO_PAGAMENTO, CD_CARTAO, DS_CARTAO, ST_DEBITO_CREDITO
										) AS RESUMO
									WHERE ST_DEBITO_CREDITO = 'D'
									GROUP BY ST_DEBITO_CREDITO
    							";

        $sqlList   = "    SELECT DISTINCT
							     CX.CD_LOJA,
							     LJ.DS_RAZAO_SOCIAL,
							     CX.NR_CAIXA,
							     CFN.CD_FUNCIONARIO,
							     FUN.DS_FUNCIONARIO,
							     CFN.DT_ENTRADA,
							     CFN.DT_SAIDA,
							     CFN.dt_hora_entrada,
							     CFN.dt_hora_saida,
							     CX.CD_TIPO_MOVIMENTO_CAIXA,
							     TMV.DS_TIPO_MOVIMENTO_CAIXA,
							     CX.DS_COMPL_MOVIMENTO,
							     VL_TOTAL_BRUTO   = CAST( CX.VL_TOTAL_BRUTO   AS NUMERIC( 17, 2 ) ),
							     VL_TOTAL_LIQUIDO = CAST( CX.VL_TOTAL_LIQUIDO AS NUMERIC( 17, 2 ) ),
							     CX.DT_MOVIMENTO,
							     CX.NR_LANCAMENTO_CAIXA,
						     TemPedidoOuOS = CASE WHEN RCP.NR_PEDIDO IS NULL AND ROP.CD_ORDEM_SERVICO IS NULL THEN 0 ELSE 1 END
						FROM TB_TIPO_MOVIMENTO_CAIXA TMV
						LEFT JOIN TB_CAIXA CX            ON CX.CD_TIPO_MOVIMENTO_CAIXA = TMV.CD_TIPO_MOVIMENTO_CAIXA
						LEFT JOIN TB_LOJA            LJ  ON LJ.CD_LOJA             = CX.CD_LOJA
						INNER JOIN TB_CAIXA_FUNCIONARIO CFN ON CFN.CD_LOJA      = CX.CD_LOJA
						                                   AND CFN.NR_CAIXA = CX.NR_CAIXA
						                                   AND CX.DT_MOVIMENTO BETWEEN CFN.DT_ENTRADA AND ( ISNULL( CFN.DT_SAIDA, CFN.DT_ENTRADA ) )
						LEFT JOIN TB_FUNCIONARIO       FUN ON FUN.CD_LOJA = CFN.CD_LOJA
						                                   AND FUN.CD_FUNCIONARIO = CFN.CD_FUNCIONARIO
						LEFT JOIN RL_CAIXA_PEDIDO      RCP ON     RCP.CD_LOJA  = CX.CD_LOJA
						                                   AND RCP.NR_CAIXA = CX.NR_CAIXA
						                                   AND RCP.NR_LANCAMENTO_CAIXA = CX.NR_LANCAMENTO_CAIXA
						LEFT JOIN RL_OS_CAIXA          ROP ON     ROP.CD_LOJA  = CX.CD_LOJA
						                                   AND ROP.NR_CAIXA = CX.NR_CAIXA
						                                   AND ROP.NR_LANCAMENTO_CAIXA = CX.NR_LANCAMENTO_CAIXA
						WHERE
								 CX.CD_LOJA		= ?
    			  			 AND CFN.DT_ENTRADA = CONVERT(VARCHAR(10),?,103)
							 AND CX.NR_CAIXA	= ?
							 AND CX.ST_CANCELADO <> 'S'

    			";

        $sqlSummyCabecalho  = "SELECT DISTINCT
										     CFN.dt_hora_entrada,
    										 DT_ENTRADA	  = CONVERT(VARCHAR(10),CFN.DT_ENTRADA,103),
    										 HORA_ENTRADA = CONVERT(VARCHAR(10),cfn.dt_hora_entrada,108),
										     CFN.dt_hora_saida,
    										 HORA_SAIDA    = CONVERT(VARCHAR(10),cfn.dt_hora_saida,108),
										     CFN.CD_FUNCIONARIO,
										     FUN.DS_FUNCIONARIO,
										     VL_TOTAL_BRUTO   = SUM(CAST( CX.VL_TOTAL_BRUTO   AS NUMERIC( 17, 2 ))),
										     VL_TOTAL_LIQUIDO = SUM(CAST( CX.VL_TOTAL_LIQUIDO AS NUMERIC( 17, 2 )))
										FROM TB_TIPO_MOVIMENTO_CAIXA TMV
										LEFT JOIN TB_CAIXA CX            ON CX.CD_TIPO_MOVIMENTO_CAIXA = TMV.CD_TIPO_MOVIMENTO_CAIXA
										LEFT JOIN TB_LOJA            LJ  ON LJ.CD_LOJA             = CX.CD_LOJA
										INNER JOIN TB_CAIXA_FUNCIONARIO CFN ON CFN.CD_LOJA      = CX.CD_LOJA
										                                   AND CFN.NR_CAIXA = CX.NR_CAIXA
										                                   AND CX.DT_MOVIMENTO BETWEEN CFN.DT_ENTRADA AND ( ISNULL( CFN.DT_SAIDA, CFN.DT_ENTRADA ) )
										LEFT JOIN TB_FUNCIONARIO       FUN ON FUN.CD_LOJA = CFN.CD_LOJA
										                                   AND FUN.CD_FUNCIONARIO = CFN.CD_FUNCIONARIO
										LEFT JOIN RL_CAIXA_PEDIDO      RCP ON     RCP.CD_LOJA  = CX.CD_LOJA
										                                   AND RCP.NR_CAIXA = CX.NR_CAIXA
										                                   AND RCP.NR_LANCAMENTO_CAIXA = CX.NR_LANCAMENTO_CAIXA
										LEFT JOIN RL_OS_CAIXA          ROP ON     ROP.CD_LOJA  = CX.CD_LOJA
										                                   AND ROP.NR_CAIXA = CX.NR_CAIXA
										                                   AND ROP.NR_LANCAMENTO_CAIXA = CX.NR_LANCAMENTO_CAIXA
										WHERE
												 CX.CD_LOJA		= ?
										     AND CFN.DT_ENTRADA = CONVERT(VARCHAR(10),?,103)
    			 							 AND CX.NR_CAIXA	= ?
										     AND CX.ST_CANCELADO <> 'S'
										";

        $sqlList .=" ORDER BY
				    	CASE WHEN RCP.NR_PEDIDO IS NULL AND ROP.CD_ORDEM_SERVICO IS NULL THEN 0 ELSE 1 END,
				    	CX.CD_TIPO_MOVIMENTO_CAIXA,
				    	CX.NR_LANCAMENTO_CAIXA";

        $sqlSummyCabecalho.="GROUP BY
    						CFN.DT_ENTRADA,
							CFN.dt_hora_entrada,
							CFN.dt_hora_saida,
							CFN.CD_FUNCIONARIO,
							FUN.DS_FUNCIONARIO ";

        if($this->params()->fromQuery('pdf') != true)
        {
            // get post data
            $post   	   		= $this->getRequest()->getPost();
            $dataCaixa  		= $post->get('dtEntrada');
            $dataRecebimento 	= $dataCaixa;
            $numeroCaixa 		= $post->get('cdCaixa');
            $cdLoja 			= $post->get('cdLoja');
            $cdFuncionario 		= $post->get('cdFuncionario');
            $dsFuncionario 		= $post->get('dsFuncionario');
            $stCaixa			= $post->get('stCaixa');


            $stList	 			 			= $dbAdapter->query($sqlList);
            $stSummy  			 			= $dbAdapter->query($sqlSummyCabecalho);
            $stSummyPosteriorRecebido		= $dbAdapter->query($sqlSummyPedidoPosterior);
            $stSummyDetCartaoCredito		= $dbAdapter->query($sqlSummyDetCartaoCredito);
            $stSummyDetCartaoDebito			= $dbAdapter->query($sqlSummyDetCartaoDebito);

            /* @var $results Zend\Db\ResultSet\ResultSet */
            //$results 						= $stList->execute(array($session->cdLoja,$dataCaixa,$numeroCaixa));
            $resultSummy					= $stSummy->execute(array($session->cdLoja,$dataCaixa,$numeroCaixa));
            $resultDetCartaoCredito			= $stSummyDetCartaoCredito->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa));
            $resultDetCartaoDebito			= $stSummyDetCartaoDebito->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa));

            $resultSummyCheque				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyChequeAvista		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque-avista', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyChequePreDatado		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque-pre', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyEspecie				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('dinheiro', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyBoleto				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('boleto', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyFinanceira			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('financeira', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartao				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartaoParcelado		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao-parcelado', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartaoManual		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao-manual', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartaCredito		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('carta-credito', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyDevolucao			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('devolucao', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyTicket				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('ticket', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyDeposito			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('deposito', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyEntrada				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('entrada', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummySaida				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('saida', $session->cdLoja, $numeroCaixa,$dataCaixa);


            //INFO ADICIONAIS
            $resultSummyPosteriorRecebido   = $stSummyPosteriorRecebido->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa,$dataRecebimento));


            $viewModel = new ViewModel();
            $viewModel->setTerminal(true);
            $viewModel->setVariable('dataCaixa',$dataCaixa);
            $viewModel->setVariable('numeroCaixa',$numeroCaixa);
            //$viewModel->setVariable('lista',$results);
            $viewModel->setVariable('total',$resultSummy->current());
            $viewModel->setVariable('totalCheque',$resultSummyCheque->current());
            $viewModel->setVariable('totalChequeAVista',$resultSummyChequeAvista->current());
            $viewModel->setVariable('totalChequePreDatado',$resultSummyChequePreDatado->current());
            $viewModel->setVariable('totalEspecie',$resultSummyEspecie->current());
            $viewModel->setVariable('totalBoleto',$resultSummyBoleto->current());
            $viewModel->setVariable('totalFinanceira',$resultSummyFinanceira->current());
            $viewModel->setVariable('totalCartao',$resultSummyCartao->current());
            $viewModel->setVariable('totalCartaoParcelado',$resultSummyCartaoParcelado->current());
            $viewModel->setVariable('totalCartaoManual',$resultSummyCartaoManual->current());
            $viewModel->setVariable('totalCartaCredito',$resultSummyCartaCredito->current());
            $viewModel->setVariable('totalDevolucao',$resultSummyDevolucao->current());
            $viewModel->setVariable('totalTicket',$resultSummyTicket->current());
            $viewModel->setVariable('totalDeposito',$resultSummyDeposito->current());
            $viewModel->setVariable('totalEntrada',$resultSummyEntrada->current());
            $viewModel->setVariable('totalSaida',$resultSummySaida->current());
            $viewModel->setVariable('totalPosteriorRecebido',$resultSummyPosteriorRecebido->current());
            $viewModel->setVariable('totalCartaoCredito',$resultDetCartaoCredito->current());
            $viewModel->setVariable('totalCartaoDebito',$resultDetCartaoDebito->current());
            $viewModel->setVariable('cdLoja',$session->cdLoja);
            $viewModel->setVariable('dsLoja',$session->dsLoja);
            $viewModel->setVariable('logo','<img src="/img/logo-orange-small.png" alt="logotipo"/>');
            $viewModel->setVariable('dataAtual',date("d/m/Y"));
            $viewModel->setVariable('horaAtual',date("h:i:s"));
            $viewModel->setVariable('detalhamento',$this->getTable('caixa-table')->detalhamentoCaixa($session->cdLoja,$numeroCaixa,$dataCaixa));
            $viewModel->setTemplate("application/relatorio/caixa/relatorio.phtml");

            return $viewModel;
        }
        else
        {
            // get url data
            $dataCaixa  		= $this->params()->fromQuery('pdf_dtEntrada');
            $dataRecebimento 	= $dataCaixa;
            $numeroCaixa 		= $this->params()->fromQuery('pdf_cdCaixa');
            $cdLoja 			= $this->params()->fromQuery('pdf_cdLoja');
            $cdFuncionario 		= $this->params()->fromQuery('pdf_cdFuncionario');
            $dsFuncionario 		= $this->params()->fromQuery('pdf_dsFuncionario');
            $stCaixa			= $this->params()->fromQuery('pdf_stCaixa');

            $pdf = new PdfModel();
            $pdf->setOption('filename', 'relatorio-caixa-diario-detalhado'); // Triggers PDF download, automatically appends ".pdf"
            $pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
            $pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"

            $stList	 			 			= $dbAdapter->query($sqlList);
            $stSummy  			 			= $dbAdapter->query($sqlSummyCabecalho);
            $stSummyPosteriorRecebido		= $dbAdapter->query($sqlSummyPedidoPosterior);
            $stSummyDetCartaoCredito		= $dbAdapter->query($sqlSummyDetCartaoCredito);
            $stSummyDetCartaoDebito			= $dbAdapter->query($sqlSummyDetCartaoDebito);


            /* @var $results Zend\Db\ResultSet\ResultSet */
            $results 						= $stList->execute(array($session->cdLoja,$dataCaixa,$numeroCaixa));
            $resultSummy					= $stSummy->execute(array($session->cdLoja,$dataCaixa,$numeroCaixa));
            $resultSummyCheque				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyChequeAvista		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque-avista', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyChequePreDatado		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cheque-pre', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyEspecie				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('dinheiro', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyBoleto				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('boleto', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyFinanceira			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('financeira', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartao				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartaoParcelado		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao-parcelado', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartaoManual		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('cartao-manual', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyCartaCredito		= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('carta-credito', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyDevolucao			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('devolucao', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyTicket				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('ticket', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyDeposito			= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('deposito', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummyEntrada				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('entrada', $session->cdLoja, $numeroCaixa,$dataCaixa);
            $resultSummySaida				= $this->getTable('caixa_table')->calculaValorTotalPorCaixa('saida', $session->cdLoja, $numeroCaixa,$dataCaixa);

            //INFO ADICIONAIS
            $resultSummyPosteriorRecebido   = $stSummyPosteriorRecebido->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa,$dataRecebimento));

            $resultDetCartaoCredito			= $stSummyDetCartaoCredito->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa));
            $resultDetCartaoDebito			= $stSummyDetCartaoDebito->execute(array($session->cdLoja,$numeroCaixa,$dataCaixa));

            // To set view variables
            $pdf->setVariable('dataCaixa',$dataCaixa);
            $pdf->setVariable('numeroCaixa',$numeroCaixa);
            $pdf->setVariable('lista',$results);
            $pdf->setVariable('total',$resultSummy->current());
            $pdf->setVariable('totalCheque',$resultSummyCheque->current());
            $pdf->setVariable('totalChequeAVista',$resultSummyChequeAvista->current());
            $pdf->setVariable('totalChequePreDatado',$resultSummyChequePreDatado->current());
            $pdf->setVariable('totalEspecie',$resultSummyEspecie->current());
            $pdf->setVariable('totalBoleto',$resultSummyBoleto->current());
            $pdf->setVariable('totalFinanceira',$resultSummyFinanceira->current());
            $pdf->setVariable('totalCartao',$resultSummyCartao->current());
            $pdf->setVariable('totalCartaoParcelado',$resultSummyCartaoParcelado->current());
            $pdf->setVariable('totalCartaoManual',$resultSummyCartaoManual->current());
            $pdf->setVariable('totalCartaCredito',$resultSummyCartaCredito->current());
            $pdf->setVariable('totalDevolucao',$resultSummyDevolucao->current());
            $pdf->setVariable('totalTicket',$resultSummyTicket->current());
            $pdf->setVariable('totalDeposito',$resultSummyDeposito->current());
            $pdf->setVariable('totalEntrada',$resultSummyEntrada->current());
            $pdf->setVariable('totalSaida',$resultSummySaida->current());
            $pdf->setVariable('totalPosteriorRecebido',$resultSummyPosteriorRecebido->current());
            $pdf->setVariable('totalCartaoCredito',$resultDetCartaoCredito->current());
            $pdf->setVariable('totalCartaoDebito',$resultDetCartaoDebito->current());
            $pdf->setVariable('cdLoja',$session->cdLoja);
            $pdf->setVariable('dsLoja',$session->dsLoja);
            $pdf->setVariable('logo','<img src="/img/logo-orange-small.png" alt="logotipo"/>');
            $pdf->setVariable('dataAtual',date("d/m/Y"));
            $pdf->setVariable('horaAtual',date("h:i:s"));
            $pdf->setVariable('detalhamento',$this->getTable('caixa-table')->detalhamentoCaixa($session->cdLoja,$numeroCaixa,$dataCaixa));

            $pdf->setTemplate("application/relatorio/caixa/relatorio.phtml");

            return $pdf;
        }
    }
}