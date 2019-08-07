<?php

namespace Application\Form;

use Zend\Form\Element\Radio;
use Zend\Form\Element\Textarea;
use Zend\Form\Element\Checkbox;
use Zend\Form\Element\Button;
use Zend\Form\Element\File;
use Zend\Form\Element\Text;
use Zend\Form\Element\Hidden;
use Zend\Form\Form;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Session\Container;
use Zend\Db\Sql\Sql;

class ClienteForm extends Form
{

    public $dbAdapter;

    public function __construct($dbAdapter)
    {

        $this->setDbAdapter($dbAdapter);

        parent::__construct("cliente_table");

        $session = new Container("orangeSessionContainer");
        if( @$session->cdBase ){
            $statement = $this->dbAdapter->query("USE BDGE_".$session->cdBase);
            $statement->execute();
        }


        $this->setAttribute('method', 'post');

        //CLIENTE RAPIDO - AGENDA
        $maca = new Hidden("maca");
        $maca->setAttributes(array(
            "id" => "maca",
        ));
        $data = new Hidden("data");
        $data->setAttributes(array(
            "id" => "data",
        ));
        $hora = new Hidden("hora");
        $hora->setAttributes(array(
            "id" => "hora",
        ));
        $vl_desconto = new Hidden("vl_desconto");
        $vl_desconto->setAttributes(array(
            "id" => "vl_desconto",
        ));
        $nr_pedido = new Hidden("nr_pedido");
        $nr_pedido->setAttributes(array(
            "id" => "nr_pedido",
        ));

        $this->add($maca);
        $this->add($data);
        $this->add($hora);
        $this->add($vl_desconto);
        $this->add($nr_pedido);
        ////CLIENTE RAPIDO - AGENDA

        $id = new Hidden("cd_cliente");
        $id->setAttributes(array(
            "id" => "cd_cliente",
        ));

        $dsRazaoSocial = new Text("ds_nome_razao_social");
        $dsRazaoSocial->setLabel("Razão Social")
                ->setAttributes(array(
                    "required" => "required", 
                    "class" => "form-control",
                    "placeholder" => "Informe o nome",
                    "id" => "ds_nome_razao_social",
                    "name" => "ds_nome_razao_social",
        ));

        $dsFone1 = new Text("ds_fone1");
        $dsFone1->setLabel("Telefone 1")
                ->setAttributes(array(
                    "class" => "form-control",
                    'min' => '8',
                    'max' => '15',
                    "id" => "ds_fone1",
                    "required" => "required",
                    "data-mask" => "(99)9999-9999"
        ));

        $dsFone2 = new Text("ds_fone2");
        $dsFone2->setLabel("Telefone 2")
                ->setAttributes(array(
                    "class" => "form-control",
                    'min' => '8',
                    'max' => '15',
                    "id" => "ds_fone2",
                    "required" => false,
                    "data-mask" => "(99)9999-9999"
        ));

        $dtAniversario = new Text("dt_nascimento");
        $dtAniversario->setLabel("Nascimento")
                ->setAttributes(array(
                    "size" => "16",
                    "class" => "input-sm input-s datepicker-input form-control",
                    "id" => "dt_nascimento",
                    'min' => '01-01-1914',
                    'max' => '31-12-2014',
                    'step' => '1',
                    "required" => "required",
                    'data-date-format' => 'dd/mm'
        ));

        $dtExclusao = new Text("dt_exclusao");
        $dtExclusao->setLabel("Data exclusão")
                ->setAttributes(array(
                    "size" => "16",
                    "class" => "dt_exclusao-small form-control",
                    "id" => "dt_exclusao",
                    'min' => '01-01-1914',
                    'max' => '31-12-2014',
                    'step' => '1',
                    "required" => false,
        ));

        $ccgCpf = new Text("nr_cgc_cpf");
        $ccgCpf->setLabel("CPF/CNPJ")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "form-control",
                    "id" => "nr_cgc_cpf",
                    'step' => '1',
                    'type' => 'text',
                    "required" => false,
                    "data-mask" => "999.999.999-99"
        ));

        $dsEndereco = new Text("ds_endereco");
        $dsEndereco->setLabel("Endereço")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "form-control",
                    "id" => "ds_endereco",
                    "required" => false,
                    'type' => 'text',
        ));
		
		$dsNumero = new Text("ds_numero");
        $dsNumero->setLabel("Numero")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "form-control",
                    "id" => "ds_numero",
                    "required" => false,
                    'type' => 'text',
        ));

        $dsBairro = new Text("ds_bairro");
        $dsBairro->setLabel("Bairro")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "form-control",
                    "id" => "ds_bairro",
                    "required" => false,
        ));


        $nrCep = new Text("nr_cep");
        $nrCep->setLabel("CEP")
                ->setAttributes(array(
                    "class" => "form-control",
                    "id" => "nr_cep",
                    'type' => 'text',
                    "required" => false,
        ));

        $dsContato = new Textarea("ds_contato");
        $dsContato->setLabel("Observação")
                ->setAttributes(array(
                    "id" => "ds_contato",
                    "rows" => "2",
                    "class" => "form-control",
                    "required" => false,
        ));


        $this->add(array(
            'name' => 'st_cartao_fidelidade_entregue',
            'type' => 'Zend\Form\Element\Radio',
            'attributes' => array(
                'required' => 'required',
                'value' => 'S',
                'class' => '',
            ),
            'options' => array(
                'label' => 'Cartão Entregue',
                'value_options' => array(
                    'S' => ' Sim',
                    'N' => ' Nao',
                ),
            ),
        ));
		
		$this->add(array(
            'name' => 'indIE',
            'type' => 'Zend\Form\Element\Radio',
            'attributes' => array(
                'required' => 'required',
                'value' => '',
                'class' => '',
            ),
            'options' => array(
                'label' => 'Cartão Entregue',
                'value_options' => array(
                    '1' => ' Contribuinte ICMS',
                    '2' => ' ISENTO',
                    '9' => ' Não Contribuinte',
                ),
            ),
        ));
		
		$this->add(array(
            'name' => 'nr_insc_estadual',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control buscaCidade',
                'id' => 'NR_INSC_ESTADUAL',
                'required' => false,
                'value' => '1',
            ),
            'options' => array(
                'label' => 'Inscrição Estadual',
                'value_options' => $this->getCodMunicipiosSelect(),
            ),
        ));
		
		$this->add(array(
            'name' => 'ds_suframa',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control buscaCidade',
                'id' => 'DS_SUFRAMA',
                'required' => false,
                'value' => '1',
            ),
            'options' => array(
                'label' => 'Inscrição na SUFRAMA',
                'value_options' => $this->getCodMunicipiosSelect(),
            ),
        ));
		
		$this->add(array(
            'name' => 'nr_insc_municipal',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control buscaCidade',
                'id' => 'NR_INSC_MUNICIPAL',
                'required' => false,
                'value' => '1',
            ),
            'options' => array(
                'label' => 'Inscrição na SUFRAMA',
                'value_options' => $this->getCodMunicipiosSelect(),
            ),
        ));

        $this->add(array(
            'name' => 'ds_sexo',
            'type' => 'Zend\Form\Element\Radio',
            'attributes' => array(
                'required' => 'required',
                'value' => 'F',
                'class' => 'radio-inline i-checks',
            ),
            'options' => array(
                'label' => 'Cartão Entregue',
                'value_options' => array(
                    'M' => ' Masculino',
                    'F' => ' Feminino',
                ),
            ),
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Checkbox',
            'name' => 'st_envia_sms',
            'name' => 'st_envia_sms',
            'options' => array(
                'label' => 'Sim',
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
            )
        ));

        $this->add(array(
            'name' => 'cd_origem',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'cd_origem',

                'value' => '1',
            ),
            'options' => array(
                'label' => 'Origem',
                'value_options' => $this->getClienteOrigemOptionsForSelect(),
            ),
        ));

        $this->add(array(
            'name' => 'cd_uf',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control buscaCidade',
                'id' => 'CD_UF',
                'required' => false,
                'value' => '1',
            ),
            'options' => array(
                'label' => 'Origem',
                'value_options' => $this->getUfOptionsForSelect(),
            ),
        ));
		
		$this->add(array(
            'name' => 'cd_ibge',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control buscaCidade',
                'id' => 'CD_IBGE',
                'required' => false,
                'value' => '',
            ),
            'options' => array(
                'label' => 'Município',
                'value_options' => $this->getCidadeOptionsForSelect(),
            ),
        ));
        
        $this->add(array(
            'name' => 'dt_nascimento_d',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'dt_nascimento_d',
                'required' => 'required',
                'style' => 'display:inline;width:35%;',
            ),
            'options' => array(
                'value_options' => $this->diasMes(),
            ),
        ));
        
        $this->add(array(
            'name' => 'dt_nascimento_m',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'dt_nascimento_m',
                'required' => 'required',
                'style' => 'display:inline;width:62%;',
            ),
            'options' => array(
                'value_options' => $this->meses(),
            ),
        ));

        // CRIADO NA CONTROLLER
