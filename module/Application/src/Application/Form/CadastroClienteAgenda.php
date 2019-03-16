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

class CadastroClienteAgenda extends Form
{

    public $dbAdapter;

    public function __construct($dbAdapter)
    {

        $this->setDbAdapter($dbAdapter);

        parent::__construct("cliente");

        $this->setAttribute('method', 'post');

        //$id 			= new Hidden("cd_cliente");

        $id = new Text("cd_cliente");
        $id->setLabel("Código")
                ->setAttributes(array(
                    "readonly" => "readonly",
                    "class" => "form-control",
                    "placeholder" => "",
                    "id" => "cd_cliente",
        ));

        $dsRazaoSocial = new Text("ds_nome_razao_social");
        $dsRazaoSocial->setLabel("Nome do Cliente")
                ->setAttributes(array(
                    "required" => true,
                    "class" => "form-control",
                    "placeholder" => "Informe o nome do cliente",
                    "id" => "ds_nome_razao_social",
        ));



        $dsFone1 = new Text("ds_fone1");
        $dsFone1->setLabel("Fone1")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "bfh-phone form-control",
                    'min' => '8',
                    'max' => '15',
                    "id" => "ds_fone1",
                    'required' => 'required',
                    "placeholder" => "(xx) xxxx-xxxx",
                    "data-mask" => "(99) 9999-9999",
        ));

        $dsFone2 = new Text("ds_fone2");
        $dsFone2->setLabel("Fone2")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "form-control",
                    'min' => '8',
                    'max' => '15',
                    "id" => "ds_fone2",
                    "required" => false,
                    "placeholder" => "(xx) xxxx-xxxx",
                    "data-mask" => "(99) 9999-9999",
        ));

        /*$desconto = new Text("vl_desconto");
        $desconto->setLabel("Desconto")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "input-small",
                    "id" => "vl_desconto",
                    "required" => false,
                    "placeholder" => "00,0",
                    "data-mask" => "99,9",
        ));*/

        $dtAniversario = new Text("dt_nascimento");
        $dtAniversario->setLabel("Nascimento")
                ->setAttributes(array(
                    "size" => "16",
                    "class" => "form-control",
                    "id" => "dt_nascimento",
                    'min' => '01-01-1914',
                    'max' => '31-12-2014',
                    'step' => '1',
                    "required" => false,
        ));


        $ccgCpf = new Text("nr_cgc_cpf");
        $ccgCpf->setLabel("nr_cgc_cpf")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "form-control",
                    "id" => "nr_cgc_cpf",
                    'min' => '8',
                    'max' => '9',
                    'step' => '1',
                    'type' => 'number',
                    "required" => false,
        ));

        $dsEndereco = new Text("ds_endereco");
        $dsEndereco->setLabel("Endereço")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "form-control",
                    "id" => "ds_endereco",
                    "required" => false,
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
                    'min' => '8',
                    'max' => '9',
                    'step' => '1',
                    'type' => 'number',
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
                'class' => 'radio',
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
            'name' => 'ds_sexo',
            'type' => 'Zend\Form\Element\Radio',
            'attributes' => array(
                'required' => 'required',
                'value' => 'F',
                'class' => 'radio',
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
            'name' => 'cd_origem',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'cd_origem',
                'required' => true,
                'value' => '1',
            ),
            'options' => array(
                'label' => 'Origem',
                'value_options' => $this->getClienteOrigemOptionsForSelect(),
            ),
        ));

        $this->add(array(
            'name' => 'st_cliente_chegou',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => array(
                'label' => 'Cliente Chegou',
                'id' => 'st_cliente_chegou',
                'use_hidden_element' => true,
                'checked_value' => 'S',
                'unchecked_value' => 'N'
            )
        ));

        $this->add(array(
            'name' => 'list_servico',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'list_servico',
                'required' => true,
                'value' => '1',
            ),
            'options' => array(
                'label' => 'Serviço',
                'value_options' => $this->getServicosOptionsForSelect(),
            ),
        ));

        $this->add(array(
            'name' => 'cd_funcionario',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'cd_funcionario',
                'required' => false,
                'value' => '1',
            ),
            'options' => array(
                'label' => 'Profissional',
                'value_options' => $this->getProfissionalOptionsForSelect(),
            ),
        ));

        $this->add(array(
            'name' => 'ds_email',
            'type' => 'Zend\Form\Element\Email',
            'attributes' => array(
                'class' => 'form-control',
                'placeholder' => 'Email Address...',
                'required' => 'required',
            ),
            'options' => array(
                'label' => 'Email',
            ),
        ));


        $submit = new Button('submit');
        $submit->setLabel("Agendar")
                ->setAttributes(array(
                    "type" => "submit",
                    "class" => "btn btn-primary bntAgendarAtendimento",
                    "onclick" => "javascript:$(form).attr('action', '/agenda/agendamento-cliente').submit();",
        ));
		
				
		$button = new Button('button');
        $button->setLabel("Enviar NF")
                ->setAttributes(array(
                    "type" => "button",
                    "class" => "btn btn-primary bntAgendarAtendimento",
                    "onclick" => "javascript:$(form).attr('action', '/;",
        ));

        $cancel = new Button('atender');
        $cancel->setLabel("Atender")
                ->setAttributes(array(
                    "type" => "button",
                    "class" => "btn btn-success",
                    "onclick" => "javascript:atenderAgendamento('A');"
        ));

        $return = new Button('return');
        $return->setLabel("Cancelar Agendamento")
                ->setAttributes(array(
                    "type" => "button",
                    "class" => "btn btn-default",
                    "onclick" => "javascript:cancelarAgendamento();"
        ));

        $fechar = new Button('fechar');
        $fechar->setLabel("Fechar")
                ->setAttributes(array(
                    "type" => "button",
                    "class" => "btn btn-default",
                    "onclick" => "javascript:fecharTela();"
        ));


        $this->add($id);
        $this->add($dsRazaoSocial);
        $this->add($dsFone1);
        $this->add($dsFone2);
        $this->add($dtAniversario);
        $this->add($dsEndereco);
        $this->add($dsBairro);
        $this->add($nrCep);
        $this->add($dsContato);
        $this->add($ccgCpf);
        $this->add($cancel);
        $this->add($return);
        $this->add($fechar);
        $this->add($submit, array('priority' => -100));
    }

    public function setDbAdapter($dbAdapter)
    {

        $this->dbAdapter = $dbAdapter;
    }

    public function getClienteOrigemOptionsForSelect()
    {
        $sql = 'SELECT CD_ORIGEM,DS_ORIGEM  FROM TB_CLIENTE_ORIGEM ORDER BY DS_ORIGEM ASC';
        $statement = $this->dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array('' => 'Selecione');

        foreach ($result as $res) {
            $selectData[$res['CD_ORIGEM']] = $res['DS_ORIGEM'];
        }
        return $selectData;
    }

    public function getServicosOptionsForSelect($cdLoja = NULL)
    {
        $sql = 'SELECT CD_MERCADORIA, DS_MERCADORIA FROM TB_MERCADORIA WHERE 1=1 ';
        if ($cdLoja) {
            $sql .= " CD_MACA = {$cdLoja}";
        }

        $sql .= " ORDER BY DS_MERCADORIA ";

        $statement = $this->dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array('' => 'Selecione');

        foreach ($result as $res) {
            $selectData[$res['CD_MERCADORIA']] = utf8_encode($res['DS_MERCADORIA']);
        }

        return $selectData;
    }

    public function getProfissionalOptionsForSelect($cdLoja = NULL)
    {
        $sql = 'SELECT CD_FUNCIONARIO, DS_FUNCIONARIO FROM TB_FUNCIONARIO WHERE 1=1 ';
        if ($cdLoja) {
            $sql .= " CD_LOJA = {$cdLoja}";
        }

        $sql .= " ORDER BY DS_FUNCIONARIO ";

        $statement = $this->dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array('' => 'Selecione');

        foreach ($result as $res) {
            $selectData[$res['CD_FUNCIONARIO']] = $res['DS_FUNCIONARIO'];
        }

        return $selectData;
    }

}
