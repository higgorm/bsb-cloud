<?php

namespace Application\Model;

use Zend\Db\Sql\Where;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;

class MercadoriaTable extends AbstractTableGateway {

    protected $table = "TB_MERCADORIA";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Mercadoria());
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }
	
	public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10")
    {
        $select = new Select();
        //$where = new Where();
		
		$where = 'M.DT_EXCLUSAO IS NULL  AND R.CD_PRAZO = 1';
        foreach ($param as $field => $search) {
            $where = $where . ' AND ' . $field . " like '%" . $search . "%'";  
        }

        $select->from(array('M' => $this->table))
            ->join(array('R' => 'RL_PRAZO_LIVRO_PRECOS'), ' M.CD_MERCADORIA = R.CD_MERCADORIA','VL_PRECO_VENDA','LEFT')
            ->where($where)
            ->order("M.DS_MERCADORIA DESC");

        //$select->quantifier('DISTINCT');
       // echo $select->getSqlString();
        //exit;

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function find($id) {
        $id = (int) $id;

        /*$select = $this->getSql()->select();
        $select->columns(array('cd_mercadoria','ds_mercadoria','VL_PRECO_VENDA'))
                ->where(array('cd_mercadoria' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();*/
		
		$statement = $this->adapter->query("SELECT TOP 1 M.*,
													R.VL_PRECO_VENDA, R.VL_PRECO_VENDA_PROMOCAO,
													LP.VL_PRECO_COMPRA
											FROM TB_MERCADORIA M
                                                LEFT JOIN RL_PRAZO_LIVRO_PRECOS R on M.CD_MERCADORIA = R.CD_MERCADORIA
												LEFT JOIN TB_LIVRO_PRECOS LP ON LP.CD_MERCADORIA = M.CD_MERCADORIA
                                            WHERE M.CD_MERCADORIA = ? and R.CD_PRAZO = 1 AND LP.CD_LIVRO = 1");

        $row = $statement->execute(array($id));
		
        if (!$row) {
            throw new \Exception("Servico $id  não existe no banco de dados!");
        }

        return $row;
    }


    public function recuperaMercadoriaAtendimento($cdMercadoria)
    {
        $statement = $this->adapter->query("SELECT m.CD_MERCADORIA,m.DS_MERCADORIA,
                                                r.VL_PRECO_VENDA as VL_NOMINAL,r.VL_PRECO_VENDA_PROMOCAO
                                            FROM TB_MERCADORIA m
                                                JOIN RL_PRAZO_LIVRO_PRECOS r on m.CD_MERCADORIA = r.CD_MERCADORIA
                                            WHERE m.CD_MERCADORIA = ?");

        $results = $statement->execute(array($cdMercadoria));
        return $results->current();
    }

    public function getComboPrecoServico($cdMercadoria){
        $statement = $this->adapter->query("select m.CD_MERCADORIA,m.DS_MERCADORIA, l.VL_PRECO_VENDA, DT_VALIDADE_PROMOCAO,
                                             VL_PRECO_VENDA_PROMOCAO = CASE WHEN l.DT_VALIDADE_PROMOCAO > GETDATE() THEN l.VL_PRECO_VENDA_PROMOCAO ELSE '0.0000' END
                                            from TB_MERCADORIA m
                                            left join RL_PRAZO_LIVRO_PRECOS r on m.CD_MERCADORIA = r.CD_MERCADORIA
                                            left join TB_LIVRO_PRECOS l on l.CD_MERCADORIA = m.CD_MERCADORIA
                                            where m.CD_MERCADORIA = ?");

        $results = $statement->execute(array($cdMercadoria));
        return $results->current();
    }

    public function getValorPrecoVenda($cdMercadoria)
    {
        $statement = $this->adapter->query("SELECT
                                                    VL_PRECO_VENDA
                                                FROM
                                                    RL_PRAZO_LIVRO_PRECOS
                                                WHERE
                                                        CD_LIVRO = 1
                                                    AND CD_PRAZO = 1
                                                    AND CD_MERCADORIA = ? ");
        $results = $statement->execute(array($cdMercadoria));
        $rowResult = $results->current();
        return $rowResult["VL_PRECO_VENDA"];
    }

    public function getValorPromocao($cdMercadoria)
    {
        $statement = $this->adapter->query("SELECT
                                                VL_PRECO_VENDA = CASE WHEN DT_VALIDADE_PROMOCAO > GETDATE() THEN VL_PRECO_VENDA_PROMOCAO ELSE VL_PRECO_VENDA END
                                            FROM
                                                TB_Livro_Precos
                                            WHERE
                                                    CD_LIVRO        = 1
                                                AND CD_MERCADORIA   = ? ");
        $results = $statement->execute(array($cdMercadoria));
        $rowResult = $results->current();
        return $rowResult["VL_PRECO_VENDA"];
    }

    public function listMercadoria()
    {

        $statement = $this->adapter->query('SELECT CD_MERCADORIA, DS_MERCADORIA, DS_REFERENCIA, DS_MODELO_COR FROM '.$this->table.' WHERE 1=1 AND DT_EXCLUSAO IS NULL
           ORDER BY DS_MERCADORIA ');
        $result = $statement->execute();

        $selectData = array('' => 'Selecione');

        foreach ($result as $res) {
            $selectData[$res['CD_MERCADORIA']] = $res['DS_MERCADORIA'];
        }

        return $selectData;
    }

    public function recuperaMercadoriaPedido($nuPedido)
        //CONVERT(VARCHAR, CONVERT(MONEY, 12345678.90) )
    {
        $sql = "SELECT M.CD_MERCADORIA, 
                      M.DS_MERCADORIA, 
                      PM.NR_QTDE_VENDIDA  AS QTD, 
                      PM.VL_DESCONTO_MERC  AS NR_DESCONTO,
                      CONVERT(VARCHAR, CONVERT(MONEY, ( PM.VL_PRECO_VENDA - (	PM.VL_PRECO_VENDA /100) * PM.VL_DESCONTO_MERC)) ) AS  VL_DESCONTO,
                      CONVERT(VARCHAR, CONVERT(MONEY,  PM.VL_PRECO_VENDA) )  AS  VL_NOMINAL, 
                      CONVERT(VARCHAR, CONVERT(MONEY, ( PM.VL_PRECO_VENDA - (	PM.VL_PRECO_VENDA /100) * PM.VL_DESCONTO_MERC)* PM.NR_QTDE_VENDIDA) ) AS VL_TOTAL, 
                      CONVERT(VARCHAR, CONVERT(MONEY, PM.VL_TOTAL_BRUTO) )  AS VL_TOTAL_BRUTO , 
                      ISNULL(P.DS_LOCAL_RETIRADA, '-')  AS RETIRADA,
                    M.ST_SERVICO
                FROM TB_MERCADORIA M
                    LEFT JOIN TB_PEDIDO_MERCADORIA PM ON PM.CD_MERCADORIA = M.CD_MERCADORIA
                    INNER JOIN TB_PEDIDO P ON P.NR_PEDIDO = PM.NR_PEDIDO
                WHERE PM.NR_PEDIDO = ?";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute(array($nuPedido));


        $res = array();
        foreach ($result as $valeus) {
            $res[] = $valeus;
        }

        return $res;
    }

    public function pesquisaMercadoriaPorParamentro($arrParam)
    {
        $select = "SELECT M.CD_MERCADORIA, M.DS_MERCADORIA, E.NR_QTDE_ESTOQUE, E.NR_QTDE_RESERVA,
                        (E.NR_QTDE_ESTOQUE - E.NR_QTDE_RESERVA) QTDE_DISPONIVEL,PLP.VL_PRECO_VENDA VL_PRECO_AVISTA,
						cast(PLP.VL_PRECO_VENDA AS NUMERIC(15,2)) as VL_PRECO_VENDA,
						PLP.VL_PRECO_VENDA_PROMOCAO, 
						M.NR_PERCENTUAL_ICMS_INTERNO, M.NR_PERCENTUAL_ICMS_EXTERNO, M.CD_UNIDADE_VENDA, M.ST_SERVICO,
						M.VL_ISS
                    FROM TB_MERCADORIA M
                        LEFT JOIN TB_ESTOQUE E ON E.CD_MERCADORIA = M.CD_MERCADORIA
                        INNER JOIN RL_PRAZO_LIVRO_PRECOS PLP ON PLP.CD_MERCADORIA = M.CD_MERCADORIA
                        LEFT JOIN TB_FABRICANTE F ON F.CD_FABRICANTE = M.CD_FABRICANTE
                    WHERE M.DT_EXCLUSAO IS NULL AND PLP.CD_PRAZO=1 ";

        if($arrParam['st_tipo_pesquisa'] == 1){
            $select .= " AND M.CD_MERCADORIA = ".$arrParam['codigoMercadoria'];
        }

        if($arrParam['st_tipo_pesquisa'] == 2){
            $select .= " AND UPPER(M.DS_MERCADORIA) like '%".strtoupper($arrParam['codigoMercadoria'])."%' ";
        }

        if($arrParam['st_tipo_pesquisa'] == 3){
            $select .= " AND UPPER(M.DS_REFERENCIA) like '%".strtoupper($arrParam['codigoMercadoria'])."%' ";
        }

        if($arrParam['st_tipo_pesquisa'] == 4){
            $select .= " AND  UPPER(M.DS_MARCA) like '%".strtoupper($arrParam['codigoMercadoria'])."%' ";
        }

        if($arrParam['st_tipo_pesquisa'] == 5){
            $select .= " AND  UPPER(M.DS_MODELO_COR) like '%".strtoupper($arrParam['codigoMercadoria'])."%' ";
        }

        if($arrParam['st_tipo_pesquisa'] == 6){
            $select .= " AND  UPPER(M.DS_MERCADORIA) like '%".strtoupper($arrParam['codigoMercadoria'])."%' ";
        }

        if($arrParam['st_tipo_pesquisa'] == 7){
            $select .= " AND  UPPER(M.DS_CODIGO_FORNECEDOR) = '".strtoupper($arrParam['codigoMercadoria'])."' ";
        }

        if($arrParam['st_tipo_pesquisa'] == 8){
            $select .= " AND  UPPER(F.DS_FANTASIA) like '%".strtoupper($arrParam['codigoMercadoria'])."%' ";
        }

        if($arrParam['st_tipo_pesquisa'] == 9){
            $select .= " AND  UPPER(M.OBSERVACAO) like '%".strtoupper($arrParam['codigoMercadoria'])."%' ";
        }
		
        //$param = array($arrParam['codigoMercadoria']);
        $statement = $this->adapter->query($select);
        $results = $statement->execute();

        return iterator_to_array($results,false);
    }
    public function getTiposMercadoriaSecao(){

        $select = "SELECT * FROM TB_TIPO_MERCADORIA_SECAO";

        $statement = $this->adapter->query($select);
        $result = $statement->execute();

        return $result;

    }
	
	public function insere( $array ){
		$sql = new Sql($this->adapter);
		$insert = $sql->insert( $this->table );
		$insert->values($array);
		
		$statement = $this->adapter->query($sql->getSqlStringForSqlObject($insert));
		$results = $statement->execute();
		
		return $results;
	}
	
	public function insere_preco( $array ){
		$sql = new Sql($this->adapter);
		$insert = $sql->insert('RL_PRAZO_LIVRO_PRECOS');
		$insert->values($array);
		
		$statement = $this->adapter->query($sql->getSqlStringForSqlObject($insert));
		$results = $statement->execute();
		
		return $results;
	}
	
	public function insere_livro_preco( $array ){
		$sql = new Sql($this->adapter);
		$insert = $sql->insert('TB_LIVRO_PRECOS');
		$insert->values($array);
		
		$statement = $this->adapter->query($sql->getSqlStringForSqlObject($insert));
		$results = $statement->execute();
		
		return $results;
	}
	
	public function atualiza_mercadoria( $id, $array ){

		$sql = new Sql($this->adapter);
		$update = $sql->update();
		$update->table($this->table);
		$update->set($array);

		$update->where(array('CD_MERCADORIA' => $id));
		
		$statement = $sql->prepareStatementForSqlObject($update);
		$results = $statement->execute();

		return $results;
	}
	
	public function atualiza_preco( $id, $array ){

		$sql = new Sql($this->adapter);
		$update = $sql->update();
		$update->table('RL_PRAZO_LIVRO_PRECOS');
		$update->set($array);

		$update->where(array('CD_MERCADORIA' => $id, 'CD_PRAZO' => '1', 'CD_LIVRO' => '1'));

		$statement = $sql->prepareStatementForSqlObject($update);
		$results = $statement->execute();
		
		return $results;
	}
	
	public function atualiza_livro_preco( $id, $array ){

		$sql = new Sql($this->adapter);
		$update = $sql->update();
		$update->table('TB_LIVRO_PRECOS');
		$update->set($array);

		$update->where(array('CD_MERCADORIA' => $id, 'CD_LIVRO' => '1'));

		$statement = $sql->prepareStatementForSqlObject($update);
		$results = $statement->execute();
		
		return $results;
	}
	
	public function getNextId(){
		
		$statement = $this->adapter->query('SELECT COALESCE(MAX(CD_MERCADORIA),0) + 1 AS nextID FROM '.$this->table );
		
		$results = $statement->execute();
		$returnArray = array();
		
		foreach ($results as $result){
			$return = $result['nextID'];
		}
		return $return;
	}
	
//	public function getMercadoriasNota(){
//		$statement = $dbAdapter->query("SELECT  M.CD_MERCADORIA,
//												M.DS_MERCADORIA
//										FROM	TB_MERCADORIA M
//										INNER JOIN	  TB_ESTOQUE E ON M.CD_MERCADORIA = E.CD_MERCADORIA
//											WHERE E.CD_LOJA 		  = ?
//												AND M.DT_EXCLUSAO is null
//    										ORDER BY
//    							  				M.CD_MERCADORIA");
//
//		return $statement->execute(array($session->cdLoja));
//	}

    public function getComboMercadorias($cdLoja) {
        $statement = $this->adapter->query("SELECT  M.CD_MERCADORIA,
												M.DS_MERCADORIA
										FROM	TB_MERCADORIA M
										INNER JOIN	  TB_ESTOQUE E ON M.CD_MERCADORIA = E.CD_MERCADORIA
											WHERE E.CD_LOJA 		  = ?
												AND M.DT_EXCLUSAO is null
    										ORDER BY
    							  				M.CD_MERCADORIA");

        $results =  $statement->execute(array($cdLoja));
        return $results;
    }

    public function recuperaValorDeVenda($cdLoja, $cdMercadoria) {
            $statement = $this->adapter->query("SELECT
                                                        B.ST_LIBERA_SEM_ESTOQUE,
                                                        dbo.MostraEstoque( b.cd_loja, a.CD_Mercadoria ) as NR_QTDE_ESTOQUE,
                                                        dbo.MostraReserva( b.cd_loja, a.CD_Mercadoria ) as NR_QTDE_RESERVA,
                                                        dbo.MostraEstoqueDisponivel( b.CD_LOJA, a.CD_MERCADORIA ) as NR_QTDE_DISPONIVEL
                                                FROM	TB_MERCADORIA A
                                                INNER JOIN TB_ESTOQUE B ON A.CD_MERCADORIA = B.CD_MERCADORIA
                                                WHERE B.CD_LOJA = ? AND
                                                    A.CD_Mercadoria = ? AND
                                                    A.DT_EXCLUSAO is null ");
            $results = $statement->execute(array($cdLoja, $cdMercadoria));
            return  $results->current();
    }
	
	public function remove($id)
    {

        $id = (int) $id;

        if ($this->getId($id)) {
            //$this->delete(array("cd_cliente" => $id));
			$data = array(
				'DT_EXCLUSAO'	=> date(FORMATO_ESCRITA_DATA_HORA)
			);
			$this->update($data, array("CD_MERCADORIA" => $id));
			
        } else {
            throw new \Exception("Identificador $id  não existe no banco de dados!");
        }
    }
	
	public function getId($id)
    {
        $id = (int) $id;

        $select = $this->getSql()->select();
		$select->where(array('CD_MERCADORIA' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row;
    }
}
