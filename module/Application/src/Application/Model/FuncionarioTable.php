<?php

namespace Application\Model;

use Zend\Db\Sql\Where;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Session\Container;

class FuncionarioTable extends AbstractTableGateway {

    protected $table = "tb_funcionario";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Servicos());
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
		
    }

    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10") {
        $select = new Select();
        $where = new Where();

        foreach ($param as $field => $search) {
            $where->like($field, '%' . $search . '%');
        }

        $select->from($this->table)
            ->where($where)
            ->order("DS_FUNCIONARIO ");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function getDadosFuncionario($dsLogin) {
        $statement = $this->adapter->query("SELECT
                                                    TB_CARGO.ST_GERENTE AS st_gerente,
                                                    CD_FUNCIONARIO
                                            FROM TB_CARGO
                                            INNER JOIN TB_FUNCIONARIO ON TB_FUNCIONARIO.CD_CARGO = TB_CARGO.CD_CARGO
                                            INNER JOIN AdmUsuario ON TB_FUNCIONARIO.CD_USUARIO = AdmUsuario.ideAdmUsuario
                                            WHERE TB_FUNCIONARIO.DT_DESLIGAMENTO IS NULL AND AdmUsuario.DESLOGIN = ?");
        $results = $statement->execute(array($dsLogin));
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray = $result;
        }
        return $returnArray;
    }

    public function getListaFuncionarioLoja($cdLoja) {

        $statement = $this->adapter->query("SELECT
                                                DISTINCT CD_FUNCIONARIO,
                                                UPPER(AdmUsuario.desLogin) DS_LOGIN,
                                                UPPER(DS_FUNCIONARIO) AS DS_FUNCIONARIO
                                            FROM TB_FUNCIONARIO 
                                            INNER JOIN AdmUsuario ON TB_FUNCIONARIO.CD_USUARIO = AdmUsuario.ideAdmUsuario
                                            WHERE TB_FUNCIONARIO.DT_DESLIGAMENTO IS NULL
                                                AND CD_LOJA = ?");
        $results = $statement->execute(array($cdLoja));
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function getListaSupervidoresLoja($cdLoja) {

        $statement = $this->adapter->query("SELECT
                                                DISTINCT CD_FUNCIONARIO,
                                                UPPER(DS_FUNCIONARIO) AS DS_FUNCIONARIO,
                                                UPPER(AdmUsuario.desLogin) DS_LOGIN
                                            FROM TB_CARGO
                                            INNER JOIN TB_FUNCIONARIO ON TB_FUNCIONARIO.CD_CARGO = TB_CARGO.CD_CARGO
                                            INNER JOIN AdmUsuario ON TB_FUNCIONARIO.CD_USUARIO = AdmUsuario.ideAdmUsuario
                                            WHERE TB_FUNCIONARIO.DT_DESLIGAMENTO IS NULL
                                                AND CD_LOJA = ?
                                                AND ST_GERENTE = 'S' ");
        $results = $statement->execute(array($cdLoja));
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function find($cdFuncionario)
    {
        $statement = $this->adapter->query("SELECT *
                                            FROM TB_FUNCIONARIO
                                            WHERE CD_FUNCIONARIO = ? ");
        $results = $statement->execute(array($cdFuncionario));
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

}
