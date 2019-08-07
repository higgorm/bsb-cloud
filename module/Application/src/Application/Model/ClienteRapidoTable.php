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

class ClienteRapidoTable extends AbstractTableGateway
{

    protected $table = "TB_FRANQUIA_CLIENTE_RAPIDO";

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new ClienteRapido());
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }

    public function getId($cdCliente)
    {

        $select = $this->getSql()->select();
        $select->columns(array('cd_cliente',
                    'ds_nome',
                    'ds_fone1',
                    'ds_fone2',
                ))
                ->where(array('cd_cliente' => $cdCliente));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

//        if (!$row) {
//            throw new Exception("Profissional $nrMaca  não existe no banco de dados!");
//        }

        return $row;
    }

    public function save(ClienteRapido $tableData)
    {
        $data = array(
            "cd_cliente" => $tableData->cd_cliente,
            "ds_nome" => $tableData->ds_nome,
            "ds_fone1" => $tableData->ds_fone1,
            "ds_fone2" => $tableData->ds_fone2,
        );

        $cdCliente = (int) $tableData->cd_cliente;

        $nextVal = "SELECT MAX(CD_CLIENTE)+1 AS CD_CLIENTE FROM TB_FRANQUIA_CLIENTE_RAPIDO";
            $statement = $this->adapter->createStatement($nextVal);
            $result = $statement->execute();

            foreach ($result as $res){
                $data['cd_cliente'] = (int)$res['CD_CLIENTE'];
            }

        if ($this->getId($cdCliente)) {
            return $this->update($data, array("cd_cliente" => $cdCliente));
        } else {
            $this->insert($data);
            return $data['cd_cliente'];
        }
    }

    public function remove($cdLoja, $nrMaca)
    {

        if ($this->getId($cdLoja, $nrMaca)) {
            $this->delete(array("cd_loja" => $cdLoja, "nr_maca" => $nrMaca));
        } else {
            throw new Exception("Profissional $nrMaca  não existe no banco de dados!");
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

}
