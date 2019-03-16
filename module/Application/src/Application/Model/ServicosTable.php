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

class ServicosTable extends AbstractTableGateway {

    protected $table = "tb_servicos";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Servicos());
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }

    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10") {
        $select = new Select();
        $where = new Where();

        foreach ($param as $field => $search) {
            $where->like($field, '%' . $search . '%');
        }

        $select->from($this->table)
                ->where($where)
                ->order("ds_servico");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function find($id) {
        $id = (int) $id;

        $select = $this->getSql()->select();
        $select->columns(array('cd_servico',
                    'ds_servico',
                    'cd_loja',
                    'vl_servico',
                    'vl_iss',
                    'nr_comissao',
                    'cListServ',
                ))
                ->where(array('cd_servico' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!$row) {
            throw new \Exception("Servico $id  não existe no banco de dados!");
        }

        return $row;
    }

}
