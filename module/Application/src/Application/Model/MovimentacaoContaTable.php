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

class MovimentacaoContaTable extends AbstractTableGateway
{

    protected $table = "TB_MOVIMENTACAO_CONTA";

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

    public function getNextId ($cdLoja){

        $select = new Select();

        $result = $select ->from($this->table)
            ->columns(array(new Expression('max(NR_LACAMENTO)+1 as NR_SEQ_MOV_ESTOQUE')))
            ->where(new Expression('CD_LOJA = '.$cdLoja));
        $rowset = $this->selectWith($result);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->NR_SEQ_MOV_ESTOQUE;
    }

    public function selectById($id,$cdLoja){

        $statement = $this->adapter->query('SELECT * FROM TB_MOVIMENTACAO_ESTOQUE WHERE CD_LOJA = '.$cdLoja.' AND NR_SEQ_MOV_ESTOQUE = '.$id);

        $result = $statement->execute();
        foreach ($result as $field => $search) {
            $selectData[$field] = $search;
        }

        return $selectData;
    }

    public function save ($alt){

        try {
            $result = $this->insert($alt);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $result;
    }

    public function change ($alt,$cdLoja,$cdSeq){

        try {
            $result = $this->update($alt,'CD_LOJA = '.$cdLoja.' AND NR_SEQ_MOV_ESTOQUE = '.$cdSeq);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $result;
    }

}