<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class AgendamentoFranquia implements InputFilterAwareInterface
{

    public $cd_loja;
    public $nr_maca;
    public $dt_horario;
    public $cd_cliente;
    public $nr_pedido;
    public $st_contatado;
    public $st_cliente_chegou;
    public $cd_cliente_rapido;
    public $cd_funcionario;
    protected $inputFilter;

    public function exchangeArray($data)
    {

        $this->cd_loja = (isset($data["cd_loja"])) ? $data["cd_loja"] : null;
        $this->nr_maca = (isset($data["nr_maca"])) ? $data["nr_maca"] : null;
        $this->dt_horario = (isset($data["dt_horario"])) ? $data["dt_horario"] : null;
        $this->cd_cliente = (isset($data["cd_cliente"])) ? $data["cd_cliente"] : null;
        $this->nr_pedido = (isset($data["nr_pedido"])) ? $data["nr_pedido"] : null;
        $this->st_contatado = (isset($data["st_contatado"])) ? $data["st_contatado"] : null;
        $this->st_cliente_chegou = (isset($data["st_cliente_chegou"])) ? $data["st_cliente_chegou"] : null;
        $this->cd_cliente_rapido = (isset($data["cd_cliente_rapido"])) ? $data["cd_cliente_rapido"] : null;
        $this->cd_funcionario = (isset($data["cd_funcionario"])) ? $data["cd_funcionario"] : null;
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
                        "name" => "cd_cliente",
                        "required" => true,
                        "filters" => array(
                            array("name" => "Int"),
                        )
            )));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}
