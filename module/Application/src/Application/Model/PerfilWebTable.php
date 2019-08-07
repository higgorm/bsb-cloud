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

class PerfilWebTable extends AbstractTableGateway {

    protected $table = "TB_PERFIL_WEB";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new PerfilWeb());
        $this->initialize();

        $statement = $this->adapter->query("USE LOGIN ");
        $statement->execute();
		
    }

    public function listPerfil()
    {
        $statement = $this->adapter->query('SELECT CD_PERFIL_WEB, DS_NOME FROM '.$this->table);
        $result = $statement->execute();

        $selectData = array('' => 'Selecione');

        foreach ($result as $res) {
            $selectData[$res['CD_PERFIL_WEB']] = utf8_encode($res['DS_NOME']);
        }

        return $selectData;
    }

    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10")
    {
        $select = new Select();
        $where = ' 1=1 ';
        foreach ($param as $field => $search) {
            $where = $where . ' AND ' . $field . " like '%" . $search . "%'";
        }

        $select->from($this->table)
            ->where($where)
            ->order("DS_NOME");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function nextId()
    {

        $select = $this->getSql()->select();
        $select->columns(array(new Expression('ISNULL(MAX(cd_perfil_web),0) + 1 as cd_perfil_web')));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->cd_perfil_web;
    }

    public function getId($id)
    {
        $id = (int) $id;

        $select = $this->getSql()->select();
        $select->columns(
            array('cd_perfil_web',
                  'ds_nome',
                   'st_ativo'
            ))
            ->where(array('cd_perfil_web' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row;
    }


    public function save($tableData)
    {
        $isNew = false;

        try {
            if ($tableData->cd_perfil_web) {
                $base = $this->getId($tableData->cd_perfil_web);
            } else {
                $isNew= true;
                $tableData->st_ativo = 'S';
            }

            $data = array(
                "ds_nome" => (isset($tableData->ds_nome)) ? trim(utf8_decode($tableData->ds_nome)) : trim($base->ds_nome),
                "st_ativo" => (isset($tableData->st_ativo)) ? $tableData->st_ativo : $base->st_ativo
            );

            if ($isNew) {
                $data["cd_perfil_web"] = $this->nextId();
            }
//var_dump($tableData,$data,$isNew);exit;
            if (!$isNew) {
                if(!$this->update($data, array("cd_perfil_web" => $tableData->cd_perfil_web)))
                    throw new \Exception ;
            } else {
                if(!$this->insert($data))
                    throw new \Exception;
            }
            return $data['cd_perfil_web'];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function inativar($id)
    {

        $id = (int) $id;

        if ($this->getId($id)) {
            $data = array(
                'st_ativo'	=> 'N'
            );

            return $this->update($data, array("cd_perfil_web" => $id));

        } else {
            throw new \Exception("Identificador $id  não existe no banco de dados!");
        }
    }

    public function ativar($id)
    {

        $id = (int) $id;

        if ($this->getId($id)) {
            $data = array(
                'st_ativo'	=> 'S'
            );

            return $this->update($data, array("cd_perfil_web" => $id));

        } else {
            throw new \Exception("Identificador $id  não existe no banco de dados!");
        }
    }

    public function buscarPerfil($dsNome)
    {
        $statement = $this->adapter->query(
            " SELECT 	
                  CD_PERFIL_WEB
                  ,DS_NOME
                  ,ST_ATIVO
             FROM  ". $this->table ." 
             WHERE UPPER(DS_NOME) LIKE '" . strtoupper($dsNome) . "%' 
             ORDER BY DS_NOME ");

        return $statement->execute();
    }

    public function getMenusPerfil($id)
    {
        $sql =  " SELECT
                      PWM.CD_MENU
	                , MW.CD_MENU_PAI
	                , MW.DS_URL
                  FROM  ". $this->table ." PW
                  INNER JOIN TB_PERFIL_WEB_MENUS PWM ON PWM.CD_PERFIL_WEB = PW.CD_PERFIL_WEB
                  INNER JOIN TB_MENU_WEB MW ON MW.CD_MENU = PWM.CD_MENU
                  WHERE PW.ST_ATIVO = 'S' ";

        $params= array();
        if ($id != null) {
            $sql .= " AND PW.CD_PERFIL_WEB = ?";
            $params =  array($id);
        }

        $sql .= " ORDER BY MW.CD_MENU,ISNULL(MW.CD_MENU_PAI,MW.CD_MENU) ";

        $statement = $this->adapter->query($sql);
        $resultset = $statement->execute($params);
        $results   = iterator_to_array($resultset,false);

        $resultsIndexed = array();

        foreach($results as $key => $menu) {
            $resultsIndexed[$menu['CD_MENU']] = $menu['DS_URL'];
        }

        return $resultsIndexed;
    }

    public function getPerfilUsuarioWebForSelectOptions($id = null)
    {
        $sql = "SELECT CD_PERFIL_WEB, DS_NOME  FROM ".$this->table;
        $params= array();

        if ($id != null) {
            $sql .= " WHERE CD_PERFIL_WEB = ?";
            $params =  array($id);
        }

        $statement = $this->adapter->query($sql);
        $results = $statement->execute($params);

        return $results;
    }

}
