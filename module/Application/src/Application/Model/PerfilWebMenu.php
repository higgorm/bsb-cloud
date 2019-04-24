<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class PerfilWebMenu implements InputFilterAwareInterface
{

	public $cd_perfil_web;
	public $cd_menu;

	protected $inputFilter;

	public function getArrayCopy() {
		return get_object_vars($this);
	}

    public function getInputFilter($param = NULL){

    }

	public function setInputFilter(InputFilterInterface $inputFilter){
		throw new \Exception( "PerfilWeb Exception filter");
	}

	public function exchangeArray($data) {
		$this->cd_perfil_web  = (isset($data["cd_perfil_web"])) ? $data["cd_perfil_web"] : null;
		$this->cd_menu        = (isset($data["cd_menu"])) ? $data["cd_menu"] : null;
	}
}