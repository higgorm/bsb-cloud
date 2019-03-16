<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class Cidade implements InputFilterAwareInterface
{

	public $cd_uf;
	public $cd_cidade;
	public $ds_cidade;

	protected $inputFilter;

	public function getArrayCopy() {
		return get_object_vars($this);
	}

    public function getInputFilter($param = NULL)
    {

    }

	public function setInputFilter(InputFilterInterface $inputFilter){
		throw new \Exception( "Cidade Exception filter");
	}

	public function exchangeArray($data) {

		$this->cd_cidade = (isset($data["cd_cidade"])) ? $data["cd_cidade"] : null;
		$this->cd_uf = (isset($data["cd_uf"])) ? $data["cd_uf"] : null;
		$this->ds_cidade = (isset($data["ds_cidade"])) ? $data["ds_cidade"] : null;

	}
}