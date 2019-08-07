<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class MenuWebResource implements InputFilterAwareInterface
{

	public $cd_menu_web_resource;
	public $ds_menu_resource;
    public $cd_menu;

	protected $inputFilter;

	public function getArrayCopy() {
		return get_object_vars($this);
	}

    public function getInputFilter($param = NULL)
    {

    }

	public function setInputFilter(InputFilterInterface $inputFilter){
		throw new \Exception( "MenuWebResource Exception filter");
	}

	public function exchangeArray($data) {

		$this->cd_menu_web_resource      = (isset($data["cd_menu_web_resource"])) ? $data["cd_menu_web_resource"] : null;
		$this->ds_menu_resource          = (isset($data["ds_menu_resource"])) ? $data["ds_menu_resource"] : null;
        $this->cd_menu                   = (isset($data["cd_menu"])) ? $data["cd_menu"] : null;


	}
}