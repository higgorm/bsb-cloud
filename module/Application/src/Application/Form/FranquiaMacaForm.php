<?php

namespace Application\Form;

use Zend\Form\Element\Button;
use Zend\Form\Element\Text;
use Zend\Form\Element\Hidden;
use Zend\Form\Form;
use Zend\Session\Container;

class FranquiaMacaForm extends Form {

    public $dbAdapter;

    public function __construct($dbAdapter) {

        $this->setDbAdapter($dbAdapter);
        $session = new Container("orangeSessionContainer");

        parent::__construct("franquia_maca");

        $this->setAttribute('method', 'post');

        $id = new Hidden("cd_loja");
        $id->setAttributes(array(
            "value" => $session->cdLoja,
            "id" => "cd_loja",
        ));

        $nrCaixa = new Text("nr_maca");
        $nrCaixa->setAttributes(array(
            "class" => "form-control",
            "placeholder" => "Número da Maca",
            "id" => "nr_maca",
        ));

        $dsIdentificacao = new Text("ds_identificacao");
        $dsIdentificacao->setLabel("Identificação")
                ->setAttributes(array(
                    "required" => "required",
                    "class" => "form-control",
                    "placeholder" => "Informe Razão Social",
                    "id" => "ds_nome_razao_social",
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
                    "type" => "reset",
                    "class" => "btn btn-danger btn",
                    "onclick" => "javascript:$('#macaModal').modal('hide');"
        ));

        $return = new Button('return');
        $return->setLabel("Retornar")
                ->setAttributes(array(
                    "type" => "button",
                    "class" => "btn btn-default btn",
                    "onclick" => "javascript:history.go(-1);"
        ));


        $this->add($id);
        $this->add($nrCaixa);
        $this->add($dsIdentificacao);
        $this->add($cancel);
        $this->add($return);
        $this->add($submit, array('priority' => -100));

        $this->add(array(
            'name' => 'cd_funcionario',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'cd_funcionario',
                'required' => false,
                'value' => '',
            ),
            'options' => array(
                'label' => 'Origem',
                'value_options' => $this->getFuncionarioOptionsForSelect(),
            ),
        ));
    }

    public function setDbAdapter($dbAdapter) {
        $this->dbAdapter = $dbAdapter;
    }

    public function getFuncionarioOptionsForSelect() {
        //$sql = 'SELECT CD_FUNCIONARIO,DS_FUNCIONARIO  FROM TB_FUNCIONARIO ORDER BY DS_FUNCIONARIO ASC';
        $sql = 'SELECT tf.CD_FUNCIONARIO,tf.DS_FUNCIONARIO
                FROM  TB_FUNCIONARIO tf
                LEFT JOIN TB_FRANQUIA_MACA tfc on tfc.CD_FUNCIONARIO = tf.CD_FUNCIONARIO
                where tfc.CD_FUNCIONARIO is null
                ORDER BY DS_FUNCIONARIO ASC ';

        $statement = $this->dbAdapter->query($sql);
        $result = $statement->execute();

        $selectData = array();

        $selectData[''] = 'Selecione...';
        foreach ($result as $res) {
            $selectData[$res['CD_FUNCIONARIO']] = utf8_encode($res['DS_FUNCIONARIO']);
        }
        return $selectData;
    }

}
