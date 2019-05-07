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

class MenuWebResourceTable extends AbstractTableGateway {

    protected $table = "TB_MENU_WEB_RESOURCE";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new MenuWebResource());
        $this->initialize();

        $statement = $this->adapter->query("USE LOGIN ");
        $statement->execute();
		
    }


    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "25")
    {
        $select = new Select();
        $where = ' 1=1 ';
        foreach ($param as $field => $search) {
            $where = $where . ' AND ' . $field . " = " . $search ;
        }

        $select->from($this->table)
            ->where($where)
            ->order("DS_MENU_RESOURCE");

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
            array('cd_menu_web_resource',
                'ds_menu_resource',
                'cd_menu'
            ))
            ->where(array('cd_menu_web_resource' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row;
    }

    public function getAllByCdMenu($cdMenu)
    {
        $id = (int) $id;

        $select = $this->getSql()->select();
        $select->columns(
            array('cd_menu_web_resource',
                'ds_menu_resource',
                'cd_menu'
            ))
            ->where(array('cd_menu' => $cdMenu));

        $rowset = $this->selectWith($select);
        //$row = $rowset->current();
        return $rowset;
    }

    public function nextId()
    {

        $select = $this->getSql()->select();
        $select->columns(array(new Expression('ISNULL(MAX(CD_MENU_WEB_RESOURCE),0) + 1 as cd_menu_web_resource')));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->cd_menu_web_resource;
    }


     public function remove($id) {

         if ($this->getId($id)) {
             $this->delete(array("cd_menu_web_resource" => $id));
         } else {
             throw new \Exception("Rota ACL $id  não existe no banco de dados!");
         }
     }

    public function save($tableData)
    {
        $isNew = false;

        try {
            if ($tableData->cd_menu_web_resource) {
                $base = $this->getId($tableData->cd_menu_web_resource);
            } else {
                $isNew= true;
            }

            $data = array(
                "ds_menu_resource"  => (isset($tableData->ds_menu_resource)) ? trim(utf8_decode($tableData->ds_menu_resource)) : trim($base->ds_menu_resource),
                "cd_menu"           => (isset($tableData->cd_menu)) ? trim($tableData->cd_menu) : trim($base->cd_menu),
            );

            if ($isNew) {
                //$data["cd_menu_web_resource"] = $this->nextId();
            }
//var_dump($tableData,$data,$isNew);exit;
            if (!$isNew) {
                if(!$this->update($data, array("cd_menu_web_resource" => $tableData->cd_menu_web_resource)))
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


}
