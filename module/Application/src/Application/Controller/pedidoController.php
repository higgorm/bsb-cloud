<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Authentication\AuthenticationService;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model\PedidoTable;
use Application\Model\MercadoriaTable;
use Application\Model\ClienteTable;

/**
 *
 * @author HIGOR
 *
 */
class PedidoController extends OrangeWebAbstractActionController
{

    /**
     * @return \Zend\Http\Response
     */
    public function listaTabletAction(){
        $auth = new AuthenticationService();
        $session = new Container("orangeSessionContainer");

        $identity = null;
        if (!$auth->hasIdentity()) {
            // redirect to user index page
            return $this->redirect()->toRoute('home');
        }
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function gridListaAction(){

        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');


        // get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $table     = new PedidoTable($dbAdapter);
        $session   = new Container("orangeSessionContainer");

        /* @var $summy Zend\Db\ResultSet\ResultSet */
        $summy = $table->selectCountNrPedido($session->cdLoja);


        //NOME_DO_CLIENTE_CPF = ISNULL(C.NR_CGC_CPF,' ')  + '  ' + C.DS_NOME_RAZAO_SOCIAL,
        $statement = $dbAdapter->query("SELECT
											   A.CD_LOJA,
    										   A.CD_CLIENTE,
											   A.NR_PEDIDO,
											   DT_PEDIDO = CONVERT(VARCHAR(10),A.DT_PEDIDO,103),
    										   C.NR_CGC_CPF,
    										   C.DS_NOME_RAZAO_SOCIAL as NOME_DO_CLIENTE,
										       A.VL_TOTAL_LIQUIDO,
										       a.CD_Funcionario,
										       DS_FUNCIONARIO = ( SELECT LTRIM( STR( CD_FUNCIONARIO ) ) + ' - ' + DS_FUNCIONARIO
										                           FROM TB_FUNCIONARIO
										                           WHERE CD_LOJA = A.CD_LOJA AND CD_FUNCIONARIO = A.CD_FUNCIONARIO ),
    										  A.DS_IDENTIFICA_CLIENTE,
    										  A.CD_TIPO_PEDIDO

										FROM TB_PEDIDO A
										LEFT JOIN TB_CLIENTE C ON A.CD_CLIENTE = C.CD_CLIENTE
    									INNER JOIN TB_PARAMEMPRESA PE ON PE.CD_LOJA=A.CD_LOJA
    									LEFT JOIN TB_AGENDAMENTO_FRANQUIA AG ON AG.NR_PEDIDO=A.NR_PEDIDO
										WHERE
    										PE.FLALOJADEFAULT = 'S'	AND
    										A.ST_PEDIDO   	  = 'A' AND
    										A.CD_LOJA     = ?
										ORDER BY
											A.NR_PEDIDO,
											NOME_DO_CLIENTE ");

        /* @var $results Zend\Db\ResultSet\ResultSet */
        $results = $statement->execute(array($session->cdLoja));

        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        $viewModel->setVariable('total', $summy);
        $viewModel->setVariable('pedidos', $results);
        return $viewModel;
    }

    /**
     *
     * @return \Zend\Http\Response|\Zend\View\Model\ViewModel
     */
    public function orcamentoAction(){
        $request = $this->getRequest();
        $session = new Container("orangeSessionContainer");
        $arrLoja = array('cd_loja' => $session->cdLoja,
                         'ds_loja' => utf8_encode($session->dsLoja));

        $nrPedido = $request->getPost()->get('nrPedido');

        $arrView = array(
            'arrLoja'       => $arrLoja,
            'arrPrazo'      => $this->getServiceLocator()->get('pedido_table')->listPrazo(),
            'arrMercadoria' => $this->getServiceLocator()->get('mercadoria_table')->getTiposMercadoriaSecao(),
            'arrTipoPedido' => $this->getServiceLocator()->get('tipo_pedido_table')->listTipoPedido(),
            'arrUf'         => $this->getServiceLocator()->get('uf_table')->listUf(),
            'arrOperador'   => $this->getServiceLocator()->get('functionario')->getListaFuncionarioLoja($session->cdLoja),
            'arrCliente'    => $this->getServiceLocator()->get('pedido_table')->recuperaClienteNumeroPedido($nrPedido),
            'nr_pedido'     => $nrPedido
        );

        $data       = $request->getPost();
        $viewModel  = new ViewModel($arrView);
        $terminal   = $data['modal'] == 'show' ? true : false;

        $viewModel->setTerminal($terminal);

        return $viewModel;
    }

    /**
     *
     */
    public function modalListaMercadoriaAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    /**
     *
     */
    public function modalPesquisaListaMercadoriaAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    /**
     *
     */
    public function modalPesquisaMercadoriaAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    /**
     * Realiza a verificação do numero do pedido
     * @param int $nu_pedido
     */
    public function verificaNumeroPedidoAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        // recuperando o pedido pelo numero
        $arrPedido = $this->getServiceLocator()->get('pedido_table')->recuperaPedidoPorNumero($this->getRequest()->getPost()->get('nu_pedido'));

        if (count($arrPedido)) {
            // validação do pedido
            if ($arrPedido[0]['ST_PEDIDO'] == 'F') {
                echo json_encode(array('result' => 'erro', 'message' => 'Pedido de venda já passou pelo caixa.'));
                exit;
            }

            if ($arrPedido[0]['ST_PEDIDO'] == 'A') {
                echo json_encode(array('result' => 'success', 'data' => $arrPedido[0]));
                exit;
            }
        }

        echo json_encode(array('result' => 'erro', 'message' => 'Pedido/Orçamento não cadastrado.'));
        exit;
    }

    /**
     *
     */
    public function recuperaDadosPedidoAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        $post = $this->getRequest()->getPost();

        $result = 'success';
        $message = '';

        $pedido = $this->getServiceLocator()->get('pedido_table')->recuperaPedido($post);

        // se nao retornar registros, pedido inexistente
        if (!count($pedido)) {
            echo json_encode(array('result' => 'erro', 'message' => 'Pedido/Orçamento não cadastrado.'));
            exit;
        }

        // se situacao F, pedido ja fechado
        if ($pedido['ST_PEDIDO'] == 'F') {
            //echo json_encode(array('result' => 'erro', 'message' => 'Pedido de venda já passou pelo caixa.'));
            //exit;
            $result = 'passou';
            $message = 'Pedido de venda já passou pelo caixa.';
        }

        if ($post['nu_pedido'] != '') {
            $arrMercadoriaPedido = $this->getServiceLocator()->get('mercadoria_table')->recuperaMercadoriaPedido($post['nu_pedido']);
        }

        $arrPedido = array(
            'pedido' => $pedido,
            'pedidoAnterior' => $this->getServiceLocator()->get('pedido_table')->recuperaPedidosAnterior($post),
            'ultimasCompras' => $this->getServiceLocator()->get('pedido_table')->recuperaUltimasCompras($pedido['CD_CLIENTE']),
            'historicoCliente' => $this->getServiceLocator()->get('pedido_table')->recuperaHistoricoCliente($pedido['CD_CLIENTE']),
            'mercadoriaPedido' => $arrMercadoriaPedido,
        );

        array_walk_recursive($arrPedido, function(&$item) { $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1252'); });
        echo json_encode(array('result' => $result, 'data' => $arrPedido, 'message' => $message));
        exit;
    }

    /**
     *
     */
    public function recuperaMercadoriaPorCodigoAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        $arrMercadoria = $this->getServiceLocator()->get('mercadoria_table')->getComboPrecoServico(
                $this->getRequest()->getPost()->get('cd_mercadoria'));

        if (count($arrMercadoria)) {
            echo json_encode(array('result' => 'success', 'data' => $arrMercadoria));
            exit;
        }

        echo json_encode(array('result' => 'erro', 'message' => 'Código não cadastrado.'));
        exit;
    }

    /**
     *
     */
    public function recuperaDadosMercadoriaAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);
        $request = $this->getRequest()->getPost();

