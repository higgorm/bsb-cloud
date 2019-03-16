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

class TabelaTable extends AbstractTableGateway
{
	protected $table = "TB_TIPO_PAGAMENTO";

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

    public function selectAll_formaPagamento(){
        $statement = $this->adapter->query('SELECT * FROM TB_TIPO_PAGAMENTO ORDER BY DS_TIPO_PAGAMENTO ASC');

        $results = $statement->execute();
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

	public function getOne($table, $where){
		$statement = $this->adapter->query('SELECT * FROM '.$table.' WHERE '.$where );

		$results = $statement->execute();
		$returnArray = array();

		foreach ($results as $result){
			$returnArray[] = $result;
		}
		return $returnArray;
	}

	public function insere_formaPagamento( $array ){
		$sql = new Sql($this->adapter);
		$insert = $sql->insert( 'TB_TIPO_PAGAMENTO' );
		$insert->values($array);

		$statement = $sql->getSqlStringForSqlObject($insert);
		$results = $statement->execute();

		return $results;
	}

	public function atualiza_formaPagamento( $id, $array ){

		$sql = new Sql($this->adapter);
		$update = $sql->update();
		$update->table('TB_TIPO_PAGAMENTO');
		$update->set($array);
		$update->where(array('CD_TIPO_PAGAMENTO' => $id));

		$statement = $sql->prepareStatementForSqlObject($update);
		$results = $statement->execute();

		return $results;
	}

	public function selectAll_cfop(){
        $statement = $this->adapter->query('SELECT * FROM TB_NATUREZA_OPERACAO ORDER BY CD_NATUREZA_OPERACAO ASC');

        $results = $statement->execute();
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

	public function insere_cfop( $array ){
		$sql = new Sql($this->adapter);
		$insert = $sql->insert( 'TB_NATUREZA_OPERACAO' );
		$insert->values($array);
		
		$selectString = $sql->getSqlStringForSqlObject($insert);
		$statement = $this->adapter->query( $selectString );
		$results = $statement->execute();

		return $results;
	}

	public function atualiza_cfop( $id, $array ){
		$sql = new Sql($this->adapter);
		$update = $sql->update();
		$update->table('TB_NATUREZA_OPERACAO');
		$update->set($array);
		$update->where(array('CD_NATUREZA_OPERACAO' => $id));

		$statement = $sql->prepareStatementForSqlObject($update);
		$results = $statement->execute();

		return $results;
	}

	public function selectAll_cartao(){
        $statement = $this->adapter->query('SELECT * FROM TB_CARTAO ORDER BY DS_CARTAO ASC');

        $results = $statement->execute();
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

	public function insere_cartao( $array ){
		$sql = new Sql($this->adapter);
		$insert = $sql->insert( 'TB_CARTAO' );
		$insert->values($array);

		$$statement = $sql->getSqlStringForSqlObject($insert);
		$results = $statement->execute();

		return $results;
	}

	public function atualiza_cartao( $id, $array ){
		$sql = new Sql($this->adapter);
		$update = $sql->update();
		$update->table('TB_CARTAO');
		$update->set($array);
		$update->where(array('CD_CARTAO' => $id));

		$statement = $sql->prepareStatementForSqlObject($update);
		$results = $statement->execute();

		return $results;
	}
}
