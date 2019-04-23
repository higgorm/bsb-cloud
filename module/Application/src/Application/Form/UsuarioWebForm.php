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

class UsuarioWebForm extends Form
{

    public $dbAdapter;

    public function __construct($dbAdapter)
    {

        $this->setDbAdapter($dbAdapter);

        parent::__construct("usuario_web_table");

        $this->setAttribute('method', 'post');

        $id = new Hidden("cd_usuario_web");
        $id->setAttributes(array(
            "id" => "cd_usuario_web",
        ));

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

        $dsFone = new Text("nr_telefone");
        $dsFone->setLabel("Telefone")
                ->setAttributes(array(
                    "class" => "form-control",
                    'min' => '8',
                    'max' => '15',
                    "id" => "nr_telefone",
                    "required" => "required",
                    "data-mask" => "(99)99999-9999"
        ));





        $this->add(array(
            'name' => 'cd_loja',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'cd_loja',
                'required' => 'required',
                'value' => '1',
            ),
            'options' => array(
                'label' => 'Origem',
                'value_options' => $this->getLojasOptionsForSelect(),
            ),
        ));


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
        $cancel->setLabel("Cancelar")
                ->setAttributes(array(
                    "type" => "button",
                    "class" => "btn btn-default btn",
                    "onclick" => "javascript:location.href='/usuario-web/cadastrar'",
        ));

        $return = new Button('return');
        $return->setLabel("Retornar a lista")
                ->setAttributes(array(
                    "type" => "button",
                    "class" => "btn btn-default btn",
                    "onclick" => "javascript:history.go(-1);"
        ));


        $this->add($id);
        $this->add($dsNome);
        $this->add($dsUsuario);
        $this->add($dsEmail);
        $this->add($dsFone);
        $this->add($cancel);
        $this->add($return);
        $this->add($submit, array('priority' => -100));
    }

    public function setDbAdapter($dbAdapter)
    {

        $this->dbAdapter = $dbAdapter;
    }

	

    public function getLojasOptionsForSelect()
    {
        $sql = "SELECT distinct CD_LOJA, DS_RAZAOSOCIAL FROM TB_LOJA ORDER BY DS_RAZAOSOCIAL ASC";
        $statement = $this->dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array();

        foreach ($result as $res) {
            $selectData[$res['CD_LOJA']] = utf8_encode($res['CD_LOJA']);
        }
        return $selectData;
    }

    

}