        $arrMercadoria = $this->getServiceLocator()->get('mercadoria_table')->recuperaMercadoriaAtendimento(
                $request->get('cd_mercadoria'));

        if (count($arrMercadoria)) {
            $arrReturn = array();

            foreach ($arrMercadoria as $key => $val) {
                $arrReturn[$key] = $val;
            }

            $arrReturn['RETIRADA'] = $request->get('tp_retirada');
            $arrReturn['QTD'] = $request->get('qt_mercadoria');
            $arrReturn['NR_DESCONTO'] = $request->get('vl_desconto');

            $arrReturn['VL_DESCONTO'] = null;
            if ($arrReturn['NR_DESCONTO']) {
                $arrReturn['VL_DESCONTO'] = (string) ($arrReturn['VL_NOMINAL'] - ((float) $arrReturn['VL_NOMINAL'] / 100) * (int) $request->get('vl_desconto'));
                $arrReturn['VL_TOTAL'] = $arrReturn['VL_DESCONTO'] * $arrReturn['QTD'];
            } else {
                $arrReturn['VL_TOTAL'] = $arrReturn['VL_NOMINAL'] * $arrReturn['QTD'];
            }

            echo json_encode(array('result' => 'success', 'data' => $arrReturn));
            exit;
        }

        echo json_encode(array('result' => 'erro', 'message' => 'Código não cadastrado.'));
        exit;
    }

    /**
     *
     */
    public function inserePedidoOrcamentoAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        $arrMercadoria = $this->getServiceLocator()->get('mercadoria_table')->getComboPrecoServico(
                $this->getRequest()->getPost()->get('cd_mercadoria'));

        if (count($arrMercadoria)) {
            echo json_encode(array('result' => 'success', 'data' => $arrMercadoria));
            exit;
        }

        echo json_encode(array('result' => 'erro', 'message' => 'Código não cadastrado.'));
        exit;
    }

    /**
     *
     * @return \Zend\Http\Response|\Zend\View\Model\ViewModel
     */
    public function pedidoTabletAction(){

//        $auth = new AuthenticationService();
//        // get the db adapter
//        $sm = $this->getServiceLocator();
//        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
//        $mercadoriaTable = new MercadoriaTable($dbAdapter);
//
//        //get session
//        $session = new Container("orangeSessionContainer");
//
//        $identity = null;
//        if (!$auth->hasIdentity()) {
//            // redirect to user index page
//            return $this->redirect()->toRoute('home');
//        } else {
//            // get post data
//            $request = $this->getRequest();
//            $post = $request->getPost();
//
//
//            $nmCliente = $post->get('nmCliente');
//            $cdVendedor = $post->get('cdVendedor');
//            $nrLoja = $post->get('nrLoja');
//            $nrPedido = $post->get('nrPedido');
//            $tpPedido = $post->get('tpPedido');
//
//            /* @var $mercadorias Zend\Db\ResultSet\ResultSet */
//            $mercadorias = $mercadoriaTable->getComboMercadorias($session->cdLoja);
//
//            $statement = $dbAdapter->query("SELECT
//    											   A.CD_FUNCIONARIO,
//												   A.DS_FUNCIONARIO
//										    FROM
//    											  TB_FUNCIONARIO A
//    											  INNER JOIN TB_CARGO B ON A.CD_CARGO = B.CD_CARGO
//									    	WHERE
//    											A.DT_DESLIGAMENTO IS NULL 	 AND
//									    		B.ST_VENDEDOR 	= 'S'		 AND
//    											A.CD_LOJA 	    = ?
//    										ORDER BY
//    							  				A.CD_FUNCIONARIO
//										    ");
//
//            /* @var $vendedores Zend\Db\ResultSet\ResultSet */
//            $vendedores = $statement->execute(array($session->cdLoja));
//
//            $statement = $dbAdapter->query("SELECT
//												 PM.CD_MERCADORIA,
//												 PM.VL_TOTAL_BRUTO,
//												 M.DS_MERCADORIA
//											FROM TB_PEDIDO_MERCADORIA PM
//												 INNER JOIN TB_MERCADORIA M  ON  PM.CD_MERCADORIA= M.CD_MERCADORIA
//
//											WHERE PM.CD_LOJA   = ? AND
//											      PM.NR_PEDIDO = ? ");
//
//            /* @var $mercadoriasDoPedido Zend\Db\ResultSet\ResultSet */
//            $mercadoriasDoPedido = $statement->execute(array($session->cdLoja, $nrPedido));
//
//
//            $viewModel = new ViewModel();
//            $viewModel->setTerminal(false);
//            $viewModel->setVariable('nmClienteSelecionado', $nmCliente);
//            $viewModel->setVariable('cdVendedorSelecionado', $cdVendedor);
//            $viewModel->setVariable('nrPedido', $nrPedido);
//            $viewModel->setVariable('tpPedido', $tpPedido);
//            $viewModel->setVariable('mercadorias', $mercadorias);
//            $viewModel->setVariable('vendedores', $vendedores);
//            $viewModel->setVariable('mercadoriasDoPedido', $mercadoriasDoPedido);
//
//            return $viewModel;
//        }
    } 
	
	/**
     *
     * @return \Zend\Http\Response|\Zend\View\Model\ViewModel
     */
    public function novoPedidoAction(){

        $auth = new AuthenticationService();
        // get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $mercadoriaTable = new MercadoriaTable($dbAdapter);

        //get session
        $session = new Container("orangeSessionContainer");

        $identity = null;
        if (!$auth->hasIdentity()) {
            // redirect to user index page
            return $this->redirect()->toRoute('home');
        } else {
            // get post data

            /* @var $mercadorias Zend\Db\ResultSet\ResultSet */
            $mercadorias = $mercadoriaTable->getComboMercadorias($session->cdLoja);

            $statement = $dbAdapter->query("SELECT
    											   A.CD_FUNCIONARIO,
												   A.DS_FUNCIONARIO
										    FROM
    											  TB_FUNCIONARIO A
    											  INNER JOIN TB_CARGO B ON A.CD_CARGO = B.CD_CARGO
									    	WHERE
    											A.DT_DESLIGAMENTO IS NULL 	 AND
									    		B.ST_VENDEDOR 	= 'S'		 AND
    											A.CD_LOJA 	    = ?
    										ORDER BY
    							  				A.CD_FUNCIONARIO
										    ");

            /* @var $vendedores Zend\Db\ResultSet\ResultSet */
            $vendedores = $statement->execute(array($session->cdLoja));

            $viewModel = new ViewModel();
            //$viewModel->setTerminal(true);
            $viewModel->setVariable('mercadorias', $mercadorias);
            $viewModel->setVariable('vendedores', $vendedores);

            return $viewModel;
        }
    }

    /**
     *
     * @return \Zend\Http\Response|\Zend\View\Model\ViewModel
     */
    public function abrirAction(){

        $nuPedido             = (int) $this->params()->fromQuery('id');

        // get the db adapter
        $sm                 = $this->getServiceLocator();
        $dbAdapter          = $sm->get('Zend\Db\Adapter\Adapter');
        $mercadoriaTable    = new MercadoriaTable($dbAdapter);
        $pedidoTable        = new PedidoTable($dbAdapter);
        $clienteTable       = new ClienteTable($dbAdapter);

        //get session
        $session        = new Container("orangeSessionContainer");

        $pedido 		      = $pedidoTable->recuperaPedidoPorNumero($nuPedido,false);

        $cliente 		      = $clienteTable->getId($pedido['CD_CLIENTE']);

        /* @var $mercadorias Zend\Db\ResultSet\ResultSet */
        $mercadoriasDoPedido = $mercadoriaTable->recuperaMercadoriaPedido($nuPedido);

        $statement = $dbAdapter->query("SELECT
                                               A.CD_FUNCIONARIO,
                                               A.DS_FUNCIONARIO
                                        FROM
                                              TB_FUNCIONARIO A
                                              INNER JOIN TB_CARGO B ON A.CD_CARGO = B.CD_CARGO
                                        WHERE
                                            A.DT_DESLIGAMENTO IS NULL 	 AND
                                            B.ST_VENDEDOR 	= 'S'		 AND
                                            A.CD_LOJA 	    = ?
                                        ORDER BY
                                            A.CD_FUNCIONARIO
                                        ");

        /* @var $vendedores Zend\Db\ResultSet\ResultSet */
        $vendedores = $statement->execute(array($session->cdLoja));

        $totalPedido            = $pedido['VL_TOTAL_LIQUIDO'];
        $totalSubPedido         = $pedido['VL_TOTAL_BRUTO'];
        $nrDescontoPedido       = 0.0;
        foreach( $mercadoriasDoPedido as $merc ){
            $nrDescontoPedido       = (float)$merc['NR_DESCONTO'];
            break;
        }

        $viewModel = new ViewModel();
        $viewModel->setTemplate('application/pedido/novo-pedido');
        $viewModel->setVariable('pedido', $pedido);
        $viewModel->setVariable('cliente', (array)$cliente);
        $viewModel->setVariable('pedidoMerc', $mercadoriasDoPedido);
        $viewModel->setVariable('vendedores', $vendedores);
        $viewModel->setVariable('nrDesconto', $nrDescontoPedido);
        $viewModel->setVariable('valorDesconto', ($totalSubPedido - $totalPedido));
        $viewModel->setVariable('totalPedido', $totalPedido);
        $viewModel->setVariable('totalSubPedido', $totalSubPedido);

        return $viewModel;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function salvarPedidoAction(){
        // get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');

        Try {
            //get session
            $session = new Container("orangeSessionContainer");

            $dbAdapter->getDriver()->getConnection()->beginTransaction();

            // get post data
            $request = $this->getRequest();
            $post = $request->getPost();
            $cdVendedor = $post->get('cdVendedor');
            $dsVendedor = $post->get('dsVendedor');
            $cdsMercadoria = $post->get('checkBoxMercadoria');
            $nrPedido = $post->get('nrPedido');
            $valorTotalPedido = 0;


            //1 - Recupero o agendamento pelo número do pedido
            $statement = $dbAdapter->query("SELECT
                                                CD_LOJA,
                                                NR_MACA,
                                                DT_HORARIO,
                                                CD_CLIENTE
                                        FROM
                                                TB_AGENDAMENTO_FRANQUIA
                                        WHERE NR_PEDIDO = ? ");
            $results = $statement->execute(array($nrPedido));
            $agendamento = $results->current();


            //2 - Apago as mercadorias na tabela pedido_pagamento
            $statement = $dbAdapter->query("DELETE TB_PEDIDO_PAGAMENTO WHERE CD_LOJA   = ?  AND NR_PEDIDO = ? ");

            $statement->execute(array($session->cdLoja, $nrPedido));


            //3 - Apago as mercadorias na  tabela agendamento franquia servicos
            $statement = $dbAdapter->query("DELETE
    										TB_AGENDAMENTO_FRANQUIA_SERVICOS
										WHERE
											CD_LOJA    = ? AND
											NR_MACA    = ? AND
											DT_HORARIO = CONVERT(DATETIME,?,121) ;
										    ");

            $statement->execute(array($agendamento["CD_LOJA"],
                $agendamento["NR_MACA"],
                $agendamento["DT_HORARIO"]
            ));


            //4 - Apago as mercadorias na tabela pedido_mercadoria
            $statement = $dbAdapter->query("DELETE
								    	 	TB_PEDIDO_ESTOQUE_LOJA
								    	 WHERE CD_LOJA   = ?  AND
											   NR_PEDIDO = ?
										    ");

            $statement->execute(array($session->cdLoja, $nrPedido));


            $statement = $dbAdapter->query("DELETE
								    	 	TB_PEDIDO_MERCADORIA
								    	 WHERE CD_LOJA   = ?  AND
											   NR_PEDIDO = ?
										    ");

            $statement->execute(array($session->cdLoja, $nrPedido));


            //5- Obtenhos os valores das mercadorias, para inserir no corpo do pedido
            foreach ($cdsMercadoria as $cdMercadoria) {

                //6 - Recuperando o valor do preço de venda normal
                $statement = $dbAdapter->query("SELECT
    											VL_PRECO_VENDA
								    	  FROM
    											RL_PRAZO_LIVRO_PRECOS
								          WHERE
    											CD_LIVRO = 1 AND
								          		CD_PRAZO = 1 AND
								          		CD_MERCADORIA = ?");
                $results = $statement->execute(array($cdMercadoria));
                $rowResult = $results->current();
                $precoNormal = $rowResult["VL_PRECO_VENDA"];

                //7 - Recuperando o valor do preço de venda em promoção
                $statement = $dbAdapter->query("SELECT
    											VL_PRECO_VENDA = CASE WHEN DT_VALIDADE_PROMOCAO > GETDATE() THEN VL_PRECO_VENDA_PROMOCAO ELSE VL_PRECO_VENDA END
											FROM
    											TB_Livro_Precos
											WHERE
    											CD_LIVRO 	  = 1 AND
    											CD_MERCADORIA = ?");
                $results = $statement->execute(array($cdMercadoria));
                $rowResult = $results->current();
                $precoPromocao = $rowResult["VL_PRECO_VENDA"];

                //array set
                $mercadoria = array();
                $mercadoria["CD_LOJA"] = $session->cdLoja;
                $mercadoria["NR_PEDIDO"] = $nrPedido;
                $mercadoria["CD_MERCADORIA"] = $cdMercadoria;
                $mercadoria["CD_LIVRO"] = 1;
                $mercadoria["CD_PRAZO"] = 1;
                $mercadoria["VL_PRECO_VENDA"] = $precoNormal;
                $mercadoria["VL_PRECO_CUSTO"] = $precoPromocao;
                $mercadoria["NR_QTDE_PEDIDA"] = 1;
                $mercadoria["NR_QTDE_VENDIDA"] = 1;
                $mercadoria["VL_TOTAL_BRUTO"] = $precoPromocao;
                $mercadoria["VL_TOTAL_LIQUIDO"] = $precoPromocao;
                $mercadoria["VL_PRECO_VENDA_TAB"] = $precoPromocao;
                $mercadoria["ST_PROMOCAO"] = ($precoNormal > $precoPromocao) ? "S" : "N";
                $mercadoria["VL_DESCONTO_MERC"] = 0;
                $mercadoria["DS_LOCAL_RETIRADA"] = "";
                $mercadoria["DS_OBSERVACAO"] = "";
                $mercadoria["VL_PRECO_VENDA"] = ($precoNormal > $precoPromocao) ? $precoPromocao : $precoNormal;


                //8-Faz um ou mais "insert" na tabela de pedido mercadorias
                $statementInsert = $dbAdapter->query("INSERT INTO TB_PEDIDO_MERCADORIA
											     (CD_LOJA
											    	,NR_PEDIDO
											    	,CD_MERCADORIA
											    	,CD_LIVRO
											    	,CD_PRAZO
											    	,VL_PRECO_VENDA
											    	,VL_PRECO_CUSTO
											    	,NR_QTDE_PEDIDA
											    	,NR_QTDE_VENDIDA
											    	,VL_TOTAL_BRUTO
											    	,VL_TOTAL_LIQUIDO
											    	,VL_PRECO_VENDA_TAB
											    	,ST_PROMOCAO
											    	,VL_DESCONTO_MERC
											    	,DS_LOCAL_RETIRADA
											    	,DS_OBSERVACAO
												  )
											      VALUES
    											  ( ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $statementInsert->execute($mercadoria);

                //9-Faz um ou mais "insert" na tabela de agendamento_franquia_servicos
                //8-Faz um ou mais "insert" na tabela de pedido mercadorias
                $statementInsertAgenda = $dbAdapter->query("INSERT INTO TB_AGENDAMENTO_FRANQUIA_SERVICOS
											      (CD_LOJA,NR_MACA,DT_HORARIO,CD_MERCADORIA)
											      VALUES
    											  (?,?,CONVERT(DATETIME,?,121),?)"
                );
                $statementInsertAgenda->execute(array($agendamento["CD_LOJA"],
                    $agendamento["NR_MACA"],
                    $agendamento["DT_HORARIO"],
                    $mercadoria["CD_MERCADORIA"]
                ));


                $valorTotalPedido+=$mercadoria["VL_PRECO_VENDA"];
            }


            //6 - Atualiza a tabela de pedido, ou seja a "cabeça do pedido"
            $pedido = array();

            //PARAMETROS DO SET NO UPDATE
            $pedido["CD_LIVRO"] = 1;
            $pedido["CD_PRAZO"] = 1;
            $pedido["ST_PEDIDO"] = "A";
            $pedido["CD_TIPO_PEDIDO"] = 1;
            $pedido["ORCAMENTO_PEDIDO"] = "P";
            $pedido["NR_CONTROLE"] = -1;
            $pedido["ST_CONSIGNADO"] = "N";
            $pedido["ST_APROVEITA_CREDITO"] = "S";
            $pedido["CD_FUNCIONARIO"] = $cdVendedor;
            $pedido["VL_TOTAL_BRUTO"] = $valorTotalPedido;
            $pedido["VL_TOTAL_LIQUIDO"] = $valorTotalPedido;
            //$pedido["VL_TOTAL_LIQUIDO"] 	 =$valorTotalPedido * (1 - $descontoAgendamento / 100 );
            $pedido["UsuarioUltimaAlteracao"] = substr($session->usuario, 0, 30); //login do usuário que fez a alteração
            //PARAMETROS DO WHERE NO UPDATE
            $pedido["NR_PEDIDO"] = $nrPedido;
            $pedido["CD_LOJA"] = $session->cdLoja;

            $statementUpdate = $dbAdapter->query("UPDATE
    											TB_PEDIDO
    											SET
									    			CD_LIVRO			 =?,
											    	CD_PRAZO			 =?,
											    	ST_PEDIDO			 =?,
											    	CD_TIPO_PEDIDO		 =?,
											    	ORCAMENTO_PEDIDO	 =?,
											    	NR_CONTROLE			 =?,
											    	ST_CONSIGNADO		 =?,
											    	ST_APROVEITA_CREDITO =?,
											    	CD_FUNCIONARIO		 =?,
											    	VL_TOTAL_BRUTO		 =?,
											    	VL_TOTAL_LIQUIDO	 =?,
											    	DT_UltimaAlteracao	 =GETDATE(),
											    	UsuarioUltimaAlteracao=?
											     WHERE
    												NR_PEDIDO = ? AND
    												CD_LOJA   = ?   ");
            $statementUpdate->execute($pedido);

            $statementUpdateAgenda = $dbAdapter->query("UPDATE
	    												TB_AGENDAMENTO_FRANQUIA
	    											 SET
										    			CD_FUNCIONARIO	= ?
												     WHERE
															CD_LOJA    = ? AND
															NR_MACA    = ? AND
															DT_HORARIO = CONVERT(DATETIME,?,121) ;
														    ");

            $statementUpdateAgenda->execute(array($cdVendedor,
                $agendamento["CD_LOJA"],
                $agendamento["NR_MACA"],
                $agendamento["DT_HORARIO"]
            ));




            //Recuperando o valor do preço de venda normal
            $statement = $dbAdapter->query("SELECT
    											VL_PRECO_VENDA
								    	  FROM
    											RL_PRAZO_LIVRO_PRECOS
								          WHERE
    											CD_LIVRO = 1 AND
								          		CD_PRAZO = 1 AND
								          		CD_MERCADORIA = ?");
            $results = $statement->execute(array($cdMercadoria));
            $rowResult = $results->current();
            $precoNormal = $rowResult["VL_PRECO_VENDA"];

            $dbAdapter->getDriver()->getConnection()->commit();

            $viewModel = new ViewModel();
            $viewModel->setTerminal(true);
            return $viewModel;
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }
	
	/**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function salvarNovoPedidoAction(){
        // get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        //get session
        $session = new Container("orangeSessionContainer");

        //redirect after save
        $redirect = '/pedido/lista-tablet';

        try {

            $dbAdapter->getDriver()->getConnection()->beginTransaction();
            $mercadoriaTable= new MercadoriaTable($dbAdapter);

            // get post data
            $request            = $this->getRequest();
            $post               = $request->getPost();
//            echo "<pre>";
//            print_r($post);
//            exit;
            $cdCliente                      = $post->get('codCliente');
            $cdVendedor                     = $post->get('cdVendedor');
            $cdsMercadoria                  = $post->get('cdMercadoria');
            $dtPedido                       = $post->get('dtPedido');
            $precoTotalBruto                = str_ireplace(",",".",$post->get('vl_sub_tot'));
            $precoTotalPedido               = str_ireplace(",",".",$post->get('vl_pedido_tot'));
            $vlDescontoPedido               = str_ireplace(",",".",$post->get('vl_desconto'));
            $nrPercentualDescontoPedido     = str_ireplace(",",".",$post->get('nr_desconto'));
            $valorTotalPedido               = 0;

            if ($post->get('nrPedido')) {
                $nrPedido = $post->get('nrPedido');
            } else {
                $nrPedido = $sm->get("pedido_table")->getNextNumeroPedido();

                //PARAMETROS
                $pedido["CD_LOJA"]                  = $session->cdLoja;
                $pedido["NR_PEDIDO"]                = $nrPedido;
                $pedido["CD_LIVRO"]                 = 1;
                $pedido["CD_PRAZO"]                 = 1;
                $pedido["ST_PEDIDO"]                = 'A';
                $pedido["CD_TIPO_PEDIDO"]           = 1;
                $pedido["ORCAMENTO_PEDIDO"]         = "P";
                $pedido["NR_CONTROLE"]              = -1;
                $pedido["ST_CONSIGNADO"]            = "N";
                $pedido["ST_APROVEITA_CREDITO"]     = "S";
                $pedido["CD_FUNCIONARIO"]           = $cdVendedor;
                $pedido["VL_TOTAL_BRUTO"]           = $precoTotalBruto;
                $pedido["VL_TOTAL_LIQUIDO"]         = $precoTotalPedido;
                $pedido["UsuarioUltimaAlteracao"]   = substr($session->usuario, 0, 30); //login do usuário que fez a alteração
                $pedido["CD_CLIENTE"]               = $cdCliente;
                $pedido["DT_PEDIDO"]                = $dtPedido;

                $sm->get("pedido_table")->inserePedido($pedido);
            }

            $sm->get("pedido_table")->deletaPedidoMercadoria($session->cdLoja, $nrPedido);

            //Obtenhos os valores das mercadorias, para inserir no corpo do pedido
            foreach ($cdsMercadoria as $i => $cdMercadoria) {

                $cdMercadoria           = (int) $cdMercadoria;

                //Recuperando o valor do preço de venda normal
                $precoNormal            = $sm->get('mercadoria_table')->getValorPrecoVenda($cdMercadoria);

                //Recuperando o valor do preço de venda em promoção
                $precoPromocao          = $sm->get('mercadoria_table')->getValorPromocao($cdMercadoria);

                $qtdeMercadoria         = (int)$post->get('qtdVendida-'.$cdMercadoria);
                $precoUnitario          = $post->get('vl_preco_unitario-'.$cdMercadoria);
                $precoDesconto          = $post->get('vl_preco_desconto-'.$cdMercadoria);
                $precototal             = $post->get('vl_tot-'.$cdMercadoria);
                $total_venda_desconto   = 0;

                //array set
                $mercadoria = array();
                $mercadoria["CD_LOJA"]              = $session->cdLoja;
                $mercadoria["NR_PEDIDO"]            = $nrPedido;
                $mercadoria["CD_MERCADORIA"]        = $cdMercadoria;
                $mercadoria["CD_LIVRO"]             = 1;
                $mercadoria["CD_PRAZO"]             = 1;
                $mercadoria["VL_PRECO_VENDA"]       = $precoUnitario;
                $mercadoria["VL_PRECO_CUSTO"]       = $precoPromocao;
                $mercadoria["NR_QTDE_PEDIDA"]       = $qtdeMercadoria;
                $mercadoria["NR_QTDE_VENDIDA"]      = $qtdeMercadoria;
                $mercadoria["VL_TOTAL_BRUTO"]       = $precoUnitario;
                $mercadoria["VL_TOTAL_LIQUIDO"]     = $precoDesconto;
                $mercadoria["VL_PRECO_VENDA_TAB"]   = $precoDesconto;
                $mercadoria["ST_PROMOCAO"]          = ($precoNormal > $precoPromocao) ? "S" : "N";
                $mercadoria["VL_DESCONTO_MERC"]     = empty($nrPercentualDescontoPedido) ? 0 : $nrPercentualDescontoPedido;
                $mercadoria["DS_LOCAL_RETIRADA"]    = "";
                $mercadoria["DS_OBSERVACAO"]        = "";

                //Faz um ou mais "insert" na tabela de pedido mercadorias
                $statementInsert = $dbAdapter->query("INSERT INTO TB_PEDIDO_MERCADORIA
											     (CD_LOJA
											    	,NR_PEDIDO
											    	,CD_MERCADORIA
											    	,CD_LIVRO
											    	,CD_PRAZO
											    	,VL_PRECO_VENDA
											    	,VL_PRECO_CUSTO
											    	,NR_QTDE_PEDIDA
											    	,NR_QTDE_VENDIDA
											    	,VL_TOTAL_BRUTO
											    	,VL_TOTAL_LIQUIDO
											    	,VL_PRECO_VENDA_TAB
											    	,ST_PROMOCAO
											    	,VL_DESCONTO_MERC
											    	,DS_LOCAL_RETIRADA
											    	,DS_OBSERVACAO
												  )
											      VALUES
    											  ( ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $statementInsert->execute($mercadoria);
                $valorTotalPedido+=$precototal;
            }
            //Atualiza a tabela de pedido, ou seja a "cabeça do pedido"
            $pedido = array();

            //PARAMETROS DO SET NO UPDATE
            $pedido["CD_LIVRO"]                 = 1;
            $pedido["CD_PRAZO"]                 = 1;
            $pedido["ST_PEDIDO"]                = "A";
            $pedido["CD_TIPO_PEDIDO"]           = 1;
            $pedido["ORCAMENTO_PEDIDO"]         = "P";
            $pedido["NR_CONTROLE"]              = -1;
            $pedido["ST_CONSIGNADO"]            = "N";
            $pedido["ST_APROVEITA_CREDITO"]     = "S";
            $pedido["CD_FUNCIONARIO"]           = $cdVendedor;
            $pedido["VL_TOTAL_BRUTO"]           = $precoTotalBruto;
            $pedido["VL_TOTAL_LIQUIDO"]         = $valorTotalPedido;
            $pedido["UsuarioUltimaAlteracao"]   = substr($session->usuario, 0, 30); //login do usuário que fez a alteração
            $pedido["DT_PEDIDO"]                = $dtPedido;
            $pedido["CD_CLIENTE"]               = $cdCliente;
            $pedido["NR_PEDIDO"]                = $nrPedido;
            $pedido["CD_LOJA"]                  = $session->cdLoja;

            $statementUpdate = $dbAdapter->query("UPDATE
    											TB_PEDIDO
    											SET
									    			CD_LIVRO			 =?,
											    	CD_PRAZO			 =?,
											    	ST_PEDIDO			 =?,
											    	CD_TIPO_PEDIDO		 =?,
											    	ORCAMENTO_PEDIDO	 =?,
											    	NR_CONTROLE			 =?,
											    	ST_CONSIGNADO		 =?,
											    	ST_APROVEITA_CREDITO =?,
											    	CD_FUNCIONARIO		 =?,
											    	VL_TOTAL_BRUTO		 =?,
											    	VL_TOTAL_LIQUIDO	 =?,
											    	DT_UltimaAlteracao	 =GETDATE(),
											    	UsuarioUltimaAlteracao=?,
											    	DT_PEDIDO			 =?,
											    	CD_CLIENTE           =?
											     WHERE
    												NR_PEDIDO = ? AND
    												CD_LOJA   = ?   ");
            $statementUpdate->execute($pedido);

            $dbAdapter->getDriver()->getConnection()->commit();
            $message = array("success" => "Pedido cadastrado com sucesso");
            $this->flashMessenger()->addMessage($message);

            if($post->get('flRedirecionarAoCaixa') == 'S') {
                $redirect = '/caixa/index';
            }

        } catch (Exception $e) {
            $message = array("error" => $e->getMessage());
            $this->flashMessenger()->addMessage($message);
            $dbAdapter->getDriver()->getConnection()->rollback();
        }

        return $this->redirect()->toUrl($redirect);
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function verificaEstoqueAction(){

        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);

        try {
            // get the db adapter
            $sm = $this->getServiceLocator();
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $mercadoriaTable = new MercadoriaTable($dbAdapter);

            //get session
            $session = new Container("orangeSessionContainer");

            // get parameters
            $parameters = $this->params();
            $cdMercadoria = (int) $parameters->fromQuery('cdMercadoria');
            $tpPedido = (int) $parameters->fromQuery('tpPedido');
            $cdLoja = (int) $session->cdLoja;
            $tpPedidoBloqueados = array(4, 6, 7);


            //Recuperando o valor do preço de venda normal
            $resultEstoque = $mercadoriaTable->recuperaValorDeVenda($cdLoja, $cdMercadoria);

            $permitirPedido = in_array($tpPedido, $tpPedidoBloqueados) ? false : true;
            $liberaSemEstoque = trim($resultEstoque['ST_LIBERA_SEM_ESTOQUE']) != 'S' ? false : true;
            $estoqueDisponivel = (float) $resultEstoque['NR_QTDE_DISPONIVEL'];

            if ($permitirPedido == true) { //Se o tipo do pedido é diferente de 4, 6 e 7
                //verifica o estoque disponível
                if (($estoqueDisponivel <= 0) && ($liberaSemEstoque == false)) { // Se a quantidade disponível for menor ou igual a zero e não liberar sem estoque
                    throw new \Exception(utf8_decode("A mercadoria/serviço não pode ser liberada sem estoque"));
                } else {
                    //Verifica o estoque dos insumos da composição da mercadoria
                    //numeros de insumos da composição
                    //Recuperando o valor do preço de venda normal
                    $statementInsumo = $dbAdapter->query("SELECT
                                                            MC.CD_MERCADORIA_COMPOSICAO
                                                            ,MC.QUANTIDADE
                                                            ,MC.VL_COMPOSICAO
                                                            ,MC.VL_COMPOSICAO_PROMOCAO
                                                            ,MC.TP_CALCULO_PRECO
                                                            ,MC.CD_UNIDADE_VENDA
                                                            ,MC.NR_RMS
                                                            ,NR_QTDE_DISPONIVEL = dbo.MostraEstoqueDisponivel(?,MC.CD_MERCADORIA_COMPOSICAO )
                                                            ,DS_MERCADORIA_COMPOSICAO = M.DS_MERCADORIA
                                                        FROM TB_MERCADORIA_COMPOSICAO MC
                                                        INNER JOIN TB_MERCADORIA M ON M.CD_MERCADORIA = MC.CD_MERCADORIA_COMPOSICAO
                                                        WHERE MC.CD_MERCADORIA = ?");
                    $resultsInsumo = $statementInsumo->execute(array($cdLoja, $cdMercadoria));

                    if (is_object($resultsInsumo)) {
                        //if numero de insumos da composição for maior igual a 1, percorre os insumos
                        foreach ($resultsInsumo as $insumo) {
                            //verifica a quantidade de estoque do insumo
                            $estoqueDisponivelInsumo = (float) $insumo['NR_QTDE_DISPONIVEL'];
                            $cdInsumo = $insumo['CD_MERCADORIA_COMPOSICAO'];
                            $nmInsumo = $insumo['DS_MERCADORIA_COMPOSICAO'];
                            if ($estoqueDisponivelInsumo <= 0) { ////se o estoque disponível do insumo for menor igual a 0, dispara exceção abaixo
                                throw new \Exception("Composição da mercadoria com insumo ($cdInsumo - $nmInsumo ) sem estoque");
                            }
                        }
                    }
                }
            } else {
                throw new \Exception("Ação negada, para este tipo de pedido.");
            }



            //Se não aconteceu nenhuma exceção acima,
            //então seta as variavéis abaixo, permitindo a inclusão da  mercadoria/serviço
            $permitir = 1;
            $erro = null;
        } catch (\Exception $e) {
            $permitir = 0;
            $erro = utf8_encode($e->getMessage());
        }


        $viewModel->setVariable('permitir', $permitir);
        $viewModel->setVariable('erroMsg', $erro);
        return $viewModel;
    }

    public function salvaMercadoriaPedidoAction(){
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        try {
            $dbAdapter->getDriver()->getConnection()->beginTransaction();
            $session = new Container("orangeSessionContainer");
            $request = $this->getRequest()->getPost();

            $arrMercadoria = $request->get('cdMercadoria');
            $arrDescontos = $request->get('nrDesconto');
            $arrValNominal = $request->get('vlNominal');
            $arrValTotal = $request->get('vlTotal');
            $arrTpRetirada = $request->get('tpRetirada');
            $codigoCliente = $request->get('cd_cliente');
            $numeroPedido = $request->get('nr_pedido');
            $cdFuncionario = $request->get('cd_funcionario');
            $cRegistros = 0;
            $bPesistencia = false;

            // caso nao exista o numero do pedido, recupera o ultimo numero e adiciona mais um
            if (empty($numeroPedido)) {
                $numeroPedido = $this->getServiceLocator()->get('pedido_table')->recuperoProximoNumeroDoPedido();
                $numeroPedido = (int) $numeroPedido["NR_PEDIDO"] + 1;
                $bPesistencia = true;
            }

            foreach ($arrValTotal as $total) {
                $valorTotalPedido = $valorTotalPedido + $total;
            }

            // cadastro do pedido
            $pedido = array();
            if ($bPesistencia) {
                $pedido["CD_LOJA"] = $session->cdLoja;
                $pedido["NR_PEDIDO"] = $numeroPedido;
                $pedido["CD_LIVRO"] = 1;
                $pedido["CD_PRAZO"] = 1;
                $pedido["ST_PEDIDO"] = "A";
                $pedido["CD_TIPO_PEDIDO"] = 1;
                $pedido["ORCAMENTO_PEDIDO"] = "P";
                $pedido["NR_CONTROLE"] = -1;
                $pedido["ST_CONSIGNADO"] = "N";
                $pedido["ST_APROVEITA_CREDITO"] = "S";
                $pedido["CD_FUNCIONARIO"] = $cdFuncionario;
                $pedido["VL_TOTAL_BRUTO"] = $valorTotalPedido;
                $pedido["VL_TOTAL_LIQUIDO"] = $valorTotalPedido;
                $pedido["UsuarioUltimaAlteracao"] = substr($session->usuario, 0, 30);
                $pedido["CD_CLIENTE"] = $codigoCliente;
                $pedido["DT_PEDIDO"] = date('d/m/Y');
                if (!$this->getServiceLocator()->get('pedido_table')->inserePedido($pedido)) {
                    throw new \Exception;
                }
            } else {
                if (!$this->getServiceLocator()->get('pedido_table')->deletaPedidoMercadoria($session->cdLoja, $numeroPedido)) {
                    throw new \Exception;
                }

                $pedido["CD_LIVRO"] = 1;
                $pedido["CD_PRAZO"] = 1;
                $pedido["ST_PEDIDO"] = "A";
                $pedido["CD_TIPO_PEDIDO"] = 1;
                $pedido["ORCAMENTO_PEDIDO"] = "P";
                $pedido["NR_CONTROLE"] = -1;
                $pedido["ST_CONSIGNADO"] = "N";
                $pedido["ST_APROVEITA_CREDITO"] = "S";
                $pedido["CD_FUNCIONARIO"] = $cdFuncionario;
                $pedido["VL_TOTAL_BRUTO"] = $valorTotalPedido;
                $pedido["VL_TOTAL_LIQUIDO"] = $valorTotalPedido;
                $pedido["UsuarioUltimaAlteracao"] = substr($session->usuario, 0, 30); //login do usu�rio que fez a altera��o
                $pedido["NR_PEDIDO"] = $numeroPedido;
                $pedido["CD_LOJA"] = $session->cdLoja;

                if (!$this->getServiceLocator()->get('pedido_table')->atualizaPedido($pedido)) {
                    throw new \Exception;
                }
            }

            // cadastro das mercadorias em PEDIDO_MERCADORIA
            foreach ($arrMercadoria as $vMercadoria) {
                $vlPromocao = $this->getServiceLocator()->get('mercadoria_table')->getValorPromocao($vMercadoria);
                $mercadoria = array();
                $mercadoria["CD_LOJA"] = (int) $session->cdLoja;
                $mercadoria["NR_PEDIDO"] = (int) $numeroPedido;
                $mercadoria["CD_MERCADORIA"] = $vMercadoria;
                $mercadoria["CD_LIVRO"] = 1;
                $mercadoria["CD_PRAZO"] = 1;
                $mercadoria["VL_PRECO_VENDA"] = $arrValNominal[$cRegistros];
                $mercadoria["VL_PRECO_CUSTO"] = $vlPromocao;
                $mercadoria["NR_QTDE_PEDIDA"] = 1;
                $mercadoria["NR_QTDE_VENDIDA"] = 1;
                $mercadoria["VL_TOTAL_BRUTO"] = $arrValTotal[$cRegistros];
                $mercadoria["VL_TOTAL_LIQUIDO"] = $arrValTotal[$cRegistros];
                $mercadoria["VL_PRECO_VENDA_TAB"] = $vlPromocao;
                $mercadoria["ST_PROMOCAO"] = ($arrValNominal[$cRegistros] > $vlPromocao) ? "S" : "N";
                $mercadoria["VL_DESCONTO_MERC"] = ($arrDescontos[$cRegistros]) ? $arrDescontos[$cRegistros] : 0;
                $mercadoria["DS_LOCAL_RETIRADA"] = $arrTpRetirada[$cRegistros];
                $mercadoria["DS_OBSERVACAO"] = "";

                if (!$this->getServiceLocator()->get('pedido_table')->inserePedidoMercadoria($mercadoria)) {
                    throw new \Exception;
                }

                $cRegistros++;
            }

            $dbAdapter->getDriver()->getConnection()->commit();

            echo json_encode(array('result' => 'success', 'NR_PEDIDO' => $numeroPedido));
            exit;
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
            exit;
        }
    }

    public function pesquisaMercadoriaPorParamentroAction(){
        $post = $this->getRequest()->getPost();
        $arrParams = array();
        foreach ($post as $k => $v) {
            $arrParams[$k] = $v;
        }
        $arrPedido = $this->getServiceLocator()->get('mercadoria_table')->pesquisaMercadoriaPorParamentro($arrParams);

        if (count($arrPedido)) {
            echo json_encode(array('result' => 'success', 'data' => $arrPedido));
            exit;
        }

        echo json_encode(array('result' => 'erro', 'message' => 'Mercadoria não encontrada.'));
        exit;
    }

    /**
     * @return ViewModel
     */
    public function cpfNotaAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    /**
     *
     */
    public function recuperaHistoricoPorDataAction(){
        $post = $this->getRequest()->getPost();
        $dtInicio = $post['dt_inicial'];
        $dtFinal = ($post['dt_final']) ? $post['dt_final'] : null;
        $arrHistorico = $this->getServiceLocator()->get('pedido_table')->recuperaHistoricoCliente($post['cd_cliente'], $dtInicio, $dtFinal);
        if(count($arrHistorico)){
            echo json_encode(array('result' => 'success', 'data' => $arrHistorico));
        } else {
            echo json_encode(array('result' => 'erro'));
        }
        exit;
    }

    /**
     *
     */
    public function cancelarAction(){

        // get the db adapter
        $sm             = $this->getServiceLocator();
        $dbAdapter      = $sm->get('Zend\Db\Adapter\Adapter');

        $dbAdapter->getDriver()->getConnection()->beginTransaction();

        try
        {
            $session        = new Container("orangeSessionContainer");
            $pedidoTable    = new PedidoTable($dbAdapter);
            $nuPedido       = (int) $this->params()->fromQuery('id');
            $retorno        = $pedidoTable->cancelaPedido($nuPedido, $session->cdFuncionario, $session->cdLoja);


            $message = array("success" => "Pedido cancelado com sucesso");
            $this->flashMessenger()->addMessage($message);

            $dbAdapter->getDriver()->getConnection()->commit();
        } catch (Exception $e) {

            $message = array("error" => $e->getMessage());
            $this->flashMessenger()->addMessage($message);
            $dbAdapter->getDriver()->getConnection()->rollback();
        }

        return $this->redirect()->toUrl('/pedido/lista-tablet');

    }

}
