<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class MenuWeb implements InputFilterAwareInterface
{

	public $cd_menu;
	public $ds_menu;
    public $cd_menu_pai;
    public $ds_url;
    public $ds_icone;
    public $st_ativo;

	protected $inputFilter;

	public function getArrayCopy() {
		return get_object_vars($this);
	}

    public function getInputFilter($param = NULL)
    {

    }

	public function setInputFilter(InputFilterInterface $inputFilter){
		throw new \Exception( "MenuWeb Exception filter");
	}

	public function exchangeArray($data) {

		$this->cd_menu      = (isset($data["cd_menu"])) ? $data["cd_menu"] : null;
		$this->ds_menu      = (isset($data["ds_menu"])) ? $data["ds_menu"] : null;
        $this->cd_menu_pai  = (isset($data["cd_menu_pai"])) ? $data["cd_menu_pai"] : null;
        $this->ds_url      = (isset($data["ds_url"])) ? $data["ds_url"] : null;
        $this->ds_icone      = (isset($data["ds_icone"])) ? $data["ds_icone"] : null;
        $this->st_ativo     = (isset($data["st_ativo"])) ? $data["st_ativo"] : 'S';

	}
}