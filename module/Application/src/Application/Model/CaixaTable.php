<?php

namespace Application\Model;

use Zend\Db\Sql\Where;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;

class CaixaTable extends AbstractTableGateway {

    protected $table = "tb_caixa";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        //$this->resultSetPrototype->setArrayObjectPrototype(new Cargo());
        //$this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }

    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10") {
        $select = new Select();
        $where = new Where();

        foreach ($param as $field => $search) {
            $where->like($field, '%' . $search . '%');
        }

        $select->from($this->table)
                ->where($where)
                ->order("ds_cargo");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function getStatusGerente($username) {

        $select = $this->getSql()->select();

        $select->columns(array('st_gerente'))
                ->join('TB_FUNCIONARIO', 'TB_FUNCIONARIO.CD_CARGO = TB_CARGO.CD_CARGO')
                ->join('AdmUsuario', 'TB_FUNCIONARIO.CD_USUARIO = AdmUsuario.ideAdmUsuario')
                ->where(array('TB_FUNCIONARIO.DT_DESLIGAMENTO' => null))
                ->where(array('AdmUsuario.DESLOGIN' => $username));

        //echo $select->getSqlString();exit;

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!$row) {
            throw new \Exception("Cargo / Usu�rio $username  n�o existe no banco de dados!");
        }

