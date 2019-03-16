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

class CidadeTable extends AbstractTableGateway {

    protected $table = "tb_cidade";

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

    public function getCidadeByUf($uf)
    {
        $statement = $this->adapter->query("SELECT CI.CD_CIDADE, CI.DS_MUNICIPIO 
											FROM TB_CIDADE_IBGE CI 
											INNER JOIN TB_CIDADE C ON CI.CD_CIDADE = C.CD_CIDADE
											WHERE CI.CD_UF = '{$uf}' ORDER BY CI.DS_MUNICIPIO ASC ");
        $result = $statement->execute();

        $selectData = array('' => 'Selecione');

        foreach ($result as $res) {
            $selectData[$res['CD_CIDADE']] = utf8_encode($res['DS_MUNICIPIO']);
        }

        return $selectData;
    }
    
    public function getUfByCidade($cd_cidade)
    {
    	$statement = $this->adapter->query("SELECT CD_UF FROM TB_CIDADE WHERE CD_CIDADE = '{$cd_cidade}'");
        $result = $statement->execute();

        foreach ($result as $res) {
            $selectData = utf8_encode($res['CD_UF']);
        }

        return $selectData;
    }
	
	public function getCidade($cd_cidade)
    {
    	$statement = $this->adapter->query("SELECT DS_CIDADE FROM TB_CIDADE WHERE CD_CIDADE = '{$cd_cidade}'");
        $result = $statement->execute();

        foreach ($result as $res) {
            $selectData = utf8_encode($res['DS_CIDADE']);
        }

        return $selectData;
    }
    

}
