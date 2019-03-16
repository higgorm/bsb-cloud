<?php

namespace Application\Model;

use Zend\Db\Sql\Where;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;

class NotaTable extends AbstractTableGateway
{
	protected $table 		= "TB_NFE";
	protected $table_config = "TB_NFE_CONFIG";
	
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
	
	public function getConfig($cliente){
		$statement = $this->adapter->query('SELECT * FROM '.$this->table_config.' WHERE CD_LOJA = '.$cliente );
		
		$results = $statement->execute();
		$returnArray = array();
		
		foreach ($results as $result){
			$returnArray[] = $result;
		}
		return $returnArray;
	}
	
	public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10"){

		$select = new Select();
        $where = new Where();
		
		foreach ($param as $field => $search) {
            $where->like($field, '%' . $search . '%');
        }
		
		$select->from($this->table)
            ->where($where)
            ->order("infNFE DESC");
			
		$adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
	}

    public function fetchArray(Array $param = array()){
        $returnArray = array();
        $adapter = $this->adapter;
        $sql = new Sql($adapter);
        $select = new Select();
        $where = new Where();

        foreach ($param as $field => $search) {
            $where->like($field, '%' . $search . '%');
        }

        $select->from($this->table)
            ->where($where)
            ->order("infNFE DESC");

        $selectString = $sql->getSqlStringForSqlObject($select);
        $statement = $this->adapter->query( $selectString );

        $results = $statement->execute();
        return $results->current();

    }
	
	public function getNota($nota){
		$statement = $this->adapter->query('SELECT * FROM '.$this->table.' WHERE infNfe = '.$nota);
		
		$results = $statement->execute();
		$returnArray = array();
		
		foreach ($results as $result){
			$returnArray[] = $result;
		}
		return $returnArray;
	}
	
	public function getMercadoria($nota){
		$statement = $this->adapter->query('SELECT * FROM TB_NFE_PRODUTOS WHERE infNfe = '.$nota);
		
		$results = $statement->execute();
		$returnArray = array();
		
		foreach ($results as $result){
			$returnArray[] = $result;
		}
		return $returnArray;	
	}
	
	public function atualiza_config( $cliente, $array ){

		$sql = new Sql($this->adapter);
		$update = $sql->update();
		$update->table($this->table_config);
		$update->set($array);
		
		$statement = $sql->prepareStatementForSqlObject($update);
		$results = $statement->execute();
		
		return $results;
	}
	
	public function insere_nota( $array ){
		$sql = new Sql($this->adapter);
		$insert = $sql->insert( $this->table );
		
		$insert->values($array);
		$selectString = $sql->getSqlStringForSqlObject($insert);
		//$results = $this->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE	);
		
		$statement = $this->adapter->query( $selectString );
		$results = $statement->execute();
		
		return $results;
	}
	
	public function insere_mercadorias( $array){
		$sql = new Sql($this->adapter);
		$insert = $sql->insert( 'TB_NFE_PRODUTOS' );
		
		$insert->values($array);
		$selectString = $sql->getSqlStringForSqlObject($insert);
		//$results = $this->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE	);
		
		$statement = $this->adapter->query( $selectString );
		$results = $statement->execute();
		
		return $results;
		
	}
	
	public function atualiza_nota( $nota, $array ){

		$sql = new Sql($this->adapter);
		$update = $sql->update();
		$update->table($this->table);
		$update->set($array);
		
		if( $nota > 2147483647 ){ 
			$update->where(array('DS_NFE_CHAVE' => $nota));
		}else{
			$update->where(array('infNFE' => $nota));
		}
		
		$statement = $sql->prepareStatementForSqlObject($update);

		$results = $statement->execute();
		
		return $results;	
	}
	
	public function atualiza_mercadorias( $infNFE, $mercadoria, $array ){

		$sql = new Sql($this->adapter);
		$update = $sql->update();
		$update->table('TB_NFE_PRODUTOS');
		$update->set($array);
		
		$update->where(array('infNFE' => $nota, 'CD_MERCADORIA' => $mercadoria));
		
		$statement = $sql->prepareStatementForSqlObject($update);
		$results = $statement->execute();
		
		return $results;	
	}
	
	public function limpa_mercadorias( $infNFE ){
		
		$statement = $this->adapter->query("DELETE FROM TB_NFE_PRODUTOS WHERE infNFE = ?");
												
		return $statement->execute(array($infNFE));
	}
	
	public function getNextId($cliente){
		
		$statement = $this->adapter->query('SELECT NR_NFE + 1 AS nextID FROM '.$this->table_config );//.' WHERE CD_LOJA = '.$cliente );
		
		$results = $statement->execute();
		$returnArray = array();
		
		foreach ($results as $result){
			$return = $result['nextID'];
		}
		return $return;
	}
	
	public function atualiza_nextId( $ID ){

		$sql = new Sql($this->adapter);
		$update = $sql->update();
		$update->table($this->table_config);
		$array = array( 'NR_NFE' => $ID );
		$update->set($array);
		
		$statement = $sql->prepareStatementForSqlObject($update);
		$results = $statement->execute();
		
		return $results;	
	}
	
}
?>