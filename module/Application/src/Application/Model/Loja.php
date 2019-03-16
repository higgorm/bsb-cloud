<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;

use Zend\InputFilter\InputFilterInterface;

use Zend\InputFilter\InputFilterAwareInterface;

use Zend\InputFilter\Factory as InputFactory;

class Loja implements InputFilterAwareInterface
{

	public $cd_loja;
	public $ds_razao_social;
	public $ds_fantasia;
	public $nr_cgc;
	public $ds_contato;
	public $ds_endereco;
	public $ds_bairro;
	public $cd_cidade;
	public $nr_cep;
	public $ds_fone1;
	public $ds_fone2;
	public $nr_insc_estadual;
	public $ds_email;
	public $cd_cliente;
	public $icms_lj_interno;
	public $icms_lj_externo;
	public $cd_centro_custo;
	public $ds_site;
	
	protected $inputFilter;

	public function getArrayCopy() {
		return get_object_vars($this);
	}

	public function setInputFilter(InputFilterInterface $inputFilter){
		throw new \Exception( "Loja Exception filter");
	}
	
	public function exchangeArray($data) {
	
		$this->cd_cliente					= (isset($data["cd_cliente"])) 			 ? $data["cd_cliente"] 			 : null;
		$this->ds_razao_social				= (isset($data["ds_razao_social"])) 	 ? $data["ds_razao_social"] 	 : null;
		$this->ds_fantasia					= (isset($data["ds_fantasia"])) 		 ? $data["ds_fantasia"] 		 : null;
		$this->cd_loja						= (isset($data["cd_loja"])) 			 ? $data["cd_loja"] 			 : null;
		$this->ds_fone1						= (isset($data["ds_fone1"])) 			 ? $data["ds_fone1"] 			 : null;
		$this->ds_fone2						= (isset($data["ds_fone2"])) 			 ? $data["ds_fone2"] 			 : null;
		$this->ds_email						= (isset($data["ds_email"])) 			 ? $data["ds_email"] 			 : null;
		$this->nr_cgc_					    = (isset($data["nr_cgc"])) 			 	 ? $data["nr_cgc"] 			 : null;
		$this->ds_endereco					= (isset($data["ds_endereco"])) 		 ? $data["ds_endereco"] 		 : null;
		$this->ds_bairro					= (isset($data["ds_bairro"])) 			 ? $data["ds_bairro"] 			 : null;
		$this->cd_cidade					= (isset($data["cd_cidade"])) 			 ? $data["cd_cidade"] 			 : null;
		$this->nr_cep						= (isset($data["nr_cep"])) 				 ? $data["nr_cep"] 				 : null;
		$this->ds_contato					= null;
		
	}

	public function getInputFilter(){
		if (!$this->inputFilter) {
			$inputFilter = new InputFilter();

			$factory 	 = new InputFactory();

			$inputFilter->add($factory->createInput(array(
					"name"=>"cd_cliente",
					"required"=>true,
					"filters"=>array(
							array("name"=>"Int"),
					)
			)));

			$inputFilter->add($factory->createInput(array(
					"name"=>"cd_loja",
					"required"=>true,
					"filters"=>array(
							array("name"=>"Int"),
					)
			)));
				
			$inputFilter->add($factory->createInput(array(
					"name"=>"ds_razao_social",
					"required"=>true,
					"filters"=>array(
							array("name"=>"StripTags"),
							array("name"=>"StringTrim"),
					),
					"validators"=> array(
							array (
									'name' => 'NotEmpty',
									'options' => array(
											'messages' => array(
													'isEmpty' => 'Campo obrigatorio',
											)
									),
							),
							array(
									"name"=>"StringLength",
									true,
									"options"=>	array(
											"encoding"=>"UTF-8",
											"min"=>3,
											"max"=>120,
											"message"=>"O nome deve esta entre 3 e 120 caracteres",
									)
							)
					)
			)));

			
			$inputFilter->add($factory->createInput(array(
					'name' => 'ds_email',
					"required"=>false,
					'filters' => array(
							array('name' => 'StripTags'),
							array('name' => 'StringTrim'),
					),
					'validators' => array(
							array (
									'name' => 'EmailAddress',
									'options' => array(
											'messages' => array(
													'emailAddressInvalidFormat' => 'Email  invalido.',
											)
									),
							),
							array (
									'name' => 'NotEmpty',
									'options' => array(
											'messages' => array(
													'isEmpty' => 'Campo obrigatorio',
											)
									),
							),
					),
			)));
			
			$inputFilter->add($factory->createInput(array(
					'name' => 'nr_cgc',
					'required' => false,
					'filters' => array(
							array('name' => 'StripTags'),
							array('name' => 'StringTrim'),
					),
					'validators' => array(
					),
			)));
			
			$inputFilter->add($factory->createInput(array(
					"name"=>"ds_endereco",
					"required"=>false,
					"filters"=>array(
							array("name"=>"StripTags"),
							array("name"=>"StringTrim"),
					),
					"validators"=> array(
							array(
									"name"=>"StringLength",
									true,
									"options"=>	array(
											"encoding"=>"UTF-8",
											"min"=>3,
											"max"=>50,
											"message"=>"O nome do bairro deve esta entre 3 e 50 caracteres",
									)
							)
					)
			)));
			$inputFilter->add($factory->createInput(array(
					"name"=>"ds_bairro",
					"required"=>false,
					"filters"=>array(
							array("name"=>"StripTags"),
							array("name"=>"StringTrim"),
					),
					"validators"=> array(
							array(
									"name"=>"StringLength",
									true,
									"options"=>	array(
											"encoding"=>"UTF-8",
											"min"=>3,
											"max"=>50,
											"message"=>"O nome do bairro deve esta entre 3 e 50 caracteres",
									)
							)
					)
			)));

			$inputFilter->add($factory->createInput(array(
					'name' => 'nr_cep',
					'required' => false,
					'filters' => array(
							array('name' => 'StripTags'),
							array('name' => 'StringTrim'),
					),
					'validators' => array(
					),
			)));
			

			$this->inputFilter = $inputFilter;

		}

		return $this->inputFilter;
	}

}