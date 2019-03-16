<?php

namespace Application\Model;

use Zend\Db\Sql\Where;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Session\Container;

class AgendamentoFranquiaServicosTable extends AbstractTableGateway
{

    protected $table = "tb_agendamento_franquia_servicos";

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new AgendamentoFranquiaServicos());
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
                    'cd_mercadoria',
                ))
                ->where(array(
                    "cd_loja" => $tableData->cd_loja,
                    "nr_maca" => $tableData->nr_maca,
                    "dt_horario" => $tableData->dt_horario,
                    "cd_mercadoria" => $tableData->cd_mercadoria
        ));

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
            "cd_mercadoria" => $tableData->cd_mercadoria
        );
        try {
            $this->insert($data);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

    public function saveMercadoriaAgendamento($tableData)
    {
        $data = array(
            "cd_loja" => $tableData['cd_loja'],
            "nr_maca" => $tableData['nr_maca'],
            "dt_horario" => $tableData['dt_horario'],
            "cd_mercadoria" => $tableData['cd_mercadoria']
        );
        try {
            $result = $this->insert($data);
//            var_dump($data );
//            echo '<br>';
//            var_dump($result);exit;
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }

    public function recuperaAgendamentoAgendamento($cdLoja, $dtHorario, $nrMaca)
    {

        $statement = $this->adapter->query("SELECT *
                                            FROM TB_AGENDAMENTO_FRANQUIA_SERVICOS FS
                                            INNER JOIN TB_MERCADORIA M ON M.CD_MERCADORIA = FS.CD_MERCADORIA
                                            INNER JOIN RL_PRAZO_LIVRO_PRECOS R on M.CD_MERCADORIA = R.CD_MERCADORIA
                                            LEFT JOIN TB_LIVRO_PRECOS L ON L.CD_MERCADORIA = M.CD_MERCADORIA
                                          WHERE FS.CD_LOJA = ?
                                          AND FS.DT_HORARIO between ? and ?
                                          AND FS.NR_MACA = ? ");


        return $statement->execute(array($cdLoja, $dtHorario, $dtHorario, $nrMaca));
    }

    public function recuperaprecoservico($mercadoria)
    {
        $statement = $this->adapter->query("SELECT
                                                VL_PRECO_VENDA
                                          FROM
                                                RL_PRAZO_LIVRO_PRECOS
                                          WHERE
                                                CD_LIVRO = 1 AND
                                                CD_PRAZO = 1 AND
                                                CD_MERCADORIA = ?");
            $results = $statement->execute(array($mercadoria));
            return $results->current();
    }

    public function removeAgendamenteFranquiaServico($dados)
    {
        $statement = $this->adapter->query("DELETE
                                                TB_AGENDAMENTO_FRANQUIA_SERVICOS
                                            WHERE
                                                CD_LOJA    = ? AND
                                                NR_MACA    = ? AND
                                                DT_HORARIO = ? ;
                                                ");
        $statement->execute($dados);
    }


}
