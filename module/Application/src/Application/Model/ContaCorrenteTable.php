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

class ContaCorrenteTable extends AbstractTableGateway
{

    protected $table = "TB_CONTA_CORRENTE";

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

    public function selectAll($cdLoja){
        $statement = $this->adapter->query('SELECT * FROM '.$this->table.' AS A
         INNER JOIN TB_BANCO AS B ON A.CD_BANCO = B.CD_BANCO WHERE CD_LOJA = '.$cdLoja.' ORDER BY DS_AGENCIA ASC');

        $results = $statement->execute();
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function nextId($cdLoja)
    {

        $select = new Select();
        $select ->from($this->table)
            ->columns(array(new Expression('max(CD_CONTA)+1 as CD_CONTA')))
            ->where(new Expression('CD_LOJA = '.$cdLoja));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->NR_DOCUMENTO_CP;
    }

    public function save ($alt){

        try {
            $result = $this->insert($alt);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $result;
    }

    public function selectById($cdLoja,$id){

        $statement = $this->adapter->query('SELECT * FROM '.$this->table.'
        WHERE CD_LOJA = '.$cdLoja.' AND CD_CONTA = '.$id);

        $result = $statement->execute();
        foreach ($result as $field => $search) {
            $selectData[$field] = $search;
        }

        return $selectData;
    }


    public function change ($alt,$cdLoja,$cdSeq){
        try {
            $result = $this->update($alt,'CD_LOJA = '.$cdLoja.' AND NR_DOCUMENTO_CP = '.$cdSeq);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $result;
    }

}