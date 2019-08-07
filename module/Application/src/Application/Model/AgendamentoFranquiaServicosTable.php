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
            echo $exc->getMessage();
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

        $existe = $this->existeMercadoriaAgendamento($data['cd_loja'],$data['nr_maca'],$data['dt_horario'],$data['cd_mercadoria']);

        try {
            if ( (int) $existe['tot'] >= 1 ){
                $this->update($data);
            } else {
                $this->insert($data);
            }
        } catch (RuntimeException $exc) {
            echo $exc->getMessage();
        }
    }

    public function existeMercadoriaAgendamento($cdLoja, $nrMaca, $dtHorario, $cdMercadoria)
    {
        $statement = $this->adapter->query("SELECT   count(*) as tot
                                            FROM TB_AGENDAMENTO_FRANQUIA_SERVICOS FS
                                            INNER JOIN TB_MERCADORIA M ON M.CD_MERCADORIA = FS.CD_MERCADORIA
                                          WHERE
                                                FS.CD_LOJA = ?
                                              AND FS.NR_MACA = ? 
                                              AND FS.DT_HORARIO between ? and ?
                                              AND FS.CD_MERCADORIA = ?
                                         ");


        $result= $statement->execute(array($cdLoja, $nrMaca, $dtHorario, $dtHorario, $cdMercadoria));
        return $result->current();
    }

    public function recuperaAgendamentoAgendamento($cdLoja, $dtHorario, $nrMaca)
    {
        $statement = $this->adapter->query("SELECT   *
                                            FROM TB_AGENDAMENTO_FRANQUIA_SERVICOS FS
                                            INNER JOIN TB_MERCADORIA M ON M.CD_MERCADORIA = FS.CD_MERCADORIA
                                            INNER JOIN RL_PRAZO_LIVRO_PRECOS R on M.CD_MERCADORIA = R.CD_MERCADORIA
                                            LEFT JOIN TB_LIVRO_PRECOS L ON L.CD_MERCADORIA = M.CD_MERCADORIA
                                          WHERE
                                              R.CD_LIVRO = ?
                                          AND  R.CD_PRAZO = ? 
                                          AND FS.CD_LOJA = ?
                                          AND FS.DT_HORARIO between ? and ?
                                          AND FS.NR_MACA = ? ");


        return $statement->execute(array(1,1,$cdLoja, $dtHorario, $dtHorario, $nrMaca));
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
