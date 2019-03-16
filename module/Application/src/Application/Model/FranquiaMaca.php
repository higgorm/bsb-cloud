<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class FranquiaMaca implements InputFilterAwareInterface {

    public $cd_loja;
    public $nr_maca;
    public $ds_identificacao;
    public $cd_funcionario;
    protected $inputFilter;

    public function exchangeArray($data) {

        $this->cd_loja = (isset($data["cd_loja"])) ? $data["cd_loja"] : null;
        $this->nr_maca = (isset($data["nr_maca"])) ? $data["nr_maca"] : null;
        $this->ds_identificacao = (isset($data["ds_identificacao"])) ? $data["ds_identificacao"] : null;
        $this->cd_funcionario = (isset($data["cd_funcionario"])) ? $data["cd_funcionario"] : null;
    }

    public function getArrayCopy() {
        return get_object_vars($this);
    }

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Franquia Maca Exception filter");
    }

    public function getInputFilter() {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                        "name" => "cd_loja",
                        "required" => true,
                        "filters" => array(
                            array("name" => "Int"),
                        )
            )));

            $inputFilter->add($factory->createInput(array(
                        "name" => "nr_maca",
                        "required" => true,
                        "filters" => array(
                            array("name" => "Int"),
                        )
            )));

            $inputFilter->add($factory->createInput(array(
                        "name" => "cd_funcionario",
                        "required" => false,
                        "filters" => array(
                            array("name" => "Int"),
                        )
            )));

            $inputFilter->add($factory->createInput(array(
                        "name" => "ds_identificacao",
                        "required" => true,
                        "filters" => array(
                            array("name" => "StripTags"),
                            array("name" => "StringTrim"),
                        ),
                        "validators" => array(
                            array(
                                'name' => 'NotEmpty',
                                'options' => array(
                                    'messages' => array(
                                        'isEmpty' => 'Campo obrigatorio',
                                    )
                                ),
                            ),
                            array(
                                "name" => "StringLength",
                                true,
                                "options" => array(
                                    "encoding" => "UTF-8",
                                    "min" => 3,
                                    "max" => 60,
                                    "message" => "O nome deve esta entre 3 e 60 caracteres",
                                )
                            )
                        )
            )));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}
