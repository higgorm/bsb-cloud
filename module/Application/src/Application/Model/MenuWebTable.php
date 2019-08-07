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

class MenuWebTable extends AbstractTableGateway {

    protected $table = "TB_MENU_WEB";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new MenuWeb());
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
            ->where($where)
            ->order("CD_MENU desc");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function getId($id)
    {
        $id = (int) $id;

        $select = $this->getSql()->select();
        $select->columns(
            array('cd_menu',
                'ds_menu',
                'cd_menu_pai',
                'ds_url',
                'ds_icone',
                'st_ativo'
            ))
            ->where(array('cd_menu' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row;
    }

    public function nextId()
    {

        $select = $this->getSql()->select();
        $select->columns(array(new Expression('ISNULL(MAX(cd_menu),0) + 1 as cd_menu')));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->cd_menu;
    }

    public function save($tableData)
    {
        $isNew = false;

        try {
            if ($tableData->cd_menu) {
                $base = $this->getId($tableData->cd_menu);
            } else {
                $isNew= true;
                $tableData->st_ativo = 'S';
            }

            $data = array(
                "ds_menu"       => (isset($tableData->ds_menu)) ? trim(utf8_decode($tableData->ds_menu)) : trim($base->ds_menu),
                "cd_menu_pai"   => (isset($tableData->cd_menu_pai)) ? trim($tableData->cd_menu_pai) : trim($base->cd_menu_pai),
                "ds_url"        => (isset($tableData->ds_url)) ? trim(utf8_decode($tableData->ds_url)) : trim($base->ds_url),
                "ds_icone"      => (isset($tableData->ds_icone)) ? trim(utf8_decode($tableData->ds_icone)) : trim($base->ds_icone),
                "st_ativo"      => (isset($tableData->st_ativo)) ? $tableData->st_ativo : $base->st_ativo
            );

            if ($isNew) {
                $data["cd_menu"] = $this->nextId();
            }
//var_dump($tableData,$data,$isNew);exit;
            if (!$isNew) {
                if(!$this->update($data, array("cd_menu" => $tableData->cd_menu)))
                    throw new \Exception ;
            } else {
                if(!$this->insert($data))
                    throw new \Exception;
            }
            return $data['cd_menu'];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getMenuPai()
    {

        $select     = "SELECT cd_menu, ds_menu FROM ". $this->table ." WHERE cd_menu_pai IS NULL";
        $statement  = $this->adapter->query($select);
        $result     = $statement->execute();

        return $result;

    }

}
