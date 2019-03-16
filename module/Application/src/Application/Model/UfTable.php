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

class UfTable extends AbstractTableGateway {

    protected $table = "tb_uf";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Uf());
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
		
    }

    public function listUf()
    {
        $statement = $this->adapter->query('SELECT CD_UF, DS_UF FROM TB_UF');
        $result = $statement->execute();

        $selectData = array('' => 'Selecione');

        foreach ($result as $res) {
            $selectData[$res['CD_UF']] = utf8_encode($res['DS_UF']);
        }

        return $selectData;
    }

}
