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

class NotaInutilizadaTable extends AbstractTableGateway {

    protected $table = "TB_NFE_INUTILIZADAS";

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new NotaInutilizada());
        $this->initialize();

        $session = new Container("orangeSessionContainer");
        if( @$session->cdBase ){
            $statement = $this->adapter->query("USE BDGE_".$session->cdBase);
            $statement->execute();
        }
    }

    public function listHistorico()
    {
        $statement = $this->adapter->query('SELECT *  FROM '.$this->table . ' ORDER BY CD_NFE_INUTILILIZADA DESC');
        $result = $statement->execute();
        return $selectData;
    }

    public function nextId()
    {
        $select = $this->getSql()->select();
        $select->columns(array(new Expression('ISNULL(MAX(cd_nfe_inutililizada),0) + 1 as cd_nfe_inutililizada')));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->cd_nfe_inutililizada;
    }

    public function save($tableData)
    {
        $isNew = false;

        try {
            if ($tableData->cd_nfe_inutililizada) {
                $base = $this->getId($tableData->cd_nfe_inutililizada);
            } else {
                $isNew= true;
            }

            $data = array(
                "tp_ambiente"       => (isset($tableData->tp_ambiente)) ? $tableData->tp_ambiente   : $base->tp_ambiente,
                "cd_loja"           => (isset($tableData->cd_loja))     ? $tableData->cd_loja       : $base->cd_loja,
                "tp_nfe"            => (isset($tableData->tp_nfe))      ? $tableData->tp_nfe        : $base->tp_nfe,
                "nr_serie"          => (isset($tableData->nr_serie))    ? $tableData->nr_serie      : $base->nr_serie,
                "nr_ano"            => (isset($tableData->nr_ano))      ? $tableData->nr_ano        : $base->nr_ano,
                "nr_faixa_inicial"  => (isset($tableData->nr_faixa_inicial))    ? $tableData->nr_faixa_inicial  : $base->nr_faixa_inicial,
                "nr_faixa_final"    => (isset($tableData->nr_faixa_final))      ? $tableData->nr_faixa_final    : $base->nr_faixa_final,
                "ds_justificativa"  => (isset($tableData->ds_justificativa))    ? $tableData->ds_justificativa  : $base->ds_justificativa,
                "ds_retorno_nfe"    => (isset($tableData->ds_retorno_nfe))      ? $tableData->ds_retorno_nfe    : $base->ds_retorno_nfe,
                "cd_usuario"        => (isset($tableData->cd_usuario))          ? $tableData->cd_usuario    : $base->cd_usuario,
                "dt_registro"       => (isset($tableData->dt_registro))         ? $tableData->dt_registro   : $base->dt_registro
            );

            if ($isNew) {
                $data["cd_nfe_inutililizada"] = $this->nextId();
            }
//var_dump($tableData,$data,$isNew);exit;
            if (!$isNew) {
                if(!$this->update($data, array("cd_nfe_inutililizada" => $tableData->cd_nfe_inutililizada)))
                    throw new \Exception ;
            } else {
                if(!$this->insert($data))
                    throw new \Exception;
            }
            return $data['cd_nfe_inutililizada'];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}