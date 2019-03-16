<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class ClienteRapido implements InputFilterAwareInterface
{

    public $cd_cliente;
    public $ds_nome;
    public $ds_fone1;
    public $ds_fone2;
    protected $inputFilter;

    public function exchangeArray($data)
    {

        $this->cd_cliente = (isset($data["cd_cliente"])) ? $data["cd_cliente"] : null;
        $this->ds_nome = (isset($data["ds_nome"])) ? $data["ds_nome"] : null;
        $this->ds_fone1 = (isset($data["ds_fone1"])) ? $data["ds_fone1"] : null;
        $this->ds_fone2 = (isset($data["ds_fone2"])) ? $data["ds_fone2"] : null;
    }

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Example Exception filter");
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                        "name" => "cd_cliente",
                        "required" => true,
                        "filters" => array(
                            array("name" => "Int"),
                        )
            )));

            $inputFilter->add($factory->createInput(array(
                        "name" => "ds_nome",
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
                                    "max" => 120,
                                    "message" => "O nome deve esta entre 3 e 120 caracteres",
                                )
                            )
                        )
            )));

            $inputFilter->add($factory->createInput(array(
                        'name' => 'ds_fone1',
                        'required' => false,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                        ),
            )));

            $inputFilter->add($factory->createInput(array(
                        'name' => 'ds_fone2',
                        'required' => false,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                        ),
            )));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}
