<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Session\Container;

class PedidoTable extends AbstractTableGateway {

    protected $table = "TB_PEDIDO";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        //$this->resultSetPrototype->setArrayObjectPrototype(new Servicos());
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }

    public function selectCountNrPedido($cdLoja){
        $statement = $this->adapter->query("SELECT  COUNT(A.NR_PEDIDO) AS NR_PEDIDO
    									FROM TB_PEDIDO A
										LEFT JOIN TB_CLIENTE C ON A.CD_CLIENTE = C.CD_CLIENTE
    									INNER JOIN TB_PARAMEMPRESA PE ON PE.CD_LOJA=A.CD_LOJA
										WHERE
    										PE.FLALOJADEFAULT = 'S'	AND
    			 							A.ST_PEDIDO   	  = 'A' AND
    									    CONVERT(VARCHAR(10),A.DT_PEDIDO,103)= CONVERT(VARCHAR(10),GETDATE(),103) AND
    										A.CD_LOJA     	  = ? ");


        $results = $statement->execute(array($cdLoja));
        $rowResult = $results->current();
        return $rowResult["NR_PEDIDO"];
    }

    public function getIdClientePedido($nrPedido, $cdLoja)
    {
        $select = $this->getSql()->select();
        $select->columns(array('cd_cliente'))
                ->where(array('nr_pedido' => $nrPedido, 'cd_loja' => $cdLoja));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row->cd_cliente;
    }
    
    public function getNextNumeroPedido() {
        try {
            $statement = $this->adapter->query("SELECT COALESCE(MAX(NR_PEDIDO),0) + 1 NR_PEDIDO  FROM TB_PEDIDO");
            $results = $statement->execute();
            $rowResult = $results->current();
            return $rowResult["NR_PEDIDO"];
        } catch (Exception $e) {
            return false;
        }
    }

    public function inserePedido($pedido) {
        try {
            $statementUpdate = $this->adapter->query("INSERT INTO TB_PEDIDO
                                                    (CD_LOJA
                                                    ,NR_PEDIDO
                                                    ,CD_LIVRO
                                                    ,CD_PRAZO
                                                    ,ST_PEDIDO
                                                    ,CD_TIPO_PEDIDO
                                                    ,ORCAMENTO_PEDIDO
                                                    ,NR_CONTROLE
                                                    ,ST_CONSIGNADO
                                                    ,ST_APROVEITA_CREDITO
                                                    ,CD_FUNCIONARIO
                                                    ,VL_TOTAL_BRUTO
                                                    ,VL_TOTAL_LIQUIDO
                                                    ,UsuarioUltimaAlteracao
                                                    ,CD_CLIENTE
                                                    ,DT_PEDIDO
                                                    )
                                              VALUES
                                                    (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

            $statementUpdate->execute($pedido);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function atualizaPedido($pedido) {
        try {
            $statementUpdate = $this->adapter->query("UPDATE
                                                    TB_PEDIDO
                                                SET
                                                    CD_LIVRO                = ?,
                                                    CD_PRAZO                = ?,
                                                    ST_PEDIDO               = ?,
                                                    CD_TIPO_PEDIDO          = ?,
                                                    ORCAMENTO_PEDIDO        = ?,
                                                    NR_CONTROLE             = ?,
                                                    ST_CONSIGNADO           = ?,
                                                    ST_APROVEITA_CREDITO    = ?,
                                                    CD_FUNCIONARIO          = ?,
                                                    VL_TOTAL_BRUTO          = ?,
                                                    VL_TOTAL_LIQUIDO        = ?,
                                                    DT_UltimaAlteracao      = GETDATE(),
                                                    UsuarioUltimaAlteracao  = ?
                                                WHERE
                                                   NR_PEDIDO = ? AND
                                                   CD_LOJA   = ?   ");
            $statementUpdate->execute($pedido);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function cancelaPedido($nrPedido, $cdUsuario, $cdLoja) {
        try {
            return $this->update(array('ST_PEDIDO' => 'C',
                                        'DT_CANCELAMENTO' => date('d/m/Y H:i:s'),
                                        'USUARIO_CANCELAMENTO' => $cdUsuario,
                                        'DT_UltimaAlteracao' => date('d/m/Y H:i:s'),
                                        'UsuarioUltimaAlteracao' => $cdUsuario,
                                        ),
                                array('NR_PEDIDO' => $nrPedido,
                                        'CD_LOJA' => $cdLoja));
        } catch (Exception $e) {
            return false;
        }
    }

    public function listaPedidosAtendidos($cdLoja, $dtPedido) {
        $statement = $this->adapter->query("select
                                                tp.CD_LOJA,
                                                tp.NR_PEDIDO,
                                                tp.VL_TOTAL_BRUTO,
                                                tp.VL_TOTAL_LIQUIDO,
                                                tp.CD_CLIENTE,
                                                tc.DS_NOME_RAZAO_SOCIAL,
                                                tfcr.DS_NOME
                                            from TB_PEDIDO tp
                                            left join TB_CLIENTE tc on tp.CD_CLIENTE = tc.CD_CLIENTE
                                            left join TB_FRANQUIA_CLIENTE_RAPIDO tfcr on tp.CD_CLIENTE = tfcr.CD_CLIENTE
                                            where tp.CD_LOJA = ?
                                                and tp.ST_PEDIDO = 'A'
                                                and tp.DT_PEDIDO between ?  and ?
                                            order by tp.NR_PEDIDO desc");

        $results = $statement->execute(array($cdLoja, $dtPedido . ' 00:00:00', $dtPedido . ' 23:59:59'));
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function recebePedido($data = array(), $cd_loja, $nr_pedido) {
        try {
            return $this->update($data, 
                                array('cd_loja' => (int) $cd_loja,
                                        'nr_pedido' => (int) $nr_pedido));

        } catch (Exception $e) {
            return false;
        }
    }

    public function recebePedidoPagamento($data = array()) {
        try {
            $statementUpdate = $this->adapter->query("INSERT INTO TB_PEDIDO_PAGAMENTO
                                                        (CD_LOJA,
                                                        NR_PEDIDO,
                                                        CD_PLANO_PAGAMENTO,
                                                        NR_PARCELA,
                                                        CD_TIPO_PAGAMENTO,
                                                        NR_PEDIDO_DEVOLUCAO,
                                                        NR_CGC_CPF_EMISSOR,
                                                        DS_EMISSOR,
                                                        NR_FONE_EMISSOR,
                                                        CD_CLIENTE,
                                                        CD_FINANCEIRA,
                                                        NR_BOLETO,
                                                        CD_CARTAO,
                                                        CD_BANCO,
                                                        CD_AGENCIA,
                                                        NR_CONTA,
                                                        NR_CHEQUE,
                                                        DT_EMISSAO,
                                                        DT_VENCIMENTO,
                                                        VL_DOCUMENTO,
                                                        ST_CANCELADO,
                                                        VINCULADO,
                                                        NR_QTDE_PARCELAS,
                                                        NR_Carta_Credito,
                                                        ST_ACORDOP1,
                                                        vl_troco,
                                                        NR_BOLETO_PARAMETRO
                                                        )
                                                  VALUES
                                                        (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

            $statementUpdate->execute($data);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function listaFormaPagamento() {
        $statement = $this->adapter->query("select * from TB_PLANO_PAGAMENTO order by CD_PLANO_PAGAMENTO ");

        $results = $statement->execute();
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function listaTipoPagamento() {
        $statement = $this->adapter->query("select * from TB_TIPO_PAGAMENTO where ST_HABILITADO = 'S' order by CD_TIPO_PAGAMENTO");

        $results = $statement->execute();
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function listaCartao() {
        $statement = $this->adapter->query("select * from TB_CARTAO order by DS_CARTAO");

        $results = $statement->execute();
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function listaBanco() {
        $statement = $this->adapter->query("select * from TB_BANCO order by DS_BANCO");

        $results = $statement->execute();
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function recuperaPedidoPorNumero($nuPedido, $res = true) {
        $select = "SELECT *
                   FROM TB_PEDIDO
                   WHERE NR_PEDIDO = ?";

        $statement = $this->adapter->query($select);
        $results = $statement->execute(array('nr_pedido' => (int) $nuPedido));
        if ($res) {
            $returnArray = array();
            foreach ($results as $result) {
                $returnArray[] = $result;
            }
            return $returnArray;
        }

        return $result->current();
    }

    public function recuperaMercadoriaNumeroPedido($nuPedido) {
        $select = "SELECT *
                   FROM TB_PEDIDO_MERCADORIA
                   WHERE NR_PEDIDO = ?";

        $statement = $this->adapter->query($select);
        $results = $statement->execute(array('nr_pedido' => (int) $nuPedido));
        return $results->current();
    }

    public function listPrazo() {
        $select = "SELECT CD_PRAZO, DS_PRAZO FROM TB_PRAZO";
        $statement = $this->adapter->query($select);
        return $statement->execute();
    }

    public function recuperaPedido($arrDados) {
        $select .= "SELECT P.CD_LOJA, P.NR_PEDIDO, P.CD_TIPO_MERCADORIA, P.CD_LIVRO, P.CD_PRAZO, P.CD_CLIENTE,
                           CONVERT(VARCHAR(10), P.DT_PEDIDO, 103) DT_PEDIDO, P.CD_FUNCIONARIO, P.ST_PEDIDO,
                           P.CD_TIPO_PEDIDO, C.DS_NOME_RAZAO_SOCIAL
                   FROM TB_PEDIDO P
                        INNER JOIN TB_CLIENTE C ON C.CD_CLIENTE = P.CD_CLIENTE
                   WHERE 1 = 1 ";

        if (!empty($arrDados['cd_loja'])) {
            $select .= " AND P.CD_LOJA = {$arrDados['cd_loja']} ";
        }

        if (!empty($arrDados['cd_cliente'])) {
            $select .= " AND P.CD_CLIENTE = {$arrDados['cd_cliente']} ";
        }

        if (!empty($arrDados['nu_pedido'])) {
            $select .= " AND P.NR_PEDIDO = {$arrDados['nu_pedido']} ";
        }

        if (!empty($arrDados['cd_tipo_mercadoria'])) {
            $select .= " AND P.CD_TIPO_MERCADORIA = {$arrDados['cd_tipo_mercadoria']} ";
        }

        if (!empty($arrDados['cd_livro'])) {
            $select .= " AND P.CD_LIVRO = {$arrDados['cd_livro']} ";
        }

        if (!empty($arrDados['cd_prazo'])) {
            $select .= " AND P.CD_PRAZO = {$arrDados['cd_prazo']} ";
        }
        if (!empty($arrDados['cd_funcionario'])) {
            $select .= " AND P.CD_FUNCIONARIO = {$arrDados['cd_funcionario']} ";
        }

        $statement = $this->adapter->query($select);
        $results = $statement->execute();
        return $results->current();
    }

    public function recuperaPedidosAnterior($arrDados) {
        $select .= "SELECT P.NR_PEDIDO, CONVERT(VARCHAR(10), P.DT_PEDIDO, 103) DT_PEDIDO, C.DS_NOME_RAZAO_SOCIAL, TP.DS_TIPO_PEDIDO,
            CAST((P.VL_TOTAL_BRUTO) AS MONEY) VL_TOTAL_BRUTO, CAST((P.VL_TOTAL_LIQUIDO) AS MONEY) VL_TOTAL_LIQUIDO
                    FROM TB_PEDIDO P
                        INNER JOIN TB_CLIENTE C ON C.CD_CLIENTE = P.CD_CLIENTE
                        INNER JOIN TB_TIPO_PEDIDO TP ON TP.CD_TIPO_PEDIDO = P.CD_TIPO_PEDIDO
                   WHERE 1 = 1 ";

        if (!empty($arrDados['cd_loja'])) {
            $select .= " AND P.CD_LOJA = {$arrDados['cd_loja']} ";
        }

        if (!empty($arrDados['cd_cliente'])) {
            $select .= " AND P.CD_CLIENTE = {$arrDados['cd_cliente']} ";
        }

        if (!empty($arrDados['nu_pedido'])) {
            $select .= " AND P.NR_PEDIDO = {$arrDados['nu_pedido']} ";
        }

        if (!empty($arrDados['cd_tipo_mercadoria'])) {
            $select .= " AND P.CD_TIPO_MERCADORIA = {$arrDados['cd_tipo_mercadoria']} ";
        }

        if (!empty($arrDados['cd_livro'])) {
            $select .= " AND P.CD_LIVRO = {$arrDados['cd_livro']} ";
        }

        if (!empty($arrDados['cd_prazo'])) {
            $select .= " AND P.CD_PRAZO = {$arrDados['cd_prazo']} ";
        }

        if (!empty($arrDados['cd_funcionario'])) {
            $select .= " AND P.CD_FUNCIONARIO = {$arrDados['cd_funcionario']} ";
        }

        $statement = $this->adapter->query($select);
        $results = $statement->execute();
        return $results->current();
    }

    public function recuperaUltimasCompras($cdCliente) {
        $select = "SELECT TOP 3 P.NR_PEDIDO, CONVERT(VARCHAR(10), P.DT_PEDIDO, 103) DT_PEDIDO , F.DS_FUNCIONARIO, DS_TIPO_PEDIDO,
                        TMS.DS_TIPO_MERCADORIA, TPR.DS_PRAZO
                    FROM TB_PEDIDO P
                        INNER JOIN TB_CLIENTE C ON C.CD_CLIENTE = P.CD_CLIENTE
                        INNER JOIN TB_TIPO_PEDIDO TP ON TP.CD_TIPO_PEDIDO = P.CD_TIPO_PEDIDO
                        LEFT JOIN TB_TIPO_MERCADORIA_SECAO TMS ON TMS.CD_TIPO_MERCADORIA = P.CD_TIPO_MERCADORIA
                        INNER JOIN TB_PRAZO TPR ON TPR.CD_PRAZO = P.CD_PRAZO
                        INNER JOIN TB_FUNCIONARIO F ON F.CD_FUNCIONARIO = P.CD_FUNCIONARIO
                    WHERE P.CD_CLIENTE = ? ";

        $statement = $this->adapter->query($select);
        $results = $statement->execute(array($cdCliente));

        $cont = 0;
        $res = array();
        // recuperando mercadorias por numero de pedido
        foreach ($results as $value) {
            $res[$cont] = $value;
            $select = "SELECT PM.CD_MERCADORIA, CONVERT(INT, PM.NR_QTDE_VENDIDA) NR_QTDE_VENDIDA, CAST((PM.VL_PRECO_CUSTO) AS MONEY) VL_PRECO_CUSTO,
                            CAST((PM.VL_PRECO_VENDA) AS MONEY) VL_PRECO_VENDA, M.DS_MERCADORIA,
                            (SELECT SUM(CAST((VL_PRECO_VENDA * NR_QTDE_VENDIDA) AS MONEY))
                             FROM TB_PEDIDO_MERCADORIA WHERE NR_PEDIDO = ? AND CD_MERCADORIA = PM.CD_MERCADORIA) TOTAL
                        FROM TB_PEDIDO_MERCADORIA PM
                         INNER JOIN TB_MERCADORIA M ON M.CD_MERCADORIA = PM.CD_MERCADORIA
                        WHERE PM.NR_PEDIDO = ?";
            $statement = $this->adapter->query($select);

            $c = 0;
            $resMerc = array();
            foreach ($statement->execute(array($value['NR_PEDIDO'], $value['NR_PEDIDO'])) as $val) {
                $resMerc[$c] = $val;
                $c++;
            }
            $res[$cont]['MERCADORIA'] = $resMerc;
            $cont++;
        }

        return $res;
    }

    public function recuperaHistoricoCliente($cdCliente, $dtInicio = null, $dtFinal = null) {
        $select = "SELECT DISTINCT TOP 10 P.NR_PEDIDO, CONVERT(VARCHAR(10), P.DT_PEDIDO, 103) DT_PEDIDO,
                            ISNULL(C.NR_CGC_CPF, '-') NR_CGC_CPF, C.DS_NOME_RAZAO_SOCIAL, CAST((P.VL_TOTAL_BRUTO) AS MONEY) VL_TOTAL_BRUTO,
                            CAST((P.VL_TOTAL_LIQUIDO) AS MONEY) VL_TOTAL_LIQUIDO, F.DS_FUNCIONARIO, PRA.DS_PRAZO,
                            ISNULL(CI.DS_CIDADE, '-') DS_CIDADE, ISNULL(CI.CD_UF, '-') CD_UF
                    FROM TB_PEDIDO P
                        INNER JOIN TB_AGENDAMENTO_FRANQUIA AF ON AF.CD_CLIENTE = P.CD_CLIENTE
                        INNER JOIN TB_CLIENTE C ON C.CD_CLIENTE = P.CD_CLIENTE
                        INNER JOIN TB_FUNCIONARIO F ON F.CD_FUNCIONARIO = P.CD_FUNCIONARIO
                        INNER JOIN TB_PRAZO PRA ON PRA.CD_PRAZO = P.CD_PRAZO
                        LEFT JOIN TB_CIDADE CI ON CI.CD_CIDADE = C.CD_CIDADE
                    WHERE P.CD_CLIENTE = ?
                    ";

        if($dtFinal){
            $select .= " AND P.DT_PEDIDO between '{$dtInicio} 00:00:00'  and '{$dtFinal} 23:59:59' ";
        } elseif($dtInicio) {
            $select .= " AND P.DT_PEDIDO >= {$dtInicio} 00:00:00 ";
        }
//exit($select);
        $statement = $this->adapter->query($select);
        $results = $statement->execute(array($cdCliente));

        $res = array();
        foreach ($results as $valeus) {
            $res[] = $valeus;
        }
        return $res;
    }

    public function deletaRegistosPedido() {
        try {
            //1 - Recupero o agendamento pelo n�mero do pedido
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
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function inserePedidoMercadoria($params) {
        try {
            $statementInsert = $this->adapter->query("INSERT INTO TB_PEDIDO_MERCADORIA
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

            $statementInsert->execute($params);
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
            return false;
        }
    }

    public function recuperoProximoNumeroDoPedido() {
        $statement = $this->adapter->query("select MAX(NR_PEDIDO) NR_PEDIDO FROM TB_PEDIDO");
        $resultado = $statement->execute();
        return $resultado->current();
    }

    public function deletaPedidoMercadoria($loja, $pedido) {
        try {
            $statement = $this->adapter->query("DELETE TB_PEDIDO_MERCADORIA WHERE CD_LOJA   = ?  AND NR_PEDIDO = ? ");
            $statement->execute(array($loja, $pedido));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }   
    
    public function insereRLCaixaPedido($rlCP) {
        try {
            $statement = $this->adapter->query("insert into RL_CAIXA_PEDIDO (CD_LOJA, NR_CAIXA, NR_PEDIDO, NR_LANCAMENTO_CAIXA)
                                                 values (?,?,?,?)");
            $statement->execute($rlCP);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

}
