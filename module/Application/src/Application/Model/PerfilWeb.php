<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class PerfilWeb implements InputFilterAwareInterface
{

	public $cd_perfil_web;
	public $ds_nome;
    public $st_ativo;

	protected $inputFilter;

	public function getArrayCopy() {
		return get_object_vars($this);
	}

    public function getInputFilter($param = NULL)
    {

    }

	public function setInputFilter(InputFilterInterface $inputFilter){
		throw new \Exception( "PerfilWeb Exception filter");
	}

	public function exchangeArray($data) {

		$this->cd_perfil_web  = (isset($data["cd_perfil_web"])) ? $data["cd_perfil_web"] : null;
		$this->ds_nome        = (isset($data["ds_nome"])) ? $data["ds_nome"] : null;
        $this->st_ativo       = (isset($data["st_ativo"])) ? $data["st_ativo"] : 'S';

	}
}