//        $this->add(array(
//            'name' => 'cd_cidade',
//            'type' => 'Zend\Form\Element\Select',
//            'attributes' => array(
//                'class' => 'form-control',
//                'id' => 'cd_cidade',
//                'required' => false,
//                'value' => '1',
//            ),
//            'options' => array(
//                'label' => 'Cidade',
//                //'value_options' => null,
//            ),
//        ));

        $this->add(array(
            'name' => 'ds_email',
            'type' => 'Zend\Form\Element\Email',
            'attributes' => array(
                'class' => 'form-control',
                'placeholder' => 'Email Address...',
                'required' => false,
            ),
            'options' => array(
                'label' => 'Email',
            ),
        ));


        $submit = new Button('submit');
        $submit->setLabel("Salvar")
                ->setAttributes(array(
                    "type" => "submit",
                    "class" => "btn btn-primary btn"                    
        ));

        $cancel = new Button('reset');
        $cancel->setLabel("Limpar")
                ->setAttributes(array(
                    "type" => "button",
                    "class" => "btn btn-default btn",
                    "onclick" => "javascript:location.href='/cliente/cadastrar'",
        ));

        $return = new Button('return');
        $return->setLabel("Retornar a lista")
                ->setAttributes(array(
                    "type" => "button",
                    "class" => "btn btn-default btn",
                    "onclick" => "javascript:history.go(-1);"
        ));


        $this->add($id);
        $this->add($dsRazaoSocial);
        $this->add($dsFone1);
        $this->add($dsFone2);
        $this->add($dtAniversario);
        $this->add($dtExclusao);
        $this->add($dsEndereco);
        $this->add($dsNumero);
        $this->add($dsBairro);
        $this->add($nrCep);
        $this->add($dsContato);
        $this->add($ccgCpf);
        $this->add($cancel);
        $this->add($return);
        $this->add($submit, array('priority' => -100));
    }

    public function setDbAdapter($dbAdapter)
    {

        $this->dbAdapter = $dbAdapter;
    }

    public function getCidadeOptionsForSelect()
    {
//        $sql = "SELECT CD_CIDADE, DS_CIDADE = DS_CIDADE + ' - ' +CD_UF  FROM TB_CIDADE ORDER BY DS_CIDADE ASC";
//        $statement = $this->dbAdapter->query($sql);
//        $result = $statement->execute();

        $selectData = array();

//        foreach ($result as $res) {
//            $selectData[$res['CD_CIDADE']] = utf8_encode($res['DS_CIDADE']);
//        }
        return $selectData;
    }
	
	public function getCodMunicipiosSelect(){
		$sql = "SELECT  CD_IBGE, DS_MUNICIPIO FROM TB_CIDADE_IBGE WHERE CD_UF = 'DF' OR CD_UF = 'GO' ORDER BY CD_UF ASC";
        $statement = $this->dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array();

        foreach ($result as $res) {
            $selectData[$res['CD_IBGE']] = utf8_encode($res['DS_MUNICIPIO']);
        }
        return $selectData;
	}

    public function getUfOptionsForSelect()
    {
        $sql = "SELECT distinct CD_UF FROM TB_CIDADE ORDER BY CD_UF ASC";
        $statement = $this->dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array();

        foreach ($result as $res) {
            $selectData[$res['CD_UF']] = utf8_encode($res['CD_UF']);
        }
        return $selectData;
    }

    public function getClienteOrigemOptionsForSelect()
    {
        $sql = 'SELECT CD_ORIGEM,DS_ORIGEM  FROM TB_CLIENTE_ORIGEM ORDER BY DS_ORIGEM ASC';
        $statement = $this->dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array();

        foreach ($result as $res) {
            $selectData[$res['CD_ORIGEM']] = utf8_encode($res['DS_ORIGEM']);
        }
        return $selectData;
    }
    
    public function diasMes()
    {
        $arrDias = array();
        $arrDias[""] = "Selecione...";
        for($i=1; $i<=31; $i++) 
        {
            $l = (strlen($i) < 2) ? '0'.$i : $i;
            $arrDias[$l] = $l;
        }
        return $arrDias;
    }
    
    public function meses()
    {
        $arrMeses = array();
        
        $arrMeses[""] = "Selecione...";
        $arrMeses["01"] = "Janeiro";
        $arrMeses["02"] = "Fevereiro";
        $arrMeses["03"] = "Março";
        $arrMeses["04"] = "Abril";
        $arrMeses["05"] = "Maio";
        $arrMeses["06"] = "Junho";
        $arrMeses["07"] = "Julho";
        $arrMeses["08"] = "Agosto";
        $arrMeses["09"] = "Setembro";
        $arrMeses["10"] = "Outubro";
        $arrMeses["11"] = "Novembro";
        $arrMeses["12"] = "Dezembro";
        
        return $arrMeses;
    }
}
