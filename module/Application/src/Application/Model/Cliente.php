<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class Cliente implements InputFilterAwareInterface {

    public $cd_cliente;
    public $ds_nome_razao_social;
    public $ds_fantasia;
    public $tp_cliente;
    public $nr_cgc_cpf;
    public $ds_atividade;
    public $ds_contato;
    public $ds_sexo;
    public $ds_endereco;
    public $ds_bairro;
    public $cd_cidade;
    public $nr_cep;
    public $ds_fone1;
    public $ds_fone2;
    public $ds_fone3;
    public $nr_insc_estadual;
    public $ds_identidade;
    public $dt_nascimento;
    public $cd_banco;
    public $cd_agencia;
    public $nr_conta;
    public $ds_email;
    public $st_bloqueio;
    public $dt_bloqueio;
    public $cd_conceito;
    public $cd_praca;
    public $ds_tipo_conta_bancaria;
    public $ds_estado_civil;
    public $st_insc_est_consumidor_final;
    public $ds_identifica_cliente;
    public $st_isento;
    public $dt_exclusao;
    public $rotatividade_compra;
    public $tp_cad_cliente;
    public $nr_carteira_profissional;
    public $serie;
    public $st_compoe_renda;
    public $ds_endereco_anterior;
    public $ds_naturalidade;
    public $ds_nacionalidade;
    public $dt_emissao;
    public $st_tipo_residencia;
    public $dt_ultimaalteracao;
    public $usuarioultimaalteracao;
    public $st_empresadogrupo;
    public $st_mala_direta;
    public $st_criticar_credito;
    public $st_consignado;
    public $nr_dias_faturamento;
    public $nr_desconto_consignado;
    public $st_conceder_desconto_boleto;
    public $nr_desconto_boleto;
    public $ds_codigo_cliente;
    public $cd_origem;
    public $st_cartao_fidelidade_entregue;
    public $dt_retorno;
    public $st_envia_sms;
    public $ds_suframa;
    public $indIE;
    public $ds_numero;
    public $nr_insc_municipal;
    public $ds_complemento;
    protected $inputFilter;

    public function exchangeArray($data) {
        
        $this->cd_cliente = (isset($data["cd_cliente"])) ? $data["cd_cliente"] : null;
        $this->ds_nome_razao_social = (isset($data["ds_nome_razao_social"])) ? $data["ds_nome_razao_social"] : null;
        //$this->ds_nome_razao_social = (isset($data["ds_nome_razao_social_hidden"])) ? $data["ds_nome_razao_social_hidden"] : null;
        $this->ds_fantasia = (isset($data["ds_fantasia"])) ? $data["ds_fantasia"] : null;
        $this->tp_cliente = (isset($data["tp_cliente"])) ? $data["tp_cliente"] : "F";
        $this->ds_fone1 = (isset($data["ds_fone1"])) ? $data["ds_fone1"] : null;
        $this->ds_fone2 = (isset($data["ds_fone2"])) ? $data["ds_fone2"] : null;
        $this->dt_nascimento = (isset($data["dt_nascimento"])) ? $data["dt_nascimento"] : null;
        $this->ds_email = (isset($data["ds_email"])) ? $data["ds_email"] : null;
        $this->cd_origem = (isset($data["cd_origem"])) ? $data["cd_origem"] : null;
        $this->st_cartao_fidelidade_entregue = (isset($data["st_cartao_fidelidade_entregue"])) ? $data["st_cartao_fidelidade_entregue"] : "N";
        $this->nr_cgc_cpf = (isset($data["nr_cgc_cpf"])) ? $data["nr_cgc_cpf"] : null;
        $this->ds_sexo = (isset($data["ds_sexo"])) ? $data["ds_sexo"] : null;
        $this->ds_endereco = (isset($data["ds_endereco"])) ? $data["ds_endereco"] : null;
        $this->ds_bairro = (isset($data["ds_bairro"])) ? $data["ds_bairro"] : null;
        $this->cd_cidade = (isset($data["cd_cidade"])) ? $data["cd_cidade"] : null;
        $this->nr_cep = (isset($data["nr_cep"])) ? $data["nr_cep"] : null;
        $this->dt_ultimaalteracao = (isset($data["dt_ultimaalteracao"])) ? $data["dt_ultimaalteracao"] : null;
        $this->usuarioultimaalteracao = (isset($data["usuarioultimaalteracao"])) ? $data["usuarioultimaalteracao"] : null;
        $this->dt_exclusao = (!empty($data["dt_exclusao"])) ? $data["dt_exclusao"] : null;
        $this->st_envia_sms = (isset($data["st_envia_sms"])) ? (int)$data["st_envia_sms"] : 0;
        $this->dt_retorno = null;
        $this->ds_atividade = null;
        $this->ds_contato = null;
        $this->ds_fone3 = null;
        $this->nr_insc_estadual = (!empty($data["nr_insc_estadual"])) ? $data["nr_insc_estadual"] : null;
        $this->ds_identidade = null;
        $this->cd_banco = null;
        $this->cd_agencia = null;
        $this->nr_conta = null;
        $this->st_bloqueio = null;
        $this->dt_bloqueio = null;
        $this->cd_conceito = null;
        $this->cd_praca = null;
        $this->ds_tipo_conta_bancaria = null;
        $this->ds_estado_civil = null;
        $this->st_insc_est_consumidor_final = null;
        $this->ds_identifica_cliente = null;
        $this->st_isento = null;
        $this->rotatividade_compra = null;
        $this->tp_cad_cliente = null;
        $this->nr_carteira_profissional = null;
        $this->serie = null;
        $this->st_compoe_renda = null;
        $this->ds_endereco_anterior = null;
        $this->ds_naturalidade = null;
        $this->ds_nacionalidade = null;
        $this->dt_emissao = null;
        $this->st_tipo_residencia = null;
        $this->st_empresadogrupo = "N";
        $this->st_mala_direta = "S";
        $this->st_criticar_credito = "N";
        $this->st_consignado = "N";
        $this->nr_dias_faturamento = null;
        $this->nr_desconto_consignado = null;
        $this->st_conceder_desconto_boleto = null;
        $this->nr_desconto_boleto = null;
        $this->ds_codigo_cliente = null;
        $this->dt_retorno = null;
		$this->ds_suframa = (!empty($data["ds_suframa"])) ? $data["ds_suframa"] : null;
		$this->indIE = (!empty($data["indIE"])) ? $data["indIE"] : null;
		$this->ds_numero = (!empty($data["ds_numero"])) ? $data["ds_numero"] : null;
		$this->ds_complemento = (!empty($data["ds_complemento"])) ? $data["ds_complemento"] : null;
		$this->nr_insc_municipal = (!empty($data["nr_insc_municipal"])) ? $data["nr_insc_municipal"] : null;
    }

    public function getArrayCopy() {
        return get_object_vars($this);
    }

    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Example Exception filter");
    }

    public function getInputFilter() {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $factory = new InputFactory();

            $inputFilter->add($factory->createInput(array(
                        "name" => "cd_cliente",
                        "required" => true,
                        "filters" => array(
                            array("name" => "Int"),
                        )
            )));

            $inputFilter->add($factory->createInput(array(
                        "name" => "ds_nome_razao_social",
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

            $inputFilter->add($factory->createInput(array(
                        'name' => 'nr_cgc_cpf',
                        'required' => false,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                        ),
            )));

            $inputFilter->add($factory->createInput(array(
                        "name" => "ds_endereco",
                        "required" => false,
                        "filters" => array(
                            array("name" => "StripTags"),
                            array("name" => "StringTrim"),
                        ),
                        "validators" => array(
                            array(
                                "name" => "StringLength",
                                true,
                                "options" => array(
                                    "encoding" => "UTF-8",
                                    "min" => 3,
                                    "max" => 50,
                                    "message" => "O nome do bairro deve esta entre 3 e 50 caracteres",
                                )
                            )
                        )
            )));
            $inputFilter->add($factory->createInput(array(
                        "name" => "ds_bairro",
                        "required" => false,
                        "filters" => array(
                            array("name" => "StripTags"),
                            array("name" => "StringTrim"),
                        ),
                        "validators" => array(
                            array(
                                "name" => "StringLength",
                                true,
                                "options" => array(
                                    "encoding" => "UTF-8",
                                    "min" => 3,
                                    "max" => 50,
                                    "message" => "O nome do bairro deve esta entre 3 e 50 caracteres",
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

// 			$inputFilter->add($factory->createInput(array(
// 					'name' => 'dt_nascimento',
// 					'required' => 0,
// 					'filters' => array(
// 							array('name' => 'StripTags'),
// 							array('name' => 'StringTrim'),
// 					),
// 					'validators' => array(
// 							array(
// 									'name' => 'Between'
// 							),
// 					),
// 			)));

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

}
