<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class UsuarioWeb implements InputFilterAwareInterface
{

	public $cd_usuario_web;
	public $cd_loja;
    public $ds_usuario;
    public $ds_senha;
    public $ds_nome;
    public $ds_email;
    public $nr_telefone;
    public $st_ativo;

	protected $inputFilter;

	public function getArrayCopy() {
		return get_object_vars($this);
	}

    public function getInputFilter($param = NULL)
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                "name" => "cd_usuario_web",
                "required" => true,
                "filters" => array(
                    array("name" => "Int"),
                )
            )));

            $inputFilter->add($factory->createInput(array(
                "name" => "ds_nome",
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
                            "max" => 120,
                            "message" => "O nome deve esta entre 3 e 120 caracteres",
                        )
                    )
                )
            )));


            $inputFilter->add($factory->createInput(array(
                'name' => 'ds_email',
                "required" => false,
                'filters' => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'EmailAddress',
                        'options' => array(
                            'messages' => array(
                                'emailAddressInvalidFormat' => 'Email  invalido.',
                            )
                        ),
                    ),
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'messages' => array(
                                'isEmpty' => 'Campo obrigatorio',
                            )
                        ),
                    ),
                ),
            )));


            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

	public function setInputFilter(InputFilterInterface $inputFilter){
		throw new \Exception( "UsuarioWeb Exception filter");
	}

	public function exchangeArray($data) {

		$this->cd_usuario_web = (isset($data["cd_usuario_web"])) ? $data["cd_usuario_web"] : null;
		$this->cd_loja = (isset($data["cd_loja"])) ? $data["cd_loja"] : null;
        $this->ds_usuario = (isset($data["ds_usuario"])) ? $data["ds_usuario"] : null;
        $this->ds_senha = (isset($data["ds_senha"])) ? $data["ds_senha"] : null;
        $this->ds_nome = (isset($data["ds_nome"])) ? $data["ds_nome"] : null;
        $this->ds_email = (isset($data["ds_email"])) ? $data["ds_email"] : null;
        $this->nr_telefone = (isset($data["nr_telefone"])) ? $data["nr_telefone"] : null;
        $this->st_ativo = (isset($data["st_ativo"])) ? $data["st_ativo"] : null;
	}
}