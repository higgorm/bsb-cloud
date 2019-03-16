<?php

namespace Application\Model;

use Zend\Db\Sql\Where;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Sql;
use Zend\Session\Container;

class AgendamentoFranquiaTable extends AbstractTableGateway
{

    protected $table = "TB_AGENDAMENTO_FRANQUIA";
	
	public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }

    public function getId($tableData)
    {
        $select = $this->getSql()->select();
        $select->columns(array('cd_loja',
                    'nr_maca',
                    'dt_horario',
                    'cd_cliente',
                    'nr_pedido',
                    'st_contatado',
                    'st_cliente_chegou',
                    'cd_cliente_rapido',
                    'cd_funcionario',
                ))
                ->where(array('cd_cliente' => (int) $tableData->cd_cliente,
                    'nr_maca' => (int) $tableData->nr_maca,
                    'cd_loja' => (int) $tableData->cd_loja,
                    'dt_horario' => $tableData->dt_horario));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row;
    }

    public function save($tableData)
    {
        $data = array(
            "cd_loja" => $tableData->cd_loja,
            "nr_maca" => $tableData->nr_maca,
            "dt_horario" => $tableData->dt_horario,
            "cd_cliente" => $tableData->cd_cliente,
            "nr_pedido" => $tableData->nr_pedido,
            "st_contatado" => $tableData->st_contatado,
            "st_cliente_chegou" => $tableData->st_cliente_chegou,
            "cd_cliente_rapido" => $tableData->cd_cliente_rapido,
            "cd_funcionario" => $tableData->cd_funcionario,
        );

        if ($this->getId($tableData)) {
            $this->update($data, array('cd_cliente' => (int) $tableData->cd_cliente,
                'nr_maca' => (int) $tableData->nr_maca,
                'cd_loja' => (int) $tableData->cd_loja,
                'dt_horario' => $tableData->dt_horario));
        } else {
            $this->insert($data);
        }
    }

    public function remove($cdLoja, $nrMaca)
    {

        if ($this->getId($cdLoja, $nrMaca)) {
            $this->delete(array("cd_loja" => $cdLoja, "nr_maca" => $nrMaca));
        } else {
            throw new \Exception("Maca $nrMaca  n�o existe no banco de dados!");
        }
    }

    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10")
    {
        $select = new Select();
        $where = new Where();

        foreach ($param as $field => $search) {
            $where->like($field, '%' . $search . '%');
        }

        $select->from($this->table)->where($where)->order("DS_NOME_RAZAO_SOCIAL ");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function getMacas($cdLoja)
    {

        $select = $this->getSql()->select();
        $select->columns(array('cd_loja',
                    'nr_maca',
                    'ds_identificacao',
                    'cd_funcionario',
                ))
                ->where(array('cd_loja' => $cdLoja));

        return $this->selectWith($select);
    }

    public function getAgendamento($cdLoja, $nrMaca, $dtInicio, $dtFim)
    {
        $statement = $this->adapter->query("select
                                                tfm.NR_MACA,
                                                tfm.DS_IDENTIFICACAO,
                                                taf.DT_HORARIO,
                                                tc.DS_NOME_RAZAO_SOCIAL,
                                                tc.CD_CLIENTE
                                            from TB_FRANQUIA_MACA tfm
                                            left join TB_AGENDAMENTO_FRANQUIA taf on tfm.CD_LOJA = taf.CD_LOJA and tfm.NR_MACA = taf.NR_MACA
                                            left join TB_CLIENTE tc on taf.CD_CLIENTE = tc.CD_CLIENTE
                                            where tfm.CD_LOJA = ?
                                                    and tfm.NR_MACA = ?
                                                    and CONVERT(VARCHAR(10),taf.DT_HORARIO,103) between CONVERT(VARCHAR(10),?,103) and CONVERT(VARCHAR(10),?,103)
                                            order by tfm.NR_MACA, taf.DT_HORARIO");

        $results = $statement->execute(array($cdLoja, $nrMaca, $dtInicio, $dtFim));
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function getAgendamentoMacas($cdLoja, $dtInicio, $dtFim)
    {

        $statement = $this->adapter->query("select
                                                        tfm.NR_MACA,
                                                        tfm.DS_IDENTIFICACAO,
                                                        taf.DT_HORARIO,
                                                        tc.DS_NOME_RAZAO_SOCIAL,
                                                        tc.CD_CLIENTE,
                                                        tc.DS_NOME_RAZAO_SOCIAL
                                                    from TB_FRANQUIA_MACA tfm
                                                    left join TB_AGENDAMENTO_FRANQUIA taf on tfm.CD_LOJA = taf.CD_LOJA and tfm.NR_MACA = taf.NR_MACA
                                                    left join TB_CLIENTE tc on taf.CD_CLIENTE = tc.CD_CLIENTE
                                                    where tfm.CD_LOJA = ?
                                                            and CONVERT(VARCHAR(10),taf.DT_HORARIO,103) between CONVERT(VARCHAR(10),?,103) and CONVERT(VARCHAR(10),?,103)
                                                    order by tfm.NR_MACA, taf.DT_HORARIO");

        return $statement->execute(array($cdLoja, $dtInicio, $dtFim));
    }

    public function recuperaAgendamentoCliente($idCliente, $dtHorario)
    {
        $statement = $this->adapter->query("SELECT DISTINCT
                                                        AF.CD_CLIENTE, AF.CD_FUNCIONARIO,
                                                DS_FONE1 = CASE WHEN ( C.CD_CLIENTE IS NULL or AF.CD_CLIENTE = 1 ) THEN FCR.DS_FONE1 ELSE C.DS_FONE1 END,
                                                DS_FONE2 = CASE WHEN ( C.CD_CLIENTE IS NULL or AF.CD_CLIENTE = 1 ) THEN FCR.DS_FONE2 ELSE C.DS_FONE2 END,
                                                DS_NOME_RAZAO_SOCIAL = CASE WHEN ( C.CD_CLIENTE IS NULL or AF.CD_CLIENTE = 1 ) THEN FCR.DS_NOME ELSE C.DS_FANTASIA END,
                                                AF.ST_CLIENTE_CHEGOU, AF.CD_FUNCIONARIO,
                                                AF.CD_CLIENTE_RAPIDO, AF.NR_PEDIDO
                                            FROM TB_AGENDAMENTO_FRANQUIA AF
                                                LEFT JOIN TB_CLIENTE C ON C.CD_CLIENTE = AF.CD_CLIENTE
                                                LEFT JOIN TB_FRANQUIA_CLIENTE_RAPIDO FCR ON FCR.CD_CLIENTE = AF.CD_CLIENTE_RAPIDO
                                                LEFT JOIN TB_FRANQUIA_MACA FM ON FM.NR_MACA = AF.NR_MACA
                                            WHERE AF.CD_CLIENTE = ?
                                            AND AF.DT_HORARIO between ? and ? ");

        return $statement->execute(array($idCliente, $dtHorario, $dtHorario));
    }

    public function alteraSituacaoAgendamento($situacao, $cliente, $maca, $horario, $loja)
    {
        try{
        return $this->update(array('ST_CLIENTE_CHEGOU' => $situacao), array('cd_cliente' => (int) $cliente,
                                                                            'nr_maca' => $maca,
                                                                            'cd_loja' => $loja,
                                                                            'dt_horario' => $horario));
        }catch(Exception $e){
            return false;
        }
    }

    public function limpaAgendamento($maca, $horario, $loja)
    {
        try{
            $statement = $this->adapter->query("DELETE TB_AGENDAMENTO_FRANQUIA_SERVICOS WHERE CD_LOJA = ? AND NR_MACA = ? AND DT_HORARIO = ? ");
            $statement->execute(array($loja, $maca, $horario));

            $statement = $this->adapter->query("DELETE TB_AGENDAMENTO_FRANQUIA WHERE CD_LOJA = ? AND NR_MACA = ? AND DT_HORARIO = ? ");
            $statement->execute(array($loja, $maca, $horario));

            return true;
        }catch(Exception $e){
            return false;
        }
    }

    public function atualizaAgendamentoFranquia($dados)
    {
        $pedidostatement = $this->adapter->query("UPDATE
                                                    TB_AGENDAMENTO_FRANQUIA
                                                SET
                                                    ST_CLIENTE_CHEGOU = 'S',
                                                    NR_PEDIDO = ?,
                                                    CD_CLIENTE = ?
                                                WHERE
                                                    CD_LOJA    = ? AND
                                                    NR_MACA    = ? AND
                                                    DT_HORARIO = ? ");

        $pedidostatement->execute($dados);
    }

    public function getAgendamentoByNumeroPedido($nrPedido)
    {
        $statement = $this->adapter->query("SELECT
                                                CD_LOJA,
                                                NR_MACA,
                                                DT_HORARIO,
                                                CD_CLIENTE
                                        FROM
                                                TB_AGENDAMENTO_FRANQUIA
                                        WHERE NR_PEDIDO = ? ");
        $results = $statement->execute(array($nrPedido));
        return $results->current();
    }
}
