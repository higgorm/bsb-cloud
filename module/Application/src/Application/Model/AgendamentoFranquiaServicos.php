<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class AgendamentoFranquiaServicos implements InputFilterAwareInterface
{
    public $cd_loja;
    public $nr_maca;
    public $dt_horario;
    public $cd_mercadoria;
    protected $inputFilter;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Loja Exception filter");
    }

    public function exchangeArray($data)
    {

        $this->cd_loja = (isset($data["cd_loja"])) ? $data["cd_loja"] : null;
        $this->nr_maca = (isset($data["nr_maca"])) ? $data["nr_maca"] : null;
        $this->dt_horario = (isset($data["dt_horario"])) ? $data["dt_horario"] : null;
        $this->cd_mercadoria = (isset($data["cd_mercadoria"])) ? $data["cd_mercadoria"] : null;
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();


            return $this->inputFilter;
        }
    }

}
