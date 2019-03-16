<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class Servicos implements InputFilterAwareInterface
{

    public $cd_loja;
    public $cd_servico;
    public $ds_servico;
    public $vl_servico;
    public $vl_iss;
    public $nr_comissao;
    public $cListServ;
    public $inputFilter;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Servico Exception filter");
    }

    public function exchangeArray($data)
    {

        $this->cd_loja = (isset($data["cd_loja"])) ? $data["cd_loja"] : null;
        $this->cd_servico = (isset($data["cd_servico"])) ? $data["cd_servico"] : null;
        $this->ds_servico = (isset($data["ds_servico"])) ? $data["ds_servico"] : null;
        $this->vl_servico = (isset($data["vl_servico"])) ? $data["vl_servico"] : null;
        $this->vl_iss = (isset($data["vl_iss"])) ? $data["vl_iss"] : null;
        $this->nr_comissao = (isset($data["nr_comissao"])) ? $data["nr_comissao"] : null;
        $this->cListServ = (isset($data["cListServ"])) ? $data["cListServ"] : null;
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                        "name" => "cd_servico",
                        "required" => true,
                        "filters" => array(
                            array("name" => "Int"),
                        )
            )));

            $inputFilter->add($factory->createInput(array(
                        "name" => "ds_servico",
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
                                    "max" => 250,
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
