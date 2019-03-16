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

class MailTable extends AbstractTableGateway {

    protected $table = "TB_CLIENTE";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Cliente());
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }


    public function pesquisaMail($data)
    {
        $ordenacao = ($data['rdOrdenacao'] == 0) ? " DATEDIFF(day, p.DT_PEDIDO, getdate()) DESC ": " c.DS_NOME_RAZAO_SOCIAL ASC ";
        $sexo = ($data['rdSexo'] == 1) ? " and c.DS_SEXO = 'F' " : "";
        $sexo = ($data['rdSexo'] == 2) ? " and c.DS_SEXO = 'F' " : "";
        $contador = " ,DATEDIFF(day, p.DT_PEDIDO, getdate()) as QTD ";
        $agrupador = " ,DATEDIFF(day, p.DT_PEDIDO, getdate()) ";
        $condUltimoPedido = " and p.NR_PEDIDO = (select MAX(p2.nr_pedido) from TB_PEDIDO p2 where p2.CD_CLIENTE = c.CD_CLIENTE) ";
        
        switch ($data['tpAtendimento']) {
            case 1:
                $top = " TOP 100 ";
                $todos = " and p.ST_PEDIDO = 'F' ";
                $contador = " ,COUNT(p.DT_PEDIDO) as QTD ";
                $condUltimoPedido = "";
                $agrupador = "";
                $ordenacao = " COUNT(p.DT_PEDIDO) DESC ";
                break;
            case 2:
                $top = "";
                $todos = " and p.ST_PEDIDO = 'F' ";
                $contador = " ,COUNT(p.DT_PEDIDO) as QTD ";
                break;
            case 3:
                $top = "";
                $todos = " and p.ST_PEDIDO = 'F' ";
                break;
        }
        $dtAusencia = ($data['dtAusencia'] != "") ? " and DATEDIFF(DAY, p.DT_PEDIDO , getdate()) = ".$data['dtAusencia']." " : "";
        $dtPeriodo = (!empty($data['dtAusenciaIni']) && !empty($data['dtAusenciaFim'])) ? " and p.DT_PEDIDO >= '".$data['dtAusenciaIni']." 00:00:00' and p.DT_PEDIDO <= '".$data['dtAusenciaFim']." 23:59:59' " : "";
        $dia = (!empty($data['diaAniversario'])) ? " and DATEPART ( DAY , c.DT_NASCIMENTO ) = ".$data['diaAniversario'] . " " : "";
        $mes = (!empty($data['mesAniversario'])) ? " and DATEPART ( MONTH , c.DT_NASCIMENTO ) = ".$data['mesAniversario'] . " " : "";
        $nome = (!empty($data['dsNome'])) ? " and c.DS_NOME_RAZAO_SOCIAL LIKE '%".$data['dsNome']."%' " : "";

        
        $statement = $this->adapter->query("select {$top}
                                                c.CD_CLIENTE,
                                                UPPER(c.DS_NOME_RAZAO_SOCIAL) as DS_NOME_RAZAO_SOCIAL,
                                                c.DS_FONE1, c.DS_EMAIL,
                                                MAX(p.DT_PEDIDO) as DT_PEDIDO,
                                                max(p.DT_RECEBIMENTO) as dt_horario
                                                {$contador}
                                            from TB_PEDIDO p
                                            inner join TB_CLIENTE C on p.CD_CLIENTE = C.CD_CLIENTE
                                            where 1=1

                                                {$todos}
                                                {$dia}
                                                {$mes}
                                                {$nome}
                                                {$dtAusencia}
                                                {$dtPeriodo}
                                                {$sexo}

                                                and c.CD_CLIENTE <> 1  and (c.dt_exclusao is null)                                                
                                                {$condUltimoPedido}
                                            group by c.CD_CLIENTE, UPPER(c.DS_NOME_RAZAO_SOCIAL), c.DS_FONE1, c.DS_EMAIL {$agrupador} 
                                            order by {$ordenacao} ");
        //echo $statement->getSql();
        //exit;
        return $statement->execute(array());

    }

    public function getClientes($param) {
        $cd = implode(',', $param);
        $statement = $this->adapter->query("select c.DS_NOME_RAZAO_SOCIAL, c.DS_FONE1, c.DS_FONE2, c.DS_EMAIL, CONVERT(VARCHAR(10), p.DT_PEDIDO, 103) DT_PEDIDO, "
                                        . "DATEDIFF(day, p.DT_PEDIDO, getdate()) as QTD "
                                        . "from TB_CLIENTE c "
                                        . "inner join TB_PEDIDO p ON p.CD_CLIENTE = c.CD_CLIENTE "
                                        . "where c.CD_CLIENTE <> 1 "
                                        . "and p.NR_PEDIDO = (select MAX(p2.nr_pedido) from TB_PEDIDO p2 where p2.CD_CLIENTE = c.CD_CLIENTE) "
                                        . "and c.CD_CLIENTE in ({$cd}) "
                                        );

        return $statement->execute();
    }

    public function getConfiguracaoEmailLoja($cdLoja){

        $statement = $this->adapter->query("SELECT DS_DANFE_eMail, DS_SMTP_Host, DS_SMTP_Port, DS_SMTP_User,DS_SMTP_Pass
                                            FROM TB_NFE_CONFIG
                                            WHERE CD_LOJA = ?");
        
        return $statement->execute(array($cdLoja))->current();
    }

    public function getAnalise($cdLoja){

        $statement = $this->adapter->query("SELECT C.CD_CLIENTE FROM TB_CLIENTE AS C
                                                            INNER JOIN TB_PEDIDO AS P ON P.CD_CLIENTE = C.CD_CLIENTE
                                                            WHERE (SELECT COUNT(P2.NR_PEDIDO) FROM TB_PEDIDO AS P2 WHERE P2.CD_CLIENTE = C.CD_CLIENTE) = '1'
                                                            AND DATEDIFF(DAY, P.DT_PEDIDO , getdate()) <= '30'
                                                            AND C.CD_CLIENTE <> '1' 
                                                            GROUP BY C.CD_CLIENTE");
        $result['aquisicao'] = $statement->execute();
        foreach ($result['aquisicao'] as $res) {
            $resultado1[] = $res['CD_CLIENTE'];
        }

        $result['aquisicao'] =  $resultado1;
        $statement = $this->adapter->query("(SELECT C.CD_CLIENTE FROM TB_CLIENTE AS C
                                                            INNER JOIN TB_PEDIDO AS P ON P.CD_CLIENTE = C.CD_CLIENTE
                                                            WHERE DATEDIFF(DAY, P.DT_PEDIDO , getdate()) > '30'
                                                            AND DATEDIFF(DAY, P.DT_PEDIDO , getdate()) < '60'
                                                            GROUP BY C.CD_CLIENTE
                                                            )
                                                            INTERSECT
                                                            (SELECT C.CD_CLIENTE FROM TB_CLIENTE AS C
                                                            INNER JOIN TB_PEDIDO AS P ON P.CD_CLIENTE = C.CD_CLIENTE
                                                            WHERE DATEDIFF(DAY, P.DT_PEDIDO , getdate()) > '60'
                                                            AND DATEDIFF(DAY, P.DT_PEDIDO , getdate()) < '90'
                                                            AND C.CD_CLIENTE <> '1'
                                                            GROUP BY C.CD_CLIENTE
                                                            )
                                                            INTERSECT
                                                            (SELECT C.CD_CLIENTE FROM TB_CLIENTE AS C
                                                            INNER JOIN TB_PEDIDO AS P ON P.CD_CLIENTE = C.CD_CLIENTE
                                                            WHERE DATEDIFF(DAY, P.DT_PEDIDO , getdate()) < '30'

                                                            GROUP BY C.CD_CLIENTE
                                                            )");
        $result['retencao'] = $statement->execute();
        foreach ($result['retencao'] as $res) {
            $resultado2[] = $res['CD_CLIENTE'];
        }
        $result['retencao'] =  $resultado2;

        $statement = $this->adapter->query("SELECT C.CD_CLIENTE FROM TB_CLIENTE AS C
                                                            INNER JOIN TB_PEDIDO AS P ON P.CD_CLIENTE = C.CD_CLIENTE
                                                            WHERE DATEDIFF(DAY, (SELECT MAX(P2.DT_PEDIDO) FROM TB_PEDIDO AS P2 WHERE C.CD_CLIENTE = P2.CD_CLIENTE), getdate()) >= '60'
                                                            AND DATEDIFF(DAY, (SELECT MAX(P2.DT_PEDIDO) FROM TB_PEDIDO AS P2 WHERE C.CD_CLIENTE = P2.CD_CLIENTE) , getdate()) <= '90'
                                                            AND C.CD_CLIENTE <> '1'
                                                            GROUP BY C.CD_CLIENTE");
        $result['perda'] = $statement->execute();
        foreach ($result['perda'] as $res) {
            $resultado3[] = $res['CD_CLIENTE'];
        }
        $result['perda'] =  $resultado3;

        return $result;
    }
}
