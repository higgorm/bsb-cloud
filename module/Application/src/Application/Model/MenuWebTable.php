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
            ->order("CD_MENU");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }


}
