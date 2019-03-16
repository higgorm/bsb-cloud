<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class Cargo implements InputFilterAwareInterface {

    public $cd_cargo;
    public $ds_cargo;
    public $st_motorista;
    public $st_vendedor;
    public $st_telemarketing;
    public $st_gerente;
    public $st_tecnico;
    protected $inputFilter;

    public function getArrayCopy() {
        return get_object_vars($this);
    }

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Cargo Exception filter");
    }

    public function exchangeArray($data) {

        $this->cd_cargo = (isset($data["cd_cargo"])) ? $data["cd_cargo"] : null;
        $this->ds_cargo = (isset($data["ds_cargo"])) ? $data["ds_cargo"] : null;
        $this->st_motorista = (isset($data["st_motorista"])) ? $data["st_motorista"] : null;
        $this->st_vendedor = (isset($data["st_vendedor"])) ? $data["st_vendedor"] : null;
        $this->st_telemarketing = (isset($data["st_telemarketing"])) ? $data["st_telemarketing"] : null;
        $this->st_gerente = (isset($data["st_gerente"])) ? $data["st_gerente"] : null;
        $this->st_tecnico = (isset($data["st_tecnico"])) ? $data["st_tecnico"] : null;
    }

    public function getInputFilter() {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                        "name" => "cd_cargo",
                        "required" => true,
                        "filters" => array(
                            array("name" => "Int"),
                        )
            )));

            $inputFilter->add($factory->createInput(array(
                        "name" => "ds_cargo",
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
                                    "max" => 40,
                                    "message" => "O nome deve esta entre 3 e 40 caracteres",
                                )
                            )
                        )
            )));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}
