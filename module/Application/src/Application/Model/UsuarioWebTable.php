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

class UsuarioWebTable extends AbstractTableGateway {

    protected $table = "TB_USUARIO_WEB";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new UsuarioWeb());
        $this->initialize();

        $statement = $this->adapter->query("USE LOGIN ");
        $statement->execute();
		
    }

    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10")
    {
        $select = new Select();
        $where = ' 1=1 ';
        foreach ($param as $field => $search) {
            $where = $where . ' AND ' . $field . " like '%" . $search . "%'";
        }

        $select->from($this->table)
            ->where($where)
            ->order("DS_USUARIO");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function nextId()
    {
        $select = $this->getSql()->select();
        $select->columns(array(new Expression('max(cd_usuario_web)+1 as cd_usuario_web')));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->cd_usuario_web;
    }

    public function getId($id)
    {
        $id = (int) $id;

        $select = $this->getSql()->select();
        $select->columns(
            array('cd_usuario_web',
                'cd_loja',
                'ds_usuario',
                'ds_senha',
                'ds_nome',
                'ds_email',
                'nr_telefone',
                'st_ativo',
                'cd_perfil_web'
            ))
            ->where(array('cd_usuario_web' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row;
    }

    public function save($tableData, $saveNewPassword = false)
    {
        $isNew= false;

        try {
            if ($tableData->cd_usuario_web) {
                $base = $this->getId($tableData->cd_usuario_web);
            } else {
                $isNew= true;
                $tableData->st_ativo = 'S';
            }

            $data = array(
             //  "cd_usuario_web" => (isset($tableData->cd_usuario_web)) ? (int)$tableData->cd_usuario_web : $base->cd_usuario_web,
                "cd_loja" => (isset($tableData->cd_loja)) ? (int)$tableData->cd_loja : $base->cd_loja,
                "ds_usuario" => (isset($tableData->ds_usuario)) ? trim(utf8_decode($tableData->ds_usuario)) : trim($base->ds_usuario),
                "ds_nome" => (isset($tableData->ds_nome)) ? trim(utf8_decode($tableData->ds_nome)) : trim($base->ds_nome),
                "ds_email" => (isset($tableData->ds_email)) ? $tableData->ds_email : $base->ds_email,
                "nr_telefone" => (isset($tableData->nr_telefone)) ? $tableData->nr_telefone : $base->nr_telefone,
                "st_ativo" => (isset($tableData->st_ativo)) ? $tableData->st_ativo : $base->st_ativo,
                "cd_perfil_web" => (isset($tableData->cd_perfil_web)) ? (int)$tableData->cd_perfil_web : $base->cd_perfil_web

            );

            //if ($isNew) {
            //   $data["cd_usuario_web"] = $this->nextId();
            //}

            if ($saveNewPassword) {
                $data["ds_senha"] = (isset($tableData->ds_senha)) ? $tableData->ds_senha : $base->ds_senha;
                $data["ds_senha"] = strtoupper(md5( $data["ds_senha"]));
            }
            //die(var_dump($isNew,$saveNewPassword,$data,$tableData));

            if (!$isNew) {
                if(!$this->update($data, array("cd_usuario_web" => $tableData->cd_usuario_web)))
                    throw new \Exception ;

            } else {

                if(!$this->insert($data))
                    throw new \Exception;
            }
            return $data['cd_usuario_web'];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function inativar($id)
    {
        $id = (int) $id;

        if ($this->getId($id)) {
            $data = array(
                'st_ativo'	=>  'N'
            );

            return $this->update($data, array("cd_usuario_web" => $id));


        } else {
            throw new \Exception("Identificador $id  não existe no banco de dados!");
        }
    }

    public function ativar($id)
    {
        $id = (int) $id;

        if ($this->getId($id)) {
            $data = array(
                'st_ativo'	=>  'S'
            );

            return $this->update($data, array("cd_usuario_web" => $id));


        } else {
            throw new \Exception("Identificador $id  não existe no banco de dados!");
        }
    }

    public function buscarUsuario($dsNome)
    {
        $statement = $this->adapter->query(
            " SELECT 	
                  CD_USUARIO_WEB
                  ,CD_LOJA
                  ,DS_USUARIO
                  ,DS_SENHA
                  ,DS_NOME
                  ,DS_EMAIL
                  ,NR_TELEFONE
                  ,ST_ATIVO
                  ,CD_PERFIL_WEB
             FROM TB_USUARIO_WEB 
             WHERE UPPER(DS_USUARIO) LIKE '" . strtoupper($dsNome) . "%' 
             ORDER BY DS_USUARIO ");

        return $statement->execute();
    }

    public function getLojaUsuarioWebForSelectOptions($id = null)
    {
        $sql = "SELECT l.CD_LOJA, l.DS_RAZAOSOCIAL  FROM TB_LOJA l ";
        $params= array();

        if ($id != null) {
            $sql .= " WHERE l.CD_LOJA = ?";
            $params =  array($id);
        }

        $statement = $this->adapter->query($sql);
        $results = $statement->execute($params);

        return $results;
    }

}