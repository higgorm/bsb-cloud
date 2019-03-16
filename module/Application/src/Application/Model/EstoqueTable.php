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

class EstoqueTable extends AbstractTableGateway
{

    protected $table = "TB_ESTOQUE";

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

    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10")
    {
        $select = new Select();
        $where = new Where();

        foreach ($param as $field => $search) {
            $where->like($field, '%' . $search . '%');
        }

        $select->from($this->table)
                 ->where($where)
                ->order('DTUltimaAlteracao');

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }
    public function getEstoqueByMercadoria ($cdMercadoria, $cdLoja){

        $select = new Select();
        $select ->from($this->table)
                ->where(array('CD_LOJA'=>$cdLoja,
                                'CD_MERCADORIA'=>$cdMercadoria));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->NR_QTDE_ESTOQUE;
    }

    public function attEstoque($cdLoja, $cdMercadoria, $estoque){

        $upd = array(
            'NR_QTDE_ESTOQUE' => $estoque
        );
        try{
            $this->update($upd,'CD_LOJA = '.$cdLoja.' AND CD_MERCADORIA = '.$cdMercadoria);
        }catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
    }
}