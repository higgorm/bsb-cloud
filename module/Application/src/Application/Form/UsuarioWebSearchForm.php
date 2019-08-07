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

class UsuarioWebSearchForm extends Form {

    public function __construct($name = null) {

        parent::__construct("usuarioWeb");

        $this->setAttribute('method', 'post');

        $dsNome = new Text("ds_nome");
        $dsNome->setLabel("Nome")
                ->setAttributes(array(
                    "required" => false,
                    "class" => "form-control",
                    "placeholder" => "Informe o nome",
                    "id" => "ds_nome",
                    "required" => false
        ));


        $dsUsuario = new Text("ds_usuario");
        $dsUsuario->setLabel("Usuário")
            ->setAttributes(array(
                "required" => false,
                "class" => "form-control",
                "placeholder" => "Informe o usuario",
                "id" => "ds_usuario",
                "required" => false
            ));

        $dsEmail = new Text("ds_email");
        $dsEmail->setLabel("Email")
                ->setAttributes(array(
                    "style" => "",
                    "class" => "form-control",
                    "id" => "ds_email",
                    "placeholder" => "Informe o e-mail",
                    "required" => false,
        ));

        $this->add($dsNome);
        $this->add($dsUsuario);
        $this->add($dsEmail);
       // $this->add($submit, array('priority' => -100));
       // $this->add($clear);
    }

}
