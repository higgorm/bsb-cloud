<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Session\Container;

class RelatorioAgendamentoTable {

    protected $adapter = "";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }

    public function pesquisa($cdLoja, $dataDeInicio, $dataDeTermino,  $situacao, $cdCliente = null, $cdMercadoria = null) {

        $statement = "SELECT DISTINCT   a.CD_CLIENTE,
	                                    a.DT_HORARIO,
                                        DS_CLIENTE	= CASE	WHEN ( C.CD_CLIENTE IS NULL or a.cd_cliente = 1 )
                                                            THEN CR.DS_NOME ELSE C.DS_FANTASIA END,
                                        CD_FONE1	= CASE	WHEN (C.CD_CLIENTE IS NULL or a.cd_cliente = 1 )
                                                            THEN CR.DS_FONE1 ELSE C.DS_FONE1 END,
                                        CD_FONE2	= CASE	WHEN (C.CD_CLIENTE IS NULL or a.cd_cliente = 1 )
                                                            THEN CR.DS_FONE2 ELSE C.DS_FONE2 END,
                                        C.DS_EMAIL,
                                        CR.DS_FONE1,
                                        t.ST_PEDIDO
                      FROM TB_AGENDAMENTO_FRANQUIA a
                      LEFT JOIN TB_CLIENTE C ON a.CD_CLIENTE = C.CD_CLIENTE
                      LEFT JOIN TB_FRANQUIA_CLIENTE_RAPIDO CR ON A.CD_CLIENTE_RAPIDO = CR.CD_CLIENTE
                      LEFT JOIN TB_PEDIDO  t ON a.CD_CLIENTE = t.CD_CLIENTE
                                        AND a.NR_PEDIDO = t.NR_PEDIDO
                      WHERE  1=1
                      AND a.CD_LOJA = '".$cdLoja."'
                      AND a.DT_HORARIO >= '".date('Ymd',strtotime($dataDeInicio))."'
                      AND a.DT_HORARIO <= '".date('Ymd',strtotime($dataDeTermino))."'";
        if($situacao != 'T'){
            $statement .= " AND ISNULL(t.ST_PEDIDO, 'A') = '".$situacao."'";
        }
        if($cdCliente != null){
            $statement .= " AND a.CD_CLIENTE = ".$cdCliente;
        }
        if($cdMercadoria != null){
            $statement .= "AND EXISTS (SELECT TOP 1 1 FROM TB_AGENDAMENTO_FRANQUIA_SERVICOS AFS
                                       WHERE AFS.CD_LOJA = A.CD_LOJA
                                       AND AFS.NR_MACA = A.NR_MACA
                                       AND AFS.DT_HORARIO = A.DT_HORARIO
                                       AND AFS.CD_MERCADORIA = ".$cdMercadoria.")";
        }

        $statement = $this->adapter->query($statement);
        $return = $statement->execute();

        return $return;
    }
}
