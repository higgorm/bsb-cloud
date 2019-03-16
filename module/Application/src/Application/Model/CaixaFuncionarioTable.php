<?php

namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Session\Container;

class CaixaFuncionarioTable extends AbstractTableGateway {

    protected $table = "TB_CAIXA_FUNCIONARIO";

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

    public function getId($tableData) {
        $select = $this->getSql()->select();
        $select->columns(array('cd_loja',
                    'nr_caixa',
                    'cd_funcionario',
                    'dt_entrada',
                    'dt_saida',
                    'st_atividade',
                    'dt_hora_entrada',
                    'dt_hora_saida',
                ))
                ->where(array('cd_loja' => (int) $tableData->cd_loja,
                    'nr_caixa' => (int) $tableData->nr_caixa,
                    'cd_funcionario' => (int) $tableData->cd_funcionario,
                    'dt_entrada' => $tableData->dt_entrada));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row;
    }

    public function save($tableData) {
        try {
            $data = array(
                "cd_loja" => $tableData['cd_loja'],
                "nr_caixa" => $tableData['nr_caixa'],
                "cd_funcionario" => $tableData['cd_funcionario'],
                "dt_entrada" => $tableData['dt_entrada'],
                "dt_saida" => $tableData['dt_saida'],
                "st_atividade" => $tableData['st_atividade'],
                "dt_hora_entrada" => $tableData['dt_hora_entrada'],
                "dt_hora_saida" => $tableData['dt_hora_saida'],
            );

            if ($this->getId($tableData)) {
                $this->update($data, array('cd_cliente' => (int) $tableData->cd_cliente,
                    'nr_maca' => (int) $tableData->nr_maca,
                    'cd_loja' => (int) $tableData->cd_loja,
                    'dt_entrada' => $tableData->dt_entrada));
            } else {
                $this->insert($data);
            }
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function validaAberturaCaixa($cdLoja, $nrCaixa, $cdFunc, $data) {
        $statement = $this->adapter->query("SELECT 
                                                tcf.CD_LOJA, NR_CAIXA, tcf.CD_Funcionario, tcf.DT_SAIDA,
                                                CONVERT(VARCHAR(10),tcf.DT_ENTRADA, 103) as DT_ENTRADA,
                                                CONVERT(VARCHAR(10), tcf.DT_SAIDA, 103) as DT_SAIDA,
                                                tcf.ST_ATIVIDADE, tcf.DT_HORA_ENTRADA, tcf.DT_HORA_SAIDA,
                                                tf.DS_FUNCIONARIO
                                            FROM TB_CAIXA_FUNCIONARIO tcf
                                            JOIN TB_FUNCIONARIO tf on tcf.CD_Funcionario = tf.CD_FUNCIONARIO 
                                            WHERE tcf.CD_LOJA = ?
                                                AND tcf.NR_CAIXA = ?
                                                AND (
                                                        ( tcf.DT_ENTRADA < ? AND tcf.DT_SAIDA IS NULL)
                                                        OR
                                                        (CONVERT(VARCHAR(10),tcf.DT_ENTRADA, 103) = ? )
                                                    ) ");

        $results = $statement->execute(array($cdLoja, $nrCaixa, $data . ' 23:59:59', $data));
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function fechamentoCaixa($cdLoja, $nr_caixa, $dtCaixa) {
        try {
            $this->update(array('DT_SAIDA' => date('d/m/Y'),
                'DT_HORA_SAIDA' => date('d/m/Y H:i:s'),
                'ST_ATIVIDADE' => 'B'
                    ), array('CD_LOJA' => (int) $cdLoja,
                'NR_CAIXA' => (int) $nr_caixa,
                'DT_ENTRADA' => $dtCaixa . ' 00:00:00'
                    )
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function reaberturaCaixa($cdLoja, $nr_caixa, $dtCaixa) {
        try {
            $this->update(array('DT_SAIDA' => null,
                'DT_HORA_SAIDA' => null,
                'ST_ATIVIDADE' => 'L'
                    ), array('CD_LOJA' => (int) $cdLoja,
                'NR_CAIXA' => (int) $nr_caixa,
                'DT_ENTRADA' => $dtCaixa . ' 00:00:00'
                    )
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

}
