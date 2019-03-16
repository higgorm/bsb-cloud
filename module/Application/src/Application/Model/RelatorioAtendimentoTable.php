<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Session\Container;

class RelatorioAtendimentoTable {

    protected $adapter = "";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }

    public function getTotal($cdLoja, $dataDeInicio, $dataDeTermino) {

        $statement = $this->adapter->query("  SELECT SUM( VALOR ) VL_TOTAL
												  FROM (
													  SELECT PM.CD_MERCADORIA,
															 VALOR   = SUM( PM.VL_TOTAL_LIQUIDO )
													  FROM
															TB_PEDIDO P
															INNER JOIN TB_PEDIDO_MERCADORIA PM ON P.CD_LOJA = PM.CD_LOJA AND P.NR_PEDIDO = PM.NR_PEDIDO
															INNER JOIN TB_MERCADORIA M ON PM.CD_MERCADORIA = M.CD_MERCADORIA
													  WHERE
															P.CD_LOJA = ?
															AND P.ST_PEDIDO = 'F'
															AND P.CD_TIPO_PEDIDO IN ( 1, 2, 5, 10 )
														    AND P.DT_PEDIDO BETWEEN CONVERT(VARCHAR(10),?,103) AND CONVERT(VARCHAR(10),?,103)
													  GROUP BY PM.CD_MERCADORIA
												  ) AS RESUMO");

        $results = $statement->execute(array($cdLoja, $dataDeInicio, $dataDeTermino));
        return $results;
    }

    public function getLista($cdLoja, $dataDeInicio, $dataDeTermino) {

        $statement = $this->adapter->query("SELECT
                                                PM.CD_MERCADORIA,
                                                M.DS_MERCADORIA,
                                                NR_QTDE = SUM( PM.NR_QTDE_VENDIDA ),
                                                VALOR   = SUM( PM.VL_TOTAL_LIQUIDO )
                                           FROM
                                                TB_PEDIDO P
                                                INNER JOIN TB_PEDIDO_MERCADORIA PM ON P.CD_LOJA = PM.CD_LOJA AND P.NR_PEDIDO = PM.NR_PEDIDO
                                                INNER JOIN TB_MERCADORIA M ON PM.CD_MERCADORIA = M.CD_MERCADORIA
                                             WHERE
                                                P.CD_LOJA = ? AND
                                                P.ST_PEDIDO = 'F' AND
                                                P.CD_TIPO_PEDIDO IN ( 1, 2, 5, 10 ) AND
                                                P.DT_PEDIDO BETWEEN  CONVERT(VARCHAR(10),?,103) AND CONVERT(VARCHAR(10),?,103)
                                             GROUP BY PM.CD_MERCADORIA, M.DS_MERCADORIA
                                             ORDER BY 4 DESC
    	");

        $results = $statement->execute(array($cdLoja, $dataDeInicio, $dataDeTermino));
        return $results;
    }

}
