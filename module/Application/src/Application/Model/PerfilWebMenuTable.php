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

class PerfilWebMenuTable extends AbstractTableGateway {

    protected $table = "TB_PERFIL_WEB_MENUS";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new PerfilWebMenu());
        $this->initialize();

        $statement = $this->adapter->query("USE LOGIN ");
        $statement->execute();
		
    }

    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10")
    {
        $select = new Select();
        $where = ' 1=1 ';
        foreach ($param as $field => $search) {
            $where = $where . ' AND ' . $field . " like '%" . $search . "%'";
        }

        $select->from($this->table)
            ->where($where);

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }


    public function getPorPerfilId($idPerfil)
    {
        $id = (int) $idPerfil;

        $select = $this->getSql()->select();
        $select ->columns(array('cd_perfil_web', 'cd_menu'))
                ->where(array('cd_perfil_web' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();
        return $rowset;
    }

    public function removerPorPerfilId($idPerfil)
    {
        $id = (int) $idPerfil;

        if ($this->getPorPerfilId($id)) {
            return $this->delete(array("cd_perfil_web" => $id));
        } else {
           return false;
        }
    }


    public function save($tableData)
    {

        try {
            if ($tableData->cd_perfil_web) {
                $this->removerPorPerfilId($tableData->cd_perfil_web);
            }

            foreach($tableData->cd_menu as $menu) {
                    $dataPerfilMenu = array(
                        'cd_perfil_web' =>  (int) $tableData->cd_perfil_web,
                        'cd_menu' => (int) $menu
                    );

                    if(!$this->insert($dataPerfilMenu)) {
                        throw new \Exception;
                    }
            }


            return;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function buscarMenusPerfil($idPerfil)
    {
        $return = array();
        $sql    = "SELECT cd_menu  FROM ". $this->table ." WHERE cd_perfil_web = ?";
        $params =  array($idPerfil);

        $statement = $this->adapter->query($sql);
        $results = $statement->execute($params);

        foreach(iterator_to_array($results) as $result) {
            array_push($return , $result['cd_menu']);
        }

        return $return;
    }
}
