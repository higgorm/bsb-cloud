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

class ClienteSearchForm extends Form {

    public function __construct($name = null) {

        parent::__construct("cliente");

        $this->setAttribute('method', 'post');

        $dsRazaoSocial = new Text("ds_nome_razao_social");
        $dsRazaoSocial->setLabel("Razão Social")
                ->setAttributes(array(
                    "required" => false,
                    "class" => "form-control",
                    "placeholder" => "Informe a Razão Social",
                    "id" => "ds_nome_razao_social",
        ));

        $ccgCpf = new Text("nr_cgc_cpf");
        $ccgCpf->setLabel("nr_cgc_cpf")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "form-control",
                    "id" => "nr_cgc_cpf",
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
        
        $dsFone1 = new Text("ds_fone1");
        $dsFone1->setLabel("Telefone 1")
                ->setAttributes(array(
                    "class" => "form-control",
                    'min' => '8',
                    'max' => '15',
                    "id" => "ds_fone1",
                    "required" => false
        ));

        $submit = new Button('submit');
        $submit->setLabel("Pesquisar")
                ->setAttributes(array(
                    "type" => "submit",
                    "id" => "btnSubmit",
                    "class" => "btn btn-primary btn",
                    "style" => "margin-left:10%",
        ));

        $clear = new Button('buttonLimpar');
        $clear->setLabel("Limpar")
                ->setAttributes(array(
                    "type" => "button",
                    "class" => "btn btn-default btn",
                    "id" => "btnLimparFormulario",
                    "onclick" => "javascript:location.href='/cliente/index'",
        ));




        $this->add($dsRazaoSocial);
        $this->add($dsEndereco);
        $this->add($ccgCpf);
        $this->add($dsFone1);
        $this->add($submit, array('priority' => -100));
        $this->add($clear);
    }

}