        return $row;
    }

    public function getNrLancamentoCaixa()
    {
        $statement = $this->adapter->query("select MAX(NR_LANCAMENTO_CAIXA)+1 NR_LANCAMENTO_CAIXA from TB_CAIXA");
        $results = $statement->execute();
        $rowResult = $results->current();
        return $rowResult["NR_LANCAMENTO_CAIXA"];
    }

    public function getId($id) {
        $id = (int) $id;

        $select = $this->getSql()->select();
        $select->columns(array('cd_cargo',
                    'ds_cargo',
                    'st_motorista',
                    'st_vendedor',
                    'st_telemarketing',
                    'st_gerente',
                    'st_tecnico',
                ))
                ->where(array('cd_cargo' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            throw new \Exception("Cargo $id  n�o existe no banco de dados!");
        }

        return $row;
    }

    public function getListaCaixasDisponíveis($cdLoja, $dtEntrada)
    {
        $statement = $this->adapter->query("select *
                                            from TB_CAIXA_FUNCIONARIO tcf
                                            join TB_FUNCIONARIO tf on tcf.CD_Funcionario = tf.CD_FUNCIONARIO
                                            where tcf.CD_LOJA = ?
                                                and tcf.DT_ENTRADA = ?
                                                and tcf.ST_ATIVIDADE = 'L' ");

        $results = $statement->execute(array($cdLoja, $dtEntrada));
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function insereCaixa($dados)
    {
        try
        {
            return $this->insert($dados);
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function atualizaValoresCaixa($vlTotalBruto, $vlTotalLiquido, $cdLoja, $nrLancamentoCaixa, $nrCaixa)
    {
        try
        {
            return $this->update(array('VL_TOTAL_BRUTO' => $vlTotalBruto,
                                        'VL_TOTAL_LIQUIDO' => $vlTotalLiquido
                                        ),
                                array('CD_LOJA' => $cdLoja,
                                    'NR_LANCAMENTO_CAIXA' => $nrLancamentoCaixa,
                                    'NR_CAIXA' => $nrCaixa));

        } catch (\Exception $ex) {
            return false;
        }
    }

    public function insereCaixaPagamento($dados)
    {
        try
        {
            $statement = $this->adapter->query("insert into TB_CAIXA_PAGAMENTO (CD_LOJA,NR_CAIXA,NR_LANCAMENTO_CAIXA,CD_PLANO_PAGAMENTO, NR_PARCELA,CD_TIPO_PAGAMENTO,NR_PEDIDO_DEVOLUCAO,
                                                                                NR_CGC_CPF_EMISSOR,DS_EMISSOR,NR_FONE_EMISSOR,CD_CLIENTE,CD_FINANCEIRA,NR_BOLETO,CD_CARTAO,CD_BANCO,CD_AGENCIA,
                                                                                NR_CONTA,NR_CHEQUE,DT_EMISSAO,DT_VENCIMENTO,VL_DOCUMENTO,ST_CANCELADO,NR_QTDE_PARCELAS,NR_Carta_Credito,vl_troco)
                                                 values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $statement->execute($dados);

            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function pesquisaMovimentacaoCaixa($cdLoja, $nrCaixa, $dtMovimento, $cdPesquisaPor, $txtProcurar)
    {
        $sql = "SELECT
                    c.NR_CAIXA,
                    c.NR_LANCAMENTO_CAIXA,
                    ISNULL(c.NR_DOCUMENTO,' ') NR_DOCUMENTO,
                    CONVERT(VARCHAR(10), c.DT_MOVIMENTO, 103) AS DT_MOVIMENTO,
                    c.VL_TOTAL_LIQUIDO,
                    tmc.DS_TIPO_MOVIMENTO_CAIXA,
                    c.DS_COMPL_MOVIMENTO,
                    tcf.DS_CLASSE_FINANCEIRA,
                    CASE WHEN ( c.ST_CANCELADO = 'N' ) THEN 'Não' ELSE 'Sim' END AS ST_CANCELADO
                FROM TB_CAIXA c
                JOIN TB_TIPO_MOVIMENTO_CAIXA tmc on c.CD_TIPO_MOVIMENTO_CAIXA = tmc.CD_TIPO_MOVIMENTO_CAIXA
                JOIN TB_CLASSIFICACAO_FINANCEIRA tcf on c.CD_CLASSE_FINANCEIRA = tcf.CD_CLASSE_FINANCEIRA
                WHERE CD_LOJA = ?
                    AND NR_CAIXA = ?
                    AND DT_MOVIMENTO = ? ";

        $sql .= ($cdPesquisaPor == 2) ? " AND c.NR_LANCAMENTO_CAIXA = ?" : "";
        $sql .= ($cdPesquisaPor == 4) ? " AND c.NR_DOCUMENTO = ?" : "";
        if($cdPesquisaPor == 3)
        {
            $sql .= " AND UPPER(c.DS_COMPL_MOVIMENTO) LIKE UPPER(?) ";
            $txtProcurar = "%".trim($txtProcurar)."%";
        }

        $statement = $this->adapter->query($sql);
        $results = $statement->execute(array($cdLoja, $nrCaixa, $dtMovimento, trim($txtProcurar)));
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function relatorioCaixa($cdLoja, $cdOperador = null, $dtInicio = null, $dtFinal = null)
    {
        $sql = "SELECT DISTINCT
                     L.DS_FANTASIA, C.NR_CAIXA, F.DS_FUNCIONARIO,
                    CONVERT(VARCHAR(10), CF.DT_ENTRADA, 103) DT_ENTRADA,
                    CONVERT(VARCHAR(10), CF.DT_SAIDA, 103) DT_SAIDA
                FROM TB_CAIXA C
                    INNER JOIN TB_LOJA L ON L.CD_LOJA = C.CD_LOJA
                    INNER JOIN TB_CAIXA_FUNCIONARIO CF ON CF.CD_LOJA = C.CD_LOJA
                    INNER JOIN TB_FUNCIONARIO F ON F.CD_FUNCIONARIO = CF.CD_Funcionario
                WHERE 1=1 ";

        if($cdOperador){
            $sql .= " AND CF.CD_FUNCIONARIO = {$cdOperador} ";
        }

        if($dtFinal){
            $sql .= " AND CF.DT_ENTRADA between '{$dtInicio} 00:00:00'  and '{$dtFinal} 23:59:59' ";
        } elseif($dtInicio) {
            $sql .= " AND CF.DT_SAIDA >= {$dtInicio} 00:00:00 ";
        }

        if($cdLoja){
            $sql .= " AND C.CD_LOJA = {$cdLoja} ";
            $sql .= " GROUP BY CD_LOJA, DT_MOVIMENTO ORDER BY CD_LOJA, DT_MOVIMENTO";
        }

        $statement = $this->adapter->query($sql);
        return $statement->execute();
//var_dump($sql); exit;
        return $return;

    }

    public function calculaValorTotalPorCaixa(  $calculo, $cdLoja, $numCaixa,  $dataCaixa){


        switch ($calculo){
            case 'dinheiro':
                $sql = " SELECT
    				    	VALOR = SUM( VL_DOCUMENTO )
						 FROM
    					    TB_CAIXA_PAGAMENTO  CXP
						 WHERE
	    				    CXP.CD_LOJA      = ? AND
						    CXP.NR_CAIXA     = ? AND
							CXP.DT_EMISSAO   = ? AND
							CXP.CD_TIPO_PAGAMENTO = 1 AND
							CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));

                break;
            case 'cheque':
                $sql = " SELECT
    				    	VALOR = SUM( VL_DOCUMENTO )
						 FROM
    					    TB_CAIXA_PAGAMENTO  CXP
						 WHERE
	    				    CXP.CD_LOJA      = ? AND
						    CXP.NR_CAIXA     = ? AND
							CXP.DT_EMISSAO   = ? AND
							CXP.CD_TIPO_PAGAMENTO = 2 AND
							CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'cheque-avista':
                $sql = "SELECT VALOR = SUM( VL_DOCUMENTO )
							    	FROM TB_CAIXA_PAGAMENTO  CXP
							    	WHERE
	    								CXP.CD_LOJA      		= ? AND
								    	CXP.NR_CAIXA     		= ? AND
								    	CXP.DT_EMISSAO   		= ? AND
								    	CXP.CD_TIPO_PAGAMENTO 	= 2 AND
								    	CXP.DT_EMISSAO 			=  CXP.DT_VENCIMENTO AND
								    	CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'cheque-pre':
                $sql = "SELECT VALOR = SUM( VL_DOCUMENTO )
							    	FROM TB_CAIXA_PAGAMENTO  CXP
							    	WHERE
	    								CXP.CD_LOJA      		= ? AND
								    	CXP.NR_CAIXA     		= ? AND
								    	CXP.DT_EMISSAO   		= ? AND
								    	CXP.CD_TIPO_PAGAMENTO 	= 2 AND
								    	CXP.DT_EMISSAO 			<>  CXP.DT_VENCIMENTO AND
								    	CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'cartao':
                $sql = "	SELECT
    								VALOR = SUM( VL_DOCUMENTO )
						    	FROM
    								TB_CAIXA_PAGAMENTO  CXP
						    	WHERE
	    							CXP.CD_LOJA      = ? AND
							    	CXP.NR_CAIXA     = ? AND
							    	CXP.DT_EMISSAO   = ? AND
							    	CXP.CD_TIPO_PAGAMENTO = 5 AND
							    	CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'cartao-parcelado':
                $sql = "	SELECT
    								VALOR = SUM( VL_DOCUMENTO )
						    	FROM
    								TB_CAIXA_PAGAMENTO  CXP
						    	WHERE
	    							CXP.CD_LOJA      = ? AND
							    	CXP.NR_CAIXA     = ? AND
							    	CXP.DT_EMISSAO   = ? AND
							    	CXP.CD_TIPO_PAGAMENTO = 12 AND
							    	CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'cartao-manual':
                $sql = "	SELECT
    								VALOR = SUM( VL_DOCUMENTO )
						    	FROM
    								TB_CAIXA_PAGAMENTO  CXP
						    	WHERE
	    							CXP.CD_LOJA      = ? AND
							    	CXP.NR_CAIXA     = ? AND
							    	CXP.DT_EMISSAO   = ? AND
							    	CXP.CD_TIPO_PAGAMENTO = 11 AND
							    	CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'carta-credito':
                $sql = "	SELECT
    								VALOR = SUM( VL_DOCUMENTO )
						    	FROM
    								TB_CAIXA_PAGAMENTO  CXP
						    	WHERE
	    							CXP.CD_LOJA      = ? AND
							    	CXP.NR_CAIXA     = ? AND
							    	CXP.DT_EMISSAO   = ? AND
							    	CXP.CD_TIPO_PAGAMENTO = 10 AND
							    	CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return  $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'boleto':
                $sql = "	SELECT
    								VALOR = SUM( VL_DOCUMENTO )
						    	FROM
    								TB_CAIXA_PAGAMENTO  CXP
						    	WHERE
	    							CXP.CD_LOJA      		= ? AND
							    	CXP.NR_CAIXA     		= ? AND
							    	CXP.DT_EMISSAO   		= ? AND
							    	CXP.CD_TIPO_PAGAMENTO 	= 3 AND
							    	CXP.ST_CANCELADO <> 'S'
    			";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'financeira':
                $sql = "	SELECT
    									VALOR = SUM( VL_DOCUMENTO )
							    	FROM
    									TB_CAIXA_PAGAMENTO  CXP
							    	WHERE
    									CXP.CD_LOJA           = ? AND
								    	CXP.NR_CAIXA     	  = ? AND
								    	CXP.DT_EMISSAO   	  = ? AND
								    	CXP.CD_TIPO_PAGAMENTO = 4 AND
								    	CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'deposito':
                $sql = "	SELECT
    								VALOR = SUM( VL_DOCUMENTO )
						    	FROM
    								TB_CAIXA_PAGAMENTO  CXP
						    	WHERE
	    							CXP.CD_LOJA      = ? AND
							    	CXP.NR_CAIXA     = ? AND
							    	CXP.DT_EMISSAO   = ? AND
							    	CXP.CD_TIPO_PAGAMENTO = 8 AND
							    	CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'devolucao':
                $sql = "	SELECT
    								VALOR = SUM( VL_DOCUMENTO )
						    	FROM
    								TB_CAIXA_PAGAMENTO  CXP
						    	WHERE
	    							CXP.CD_LOJA      = ? AND
							    	CXP.NR_CAIXA     = ? AND
							    	CXP.DT_EMISSAO   = ? AND
							    	CXP.CD_TIPO_PAGAMENTO = 6 AND
							    	CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'ticket':
                $sql = "	SELECT
    								VALOR = SUM( VL_DOCUMENTO )
						    	FROM
    								TB_CAIXA_PAGAMENTO  CXP
						    	WHERE
	    							CXP.CD_LOJA      = ? AND
							    	CXP.NR_CAIXA     = ? AND
							    	CXP.DT_EMISSAO   = ? AND
							    	CXP.CD_TIPO_PAGAMENTO = 7 AND
							    	CXP.ST_CANCELADO <> 'S'";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'entrada':
                $sql = "	SELECT
    								VALOR = SUM (VL_TOTAL_LIQUIDO)
								FROM TB_CAIXA
								WHERE
    									CD_LOJA = ?
									AND NR_CAIXA = ?
									AND DT_MOVIMENTO = ?
									AND ST_CANCELADO <> 'S'
									AND CD_TIPO_MOVIMENTO_CAIXA NOT IN (1, 2, 4, 5, 6, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29)
							";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'saida':
                $sql = "	SELECT
    								VALOR = SUM (VL_TOTAL_LIQUIDO)
								FROM TB_CAIXA
								WHERE
    									CD_LOJA = ?
									AND NR_CAIXA = ?
									AND DT_MOVIMENTO = ?
									AND ST_CANCELADO <> 'S'
									AND CD_TIPO_MOVIMENTO_CAIXA NOT IN (1, 3, 4, 5, 6, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19)
							";

                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
            case 'carta-credito':
                $sql = "	SELECT
    								VALOR = SUM( VL_DOCUMENTO )
						    	FROM
    								TB_CAIXA_PAGAMENTO  CXP
						    	WHERE
	    							CXP.CD_LOJA      = ? AND
							    	CXP.NR_CAIXA     = ? AND
							    	CXP.DT_EMISSAO   = ? AND
							    	CXP.CD_TIPO_PAGAMENTO = 10 AND
							    	CXP.ST_CANCELADO <> 'S'";
                $sql = $this->adapter->query($sql);

                return $sql->execute(array($cdLoja,$numCaixa,$dataCaixa));
                break;
        }
    }

    public function calculaValorTotalCaixa(  $calculo, $cdLoja,  $dataCaixa, $dataFim = null){


        switch ($calculo){
               case 'caixa':
                    $sql = "SELECT	CD_LOJA,
                                                DT_MOVIMENTO,
                                                VL_TOTAL_BRUTO    = SUM( VL_TOTAL_BRUTO   ),
                                                VL_TOTAL_LIQUIDO  = SUM( VL_TOTAL_LIQUIDO )
                                        FROM TB_CAIXA
                                        WHERE DT_MOVIMENTO BETWEEN ? AND ?
                                              AND CD_LOJA = ?
                                              AND isNull( ST_CANCELADO, 'N' ) <> 'S'
                                              AND CD_TIPO_MOVIMENTO_CAIXA IN ( 1, 4, 6 )
                                        GROUP BY CD_LOJA, DT_MOVIMENTO
                                        ORDER BY CD_LOJA, DT_MOVIMENTO";

                    $sql = $this->adapter->query($sql);

                    return $sql->execute(array($dataCaixa, $dataFim, $cdLoja));
               break;

               case 'totalOs':
                    $sql = "SELECT SUM( IsNull( P.VL_TOTAL_LIQUIDO, 0 ) ) AS TotalOs
                                    FROM TB_CAIXA A
                                    INNER JOIN RL_OS_CAIXA RC ON A.CD_LOJA = RC.CD_LOJA
                                        AND A.Nr_Caixa = RC.NR_CAIXA
                                        AND A.NR_LANCAMENTO_CAIXA = RC.NR_LANCAMENTO_CAIXA
                                        INNER JOIN TB_ORDEM_SERVICO P ON P.CD_LOJA   = RC.CD_LOJA
                                            AND P.CD_ORDEM_SERVICO = RC.CD_ORDEM_SERVICO
                                            WHERE A.CD_LOJA = ?
                                                AND isNull( A.ST_CANCELADO, 'N' ) = 'N'
                                                AND A.CD_TIPO_MOVIMENTO_CAIXA IN ( 6 )
                                                AND P.CD_SITUACAO_OS = '4'
                                                AND DT_Movimento = ?";

                    $sql = $this->adapter->query($sql);

                    $result = $sql->execute(array($cdLoja, $dataCaixa));
                   foreach($result as $row){
                       return $row['TotalOs'];
                   }
               break;

               case 'frete':
                    $sql = "select TotalFrete = Cast( Sum( p.VL_Frete ) as numeric( 15, 2 ) )
                            from tb_pedido p
                            inner join RL_CAIXA_PEDIDO rl on p.cd_loja = rl.cd_loja and p.nr_pedido = rl.nr_pedido
                            inner join tb_caixa CX on     rl.cd_loja = cx.cd_loja
                            and rl.nr_caixa = cx.nr_caixa
                            and rl.nr_lancamento_caixa = cx.nr_lancamento_caixa
                            where P.CD_LOJA = ?
                            AND ST_PEDIDO    = 'F'
                            AND DT_MOVIMENTO = ?";

                    $sql = $this->adapter->query($sql);

                    $result = $sql->execute(array($cdLoja, $dataCaixa));
                   foreach($result as $row){
                       return $row['TotalFrete'];
                   }
               break;

               case 'dinheiro':
                    $sql = " SELECT
                                VALOR = SUM( VL_DOCUMENTO )
                             FROM
                                TB_CAIXA_PAGAMENTO  CXP
                             WHERE
                                CXP.CD_LOJA      = ? AND
                                CXP.DT_EMISSAO   = ? AND
                                CXP.CD_TIPO_PAGAMENTO = 1 AND
                                CXP.ST_CANCELADO <> 'S'";

                    $sql = $this->adapter->query($sql);

                   $result = $sql->execute(array($cdLoja, $dataCaixa));
                   foreach($result as $row){
                        return $row['VALOR'];
                   }
               break;

               case 'cheque':
                    $sql = " SELECT
                                VALOR = SUM( VL_DOCUMENTO )
                             FROM
                                TB_CAIXA_PAGAMENTO  CXP
                             WHERE
                                CXP.CD_LOJA      = ? AND
                                CXP.DT_EMISSAO   = ? AND
                                CXP.CD_TIPO_PAGAMENTO = 2 AND
                                CXP.ST_CANCELADO <> 'S'";

                    $sql = $this->adapter->query($sql);

                   $result = $sql->execute(array($cdLoja, $dataCaixa));
                   foreach($result as $row){
                       return $row['VALOR'];
                   }
               break;

               case 'cheque-pre':
                    $sql = "SELECT VALOR = SUM( VL_DOCUMENTO )
                                        FROM TB_CAIXA_PAGAMENTO  CXP
                                        WHERE
                                            CXP.CD_LOJA      		= ? AND
                                            CXP.DT_EMISSAO   		= ? AND
                                            CXP.CD_TIPO_PAGAMENTO 	= 2 AND
                                            CXP.DT_EMISSAO 			<>  CXP.DT_VENCIMENTO AND
                                            CXP.ST_CANCELADO <> 'S'";

                    $sql = $this->adapter->query($sql);

                   $result = $sql->execute(array($cdLoja, $dataCaixa));
                   foreach($result as $row){
                       return $row['VALOR'];
                   }
               break;

               case 'cartao':
                    $sql = "	SELECT
                                        VALOR = SUM( VL_DOCUMENTO )
                                    FROM
                                        TB_CAIXA_PAGAMENTO  CXP
                                    WHERE
                                        CXP.CD_LOJA      = ? AND
                                        CXP.DT_EMISSAO   = ? AND
                                        CXP.CD_TIPO_PAGAMENTO = 5 AND
                                        CXP.ST_CANCELADO <> 'S'";

                    $sql = $this->adapter->query($sql);

                   $result = $sql->execute(array($cdLoja, $dataCaixa));
                   foreach($result as $row){
                       return $row['VALOR'];
                   }
               break;

               case 'boleto':
                    $sql = "	SELECT
                                        VALOR = SUM( VL_DOCUMENTO )
                                    FROM
                                        TB_CAIXA_PAGAMENTO  CXP
                                    WHERE
                                        CXP.CD_LOJA      		= ? AND
                                        CXP.DT_EMISSAO   		= ? AND
                                        CXP.CD_TIPO_PAGAMENTO 	= 3 AND
                                        CXP.ST_CANCELADO <> 'S'
                    ";

                    $sql = $this->adapter->query($sql);

                   $result = $sql->execute(array($cdLoja, $dataCaixa));
                   foreach($result as $row){
                       return $row['VALOR'];
                   }
               break;

               case 'deposito':
                    $sql = "	SELECT
                                        VALOR = SUM( VL_DOCUMENTO )
                                    FROM
                                        TB_CAIXA_PAGAMENTO  CXP
                                    WHERE
                                        CXP.CD_LOJA      = ? AND
                                        CXP.DT_EMISSAO   = ? AND
                                        CXP.CD_TIPO_PAGAMENTO = 8 AND
                                        CXP.ST_CANCELADO <> 'S'";

                    $sql = $this->adapter->query($sql);

                   $result = $sql->execute(array($cdLoja, $dataCaixa));
                   foreach($result as $row){
                       return $row['VALOR'];
                   }
               break;

               case 'entrada':
                    $sql = "SELECT SUM(VL_Total_Liquido) as VALOR
                            FROM (	SELECT C.DT_Movimento,VL_Total_Liquido
                                    FROM TB_Caixa C
                                        WHERE c.CD_Tipo_Movimento_Caixa in ( 3 )
                                        AND C.CD_LOJA = ?
                                        AND C.DT_Movimento = ?
                                        AND IsNull( C.ST_Cancelado, 'N' ) = 'N') P
                            GROUP BY DT_Movimento ";

                    $sql = $this->adapter->query($sql);

                   $result = $sql->execute(array($cdLoja, $dataCaixa));
                   foreach($result as $row){
                       return $row['VALOR'];
                   }
               break;

               case 'saida':
                    $sql = "SELECT SUM(VL_Total_Liquido) as VALOR
                            FROM (	SELECT C.DT_Movimento,VL_Total_Liquido
                                    FROM TB_Caixa C
                                        WHERE c.CD_Tipo_Movimento_Caixa in ( 2 )
                                        AND C.CD_LOJA = ?
                                        AND C.DT_Movimento = ?
                                        AND IsNull( C.ST_Cancelado, 'N' ) = 'N') P
                            GROUP BY DT_Movimento ";

                    $sql = $this->adapter->query($sql);

                   $result = $sql->execute(array($cdLoja, $dataCaixa));
                   foreach($result as $row){
                       return $row['VALOR'];
                   }
               break;
        }
    }

    function detalhamentoCaixa($cdLoja, $numCaixa, $dataCaixa){
        $sql = "SELECT	CX.CD_LOJA,
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
                        LEFT JOIN TB_CAIXA CX ON CX.CD_TIPO_MOVIMENTO_CAIXA = TMV.CD_TIPO_MOVIMENTO_CAIXA
                        LEFT JOIN TB_LOJA LJ  ON LJ.CD_LOJA = CX.CD_LOJA
                        INNER JOIN TB_CAIXA_FUNCIONARIO CFN ON CFN.CD_LOJA = CX.CD_LOJA
                            AND CFN.NR_CAIXA = CX.NR_CAIXA
                            AND CX.DT_MOVIMENTO BETWEEN CFN.DT_ENTRADA AND ( ISNULL( CFN.DT_SAIDA, CFN.DT_ENTRADA ) )
                        LEFT JOIN TB_FUNCIONARIO FUN ON FUN.CD_LOJA = CFN.CD_LOJA
                            AND FUN.CD_FUNCIONARIO = CFN.CD_FUNCIONARIO
                        LEFT JOIN RL_CAIXA_PEDIDO RCP ON RCP.CD_LOJA = CX.CD_LOJA
                            AND RCP.NR_CAIXA = CX.NR_CAIXA
                            AND RCP.NR_LANCAMENTO_CAIXA = CX.NR_LANCAMENTO_CAIXA
                        LEFT JOIN RL_OS_CAIXA ROP ON ROP.CD_LOJA = CX.CD_LOJA
                            AND ROP.NR_CAIXA = CX.NR_CAIXA
                            AND ROP.NR_LANCAMENTO_CAIXA = CX.NR_LANCAMENTO_CAIXA
                WHERE CX.CD_LOJA = ?
                    AND CX.NR_CAIXA = ?
                    AND CFN.DT_ENTRADA = ?
                    AND CX.ST_CANCELADO <> 'S'
                ORDER BY CASE WHEN RCP.NR_PEDIDO IS NULL AND ROP.CD_ORDEM_SERVICO IS NULL THEN 0 ELSE 1 END,
                    CX.CD_TIPO_MOVIMENTO_CAIXA, CX.NR_LANCAMENTO_CAIXA";

        $stList = $this->adapter->query($sql);
            $return = $stList->execute(array($cdLoja,$numCaixa,$dataCaixa));

        return $return;
    }
}
