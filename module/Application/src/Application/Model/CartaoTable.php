<?php
//e.Guilherme
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Session\Container;

class CartaoTable extends AbstractTableGateway {

    protected $table = "TB_CARTAO";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->initialize();

		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }

    public function nextId()
    {
        $statement = $this->adapter->query('SELECT MAX(CD_CARTAO)+1 as NEXT FROM '.$this->table);
        $results = $statement->execute();

        $current = $results->current();
        return $current['NEXT'];
    }

    public function selectAll(){
        $statement = $this->adapter->query('SELECT * FROM '.$this->table.' ORDER BY DS_CARTAO ASC');

        $results = $statement->execute();
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }
}