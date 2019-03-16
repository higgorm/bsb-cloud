<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;

class Mercadoria implements InputFilterAwareInterface
{

    public $cd_mercadoria;
    public $ds_mercadoria;
    public $inputFilter;

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

        $this->cd_mercadoria = (isset($data["cd_mercadoria"])) ? $data["cd_mercadoria"] : null;
        $this->ds_mercadoria = (isset($data["ds_mercadoria"])) ? $data["ds_mercadoria"] : null;
    }

    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}
