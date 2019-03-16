<?php
//e.Guilherme
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

class ContasPagarPagamentoTable extends AbstractTableGateway
{

    protected $table = "TB_CONTASPAGAR_PAGAMENTO";

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }



    public function save ($pagamento){

        try {
            $result = $this->insert($pagamento);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $result;
    }

    public function selectById($cdLoja,$id){

        $statement = $this->adapter->query('SELECT * FROM '.$this->table.' WHERE CD_LOJA = '.$cdLoja.' AND NR_DOCUMENTO_CP_SEQ = '.$id);

        $result = $statement->execute();
        foreach ($result as $field => $search) {
            $selectData[$field] = $search;
        }

        return $selectData;
    }


    public function change ($alt,$cdLoja,$cdSeq){
        try {
            $result = $this->update($alt,'CD_LOJA = '.$cdLoja.' AND NR_DOCUMENTO_CP_SEQ = '.$cdSeq);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $result;
    }

    public function selectParcelas ($cdLoja, $id){

        $statement = $this->adapter->query('SELECT * FROM '.$this->table.'
         WHERE CD_LOJA = '.$cdLoja.' AND NR_DOCUMENTO_CP = '.$id);

        $result = $statement->execute();
        foreach ($result as $field => $search) {
            $selectData[$field] = $search;
        }

        return $selectData;
    }

}