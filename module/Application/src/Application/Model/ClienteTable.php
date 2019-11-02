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

class ClienteTable extends AbstractTableGateway
{

    protected $table = "TB_CLIENTE";

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Cliente());
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
        //$where = new Where();

		$where = 'DT_EXCLUSAO IS NULL';
        foreach ($param as $field => $search) {
            $where = $where . ' AND ' . $field . " like '%" . $search . "%'";
        }
		
        $select->from($this->table)
            ->where($where)
            ->order("DS_NOME_RAZAO_SOCIAL");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function nextId()
    {

        $select = $this->getSql()->select();
        $select->columns(array(new Expression('max(cd_cliente)+1 as cd_cliente')));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->cd_cliente;
    }

    public function getId($id)
    {
        $id = (int) $id;

        $select = $this->getSql()->select();
        $select->columns(array('cd_cliente',
            'ds_nome_razao_social',
            'ds_fantasia',
            'tp_cliente',
            'nr_cgc_cpf',
            'ds_atividade',
            'ds_contato',
            'ds_sexo',
            'ds_endereco',
            'ds_bairro',
            'cd_cidade',
            'nr_cep',
            'ds_fone1',
            'ds_fone2',
            'ds_fone3',
            'nr_insc_estadual',
            'ds_identidade',
            'dt_nascimento',
            'cd_banco',
            'cd_agencia',
            'nr_conta',
            'ds_email',
            'st_bloqueio',
            'dt_bloqueio',
            'cd_conceito',
            'cd_praca',
            'ds_tipo_conta_bancaria',
            'ds_estado_civil',
            'st_insc_est_consumidor_final',
            'ds_identifica_cliente',
            'st_isento',
            'dt_exclusao',
            'rotatividade_compra',
            'tp_cad_cliente',
            'nr_carteira_profissional',
            'serie',
            'st_compoe_renda',
            'ds_endereco_anterior',
            'ds_naturalidade',
            'ds_nacionalidade',
            'dt_emissao',
            'st_tipo_residencia',
            'dt_ultimaalteracao',
            'usuarioultimaalteracao',
            'st_empresadogrupo',
            'st_mala_direta',
            'st_criticar_credito',
            'st_consignado',
            'nr_dias_faturamento',
            'nr_desconto_consignado',
            'st_conceder_desconto_boleto',
            'nr_desconto_boleto',
            'ds_codigo_cliente',
            'cd_origem',
            'st_cartao_fidelidade_entregue',
            'dt_retorno',
            'st_envia_sms',
			'ds_suframa',
			'indIE',
			'ds_numero',
			'nr_insc_municipal',
			'ds_complemento',
        ))
            ->where(array('cd_cliente' => $id));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row;
    }

    public function getClientePorNrCgcCpf($nrCgcCpf)
    {

        $select = $this->getSql()->select();
        $select->columns(
            array('cd_cliente',
            'ds_nome_razao_social',
            'ds_fantasia',
            'tp_cliente',
            'nr_cgc_cpf',
            'dt_exclusao',
        ))
        ->where(array(
                'nr_cgc_cpf' => trim($nrCgcCpf),
               // 'dt_exclusao' => ''
        ));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row;
    }

    /**
     * @param $nuPedido
     * @return mixed
     */
    public function recuperaClienteNumeroCgcCpf($nrCgcCpf) {
        $select = "SELECT C.*
                   FROM TB_CLIENTE C
                   WHERE C.NR_CGC_CPF = ?";

        $statement      = $this->adapter->query($select);
        $results        = $statement->execute(array('nr_cgc_cpf' => $nrCgcCpf));
        $returnArray    = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }

        return $returnArray;
    }


    public function save($tableData)
    {

        try {
            if ($tableData->cd_cliente)
                $base = $this->getId($tableData->cd_cliente);
			else 
				$tableData->cd_cliente = $this->nextId();

			
            $data = array(
                "cd_cliente" => $tableData->cd_cliente,
                "ds_nome_razao_social" => (isset($tableData->ds_nome_razao_social)) ? trim($tableData->ds_nome_razao_social) : trim($base->ds_nome_razao_social),
                "ds_fantasia" => (isset($tableData->ds_fantasia)) ? trim(($tableData->ds_fantasia)) : trim($base->ds_fantasia),
                "tp_cliente" => (isset($tableData->tp_cliente)) ? $tableData->tp_cliente : $base->tp_cliente,
                "ds_fone1" => (isset($tableData->ds_fone1)) ? $tableData->ds_fone1 : $base->ds_fone1,
                "ds_fone2" => (isset($tableData->ds_fone2)) ? $tableData->ds_fone2 : $base->ds_fone2,
                "dt_nascimento" => (isset($tableData->dt_nascimento)) ? date(FORMATO_ESCRITA_DATA_HORA, strtotime($tableData->dt_nascimento . '/1990')) : date(FORMATO_ESCRITA_DATA_HORA, strtotime($base->dt_nascimento)),
                "ds_email" => (isset($tableData->ds_email)) ? $tableData->ds_email : $base->ds_email,
                "cd_origem" => (isset($tableData->cd_origem)) ? $tableData->cd_origem : $base->cd_origem,
                 "st_cartao_fidelidade_entregue" => (isset($tableData->st_cartao_fidelidade_entregue)) ? $tableData->st_cartao_fidelidade_entregue : $base->st_cartao_fidelidade_entregue,
                 "nr_cgc_cpf" => (isset($tableData->nr_cgc_cpf)) ?  str_replace(array('.',',','/','-'),array('','','',''),$tableData->nr_cgc_cpf) : str_replace(array('.',',','/','-'),array('','','',''),$base->nr_cgc_cpf ),
                 "ds_sexo" => (isset($tableData->ds_sexo)) ? $tableData->ds_sexo : $base->ds_sexo,
                 "ds_endereco" => (isset($tableData->ds_endereco)) ? $tableData->ds_endereco : $base->ds_endereco,
                 "ds_bairro" => (isset($tableData->ds_bairro)) ? $tableData->ds_bairro : $base->ds_bairro,
                 "cd_cidade" => (isset($tableData->cd_cidade)) ? $tableData->cd_cidade : $base->cd_cidade,
                 "nr_cep" => (isset($tableData->nr_cep)) ? str_replace(array('.',',','/','-'),array('','','',''),$tableData->nr_cep) : $base->nr_cep,
                 "st_empresadogrupo" => (isset($tableData->st_empresadogrupo)) ? $tableData->st_empresadogrupo : $base->st_empresadogrupo,
                  "st_mala_direta" => (isset($tableData->st_mala_direta)) ? $tableData->st_mala_direta : $base->st_mala_direta,
                 "st_criticar_credito" => (isset($tableData->st_criticar_credito)) ? $tableData->st_criticar_credito : $base->st_criticar_credito,
                  "st_consignado" => (isset($tableData->st_consignado)) ? $tableData->st_consignado : $base->st_consignado,
                  "dt_ultimaalteracao" => date(FORMATO_ESCRITA_DATA_HORA),
                  "usuarioultimaalteracao" => (isset($tableData->usuarioultimaalteracao)) ? $tableData->usuarioultimaalteracao : $base->usuarioultimaalteracao,
//  //                "st_envia_email" => (isset($tableData->st_envia_email)) ? $tableData->st_envia_email : $base->st_envia_email,
                  "st_envia_sms" => (isset($tableData->st_envia_sms)) ? $tableData->st_envia_sms : $base->st_envia_sms,
                  "nr_insc_estadual" => (isset($tableData->nr_insc_estadual)) ? $tableData->nr_insc_estadual : $base->nr_insc_estadual,
                  "ds_suframa" => (isset($tableData->ds_suframa)) ? $tableData->ds_suframa : $base->ds_suframa,
                  "indIE" => (isset($tableData->indIE)) ? $tableData->indIE : $base->indIE,
                  "ds_numero" => (isset($tableData->ds_numero)) ? $tableData->ds_numero : $base->ds_numero,
                  "nr_insc_municipal" => (isset($tableData->nr_insc_municipal)) ? $tableData->nr_insc_municipal : $base->nr_insc_municipal,
                  "ds_complemento" => (isset($tableData->ds_complemento)) ? $tableData->ds_complemento : $base->ds_complemento,
            );
			//die(var_dump($data));

            if (!empty($base->cd_cliente)) {
                if(!$this->update($data, array("cd_cliente" => $tableData->cd_cliente)))
                    throw new \Exception ;

            } else {

                if(!$this->insert($data))
                    throw new \Exception;
            }
            return $data['cd_cliente'];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function remove($id)
    {

        $id = (int) $id;

        if ($this->getId($id)) {
            //$this->delete(array("cd_cliente" => $id));
			$data = array(
				'DT_EXCLUSAO'	=> date(FORMATO_ESCRITA_DATA_HORA)
			);
			$this->update($data, array("cd_cliente" => $id));
			
        } else {
            throw new \Exception("Identificador $id  não existe no banco de dados!");
        }
    }

    public function buscarcliente($dsNome)
    {
        $statement = $this->adapter->query(" SELECT TOP 50 	C.CD_CLIENTE,
															C.DS_NOME_RAZAO_SOCIAL, 
															C.DS_FONE1, DS_FONE2, 
															C.NR_CGC_CPF,
															C.DS_EMAIL,
															C.NR_INSC_ESTADUAL,
															C.indIE,
															C.DS_ENDERECO,
															C.DS_NUMERO,
															C.DS_BAIRRO,
															C.CD_CIDADE,
															UF.CD_UF,
															C.NR_CEP,
															C.DS_SUFRAMA
             FROM TB_CLIENTE C
			 	LEFT JOIN TB_CIDADE UF on C.CD_CIDADE = UF.CD_CIDADE
             WHERE UPPER(DS_NOME_RAZAO_SOCIAL) LIKE '" . strtoupper($dsNome) . "%' 
             ORDER BY DS_NOME_RAZAO_SOCIAL ");

        return $statement->execute();
    }
	
	public function pesquisaClientePorParamentro($arrParam)
    {
        $select = "SELECT C.*
                    FROM TB_CLIENTE C
                    WHERE DT_EXCLUSAO IS NULL ";

        if($arrParam['st_tipo_pesquisa'] == 1){
            $select .= " AND C.CD_CLIENTE = ".$arrParam['codigoCliente'];
        }

        if($arrParam['st_tipo_pesquisa'] == 2){
            $select .= " AND C.DS_NOME_RAZAO_SOCIAL like '%".$arrParam['codigoCliente']."%' ";
        }
		
		if($arrParam['st_tipo_pesquisa'] == 3){
            $select .= " AND C.DS_FANTASIA like '%".$arrParam['codigoCliente']."%' ";
        }

        if($arrParam['st_tipo_pesquisa'] == 4){
            $select .= " AND C.NR_CGC_CPF like '".$arrParam['codigoCliente']."%' ";
        }

        $statement = $this->adapter->query($select);
        $result = $statement->execute();

        return $result->current();
    }


    public function pesquisaClientePedidoPorParametro($arrParam)
    {
        $select = "SELECT C.*
                    FROM TB_CLIENTE C
                    WHERE DT_EXCLUSAO IS NULL ";

        if($arrParam['st_tipo_pesquisa'] == 1){
            $select .= " AND C.CD_CLIENTE = ".$arrParam['codigoCliente'];
        }

        if($arrParam['st_tipo_pesquisa'] == 2){
            $select .= " AND C.DS_NOME_RAZAO_SOCIAL like '%".$arrParam['codigoCliente']."%' ";
        }

        if($arrParam['st_tipo_pesquisa'] == 3){
            $select .= " AND C.DS_FANTASIA like '%".$arrParam['codigoCliente']."%' ";
        }

        if($arrParam['st_tipo_pesquisa'] == 4){
            $select .= " AND C.NR_CGC_CPF like '".$arrParam['codigoCliente']."%' ";
        }
        $statement = $this->adapter->query($select);
        $results = $statement->execute();

        return iterator_to_array($results,false);

    }

}
