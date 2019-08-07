<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class Uf implements InputFilterAwareInterface
{

	public $cd_uf;
	public $ds_uf;

	protected $inputFilter;

	public function getArrayCopy() {
		return get_object_vars($this);
	}

    public function getInputFilter($param = NULL)
    {

    }

	public function setInputFilter(InputFilterInterface $inputFilter){
		throw new \Exception( "UF Exception filter");
	}

	public function exchangeArray($data) {

		$this->cd_uf = (isset($data["cd_uf"])) ? $data["cd_uf"] : null;
		$this->ds_uf = (isset($data["ds_uf"])) ? $data["ds_uf"] : null;

	}
}