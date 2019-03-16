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

class TipoMovimentoCaixaTable extends AbstractTableGateway {

    protected $table = "TB_TIPO_MOVIMENTO_CAIXA";

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

    public function listaTipoMovimentoCaixa($stDebCred)
    {
        $statement = $this->adapter->query('SELECT CD_TIPO_MOVIMENTO_CAIXA, DS_TIPO_MOVIMENTO_CAIXA '
                                        . 'FROM TB_TIPO_MOVIMENTO_CAIXA '
                                        . 'WHERE ST_DEB_CRED = ? ');
        
        $result = $statement->execute(array($stDebCred));

        $selectData = array('' => 'Selecione');

        foreach ($result as $res) {
            $selectData[$res['CD_TIPO_MOVIMENTO_CAIXA']] = utf8_encode($res['DS_TIPO_MOVIMENTO_CAIXA']);
        }

        return $selectData;
    }

}
