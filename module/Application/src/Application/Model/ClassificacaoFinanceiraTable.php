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

class ClassificacaoFinanceiraTable extends AbstractTableGateway {

    protected $table = "TB_CLASSIFICACAO_FINANCEIRA";

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

    public function listaClassiFinanceira($stDebCred)
    {
        $statement = $this->adapter->query("SELECT CD_CLASSE_FINANCEIRA, DS_ID_CLASSE_FINANCEIRA + ' - ' + DS_CLASSE_FINANCEIRA DS_CLASSE_FINANCEIRA "
                                        . "FROM TB_CLASSIFICACAO_FINANCEIRA "
                                        . "WHERE ST_DEB_CRED = ? ");
        
        $result = $statement->execute(array($stDebCred));

        $selectData = array('' => 'Selecione');

        foreach ($result as $res) {
            $selectData[$res['CD_CLASSE_FINANCEIRA']] = utf8_encode($res['DS_CLASSE_FINANCEIRA']);
        }

        return $selectData;
    }

}
