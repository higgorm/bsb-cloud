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

class LojaTable extends AbstractTableGateway {

    protected $table = "tb_loja";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Loja());
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
                ->order("ds_razao_social ");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function getLojaDefault($id) {
		
        $id = (int) $id;

        $select = $this->getSql()->select();

        $select->columns(array('cd_loja', 'ds_fantasia'))
                ->join('tb_paramempresa', 'tb_paramempresa.cd_loja = tb_loja.cd_loja')
                ->where(array('tb_paramempresa.FlaLojaDefault' => 'S'))
                ->where(array('tb_loja.cd_loja' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!$row) {
            throw new \Exception("Identificador $id  n�o existe no banco de dados!");
        }
		
        return $row;
    }

    public function getLojaLogin() {

        $select = $this->getSql()->select();

        $select->columns(array('cd_loja', 'ds_fantasia'))
                ->join('tb_paramempresa', 'tb_paramempresa.cd_loja = tb_loja.cd_loja')
                ->where(array('tb_paramempresa.FlaLojaDefault' => 'S'));

        $rowset = $this->selectWith($select);

        return $rowset;
    }
	
	public function getDadosLogin($ds_usuario) {
		
		$statement = $this->adapter->query("USE LOGIN");  
        $statement->execute();
		
        $statement = $this->adapter->query("SELECT TB_LOJA.*, 
												TB_USUARIO_WEB.DS_USUARIO,
												TB_USUARIO_WEB.DS_NOME,
												TB_USUARIO_WEB.ST_ATIVO
											FROM TB_LOJA
											LEFT JOIN TB_USUARIO_WEB ON TB_USUARIO_WEB.CD_LOJA = TB_LOJA.CD_LOJA
											WHERE TB_USUARIO_WEB.DS_USUARIO = '".$ds_usuario."'");

        $results = $statement->execute();     
        $returnArray = array();
        foreach ($results as $result) {
            $returnArray = $result;
        }
        return $returnArray;
    }

    public function getId($id) {
        $id = (int) $id;

        $select = $this->getSql()->select();
        $select->columns(array('cd_loja',
                    'ds_razao_social',
                    'ds_fantasia',
                    'nr_cgc',
                    'ds_endereco',
                    'ds_bairro',
                    'cd_cidade',
                    'nr_cep',
                    'ds_fone1',
                    'ds_fone2',
                    'cd_matriz',
                    'ds_email',
                    'nr_insc_estadual',
                    'ds_contato',
                    'icms_lj_interno',
                    'icms_lj_externo',
                    'cd_centro_custo',
                    'cd_cliente',
                    'ds_site'
                ))
                ->where(array('cd_loja' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            throw new \Exception("Identificador $id  n�o existe no banco de dados!");
        }

        return $row;
    }
        
    public function getLojaCidadeUf($id)
    {
        $statement = $this->adapter->query("select l.CD_LOJA, l.DS_RAZAO_SOCIAL, l.CD_CIDADE, c.CD_UF 
                                            from TB_LOJA l
                                            join TB_CIDADE c on l.CD_CIDADE = c.CD_CIDADE
                                            where l.CD_LOJA = ?");
        $results = $statement->execute(array($id));     
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray = $result;
        }
        return $returnArray;
    }

}
