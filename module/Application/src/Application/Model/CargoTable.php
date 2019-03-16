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

class CargoTable extends AbstractTableGateway 
{
	protected $table = "tb_cargo";
	
	public function __construct(Adapter $adapter) {
		$this->adapter 				= $adapter;
		$this->resultSetPrototype	= new ResultSet();
		$this->resultSetPrototype->setArrayObjectPrototype(new Cargo());
		$this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
	}
	
	public function fetchAll(Array $param = array(),$currentPage="1",$countPerPage="10"){
		$select = new Select();
		$where  = new Where();
		
		foreach($param as $field=>$search){
			$where->like($field, '%' . $search . '%');
		}
		
		$select ->from($this->table)
				->where($where)
				->order("ds_cargo");
		
		$adapter = new DbSelect($select, $this->adapter);
		$paginator = new Paginator($adapter);
		$paginator->setCurrentPageNumber($currentPage);
		$paginator->setItemCountPerPage($countPerPage);
		
		return $paginator;
	}

	public function getStatusGerente($username) {
		
		$select = $this->getSql()->select();
				
		$select	->columns(array('TB_CARGO'=>'st_gerente','TB_FUNCIONARIO'=>'CD_FUNCIONARIO'), false)
                        
                        ->join('TB_FUNCIONARIO','TB_FUNCIONARIO.CD_CARGO = TB_CARGO.CD_CARGO')
                        ->join('AdmUsuario','TB_FUNCIONARIO.CD_USUARIO = AdmUsuario.ideAdmUsuario')
                        
                        ->where(array('TB_FUNCIONARIO.DT_DESLIGAMENTO'=>null))
                        ->where(array('AdmUsuario.DESLOGIN'=>$username));
		
		//echo $select->getSqlString();exit;
		
		$rowset = $this->selectWith($select);
		$row 	= $rowset->current();

		if (!$row) {
			throw new \Exception ( "Cargo / Usu�rio $username  n�o existe no banco de dados!");
		}
		
		return $row;
	}
		
	public function getId($id) {
		$id 	   = (int) $id;

		$select = $this->getSql()->select();
		$select	->columns(array('cd_cargo',
								'ds_cargo',
								'st_motorista',
								'st_vendedor',
								'st_telemarketing',
								'st_gerente',
								'st_tecnico',
				))
				->where(array('cd_cargo'=> $id));
		
		$rowset = $this->selectWith($select);
		$row 	= $rowset->current();
		
		if (!row) {
			throw new \Exception ( "Cargo $id  n�o existe no banco de dados!");
		}
		
		return $row;
	}
	
}