<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class NotaInutilizada implements InputFilterAwareInterface
{
    public $cd_nfe_inutililizada;
	public $tp_ambiente;
	public $cd_loja;
	public $tp_nfe;
	public $nr_serie;
	public $nr_ano;
	public $nr_faixa_inicial;
	public $nr_faixa_final;
	public $ds_justificativa;
	public $ds_retorno_nfe;
	public $cd_usuario;
	public $dt_registro;

	protected $inputFilter;

	public function getArrayCopy() {
		return get_object_vars($this);
	}

    public function getInputFilter($param = NULL) {

    }

	public function setInputFilter(InputFilterInterface $inputFilter) {
		throw new \Exception(" NotaInutilizada Exception filter ");
	}

	public function exchangeArray($data) {

        $this->cd_nfe_inutililizada = (isset($data["cd_nfe_inutililizada"])) ? $data["cd_nfe_inutililizada"] : null;
        $this->tp_ambiente          = (isset($data["tp_ambiente"])) ? $data["tp_ambiente"] : null;
        $this->cd_loja              = (isset($data["cd_loja"])) ? $data["cd_loja"] : null;
        $this->tp_nfe               = (isset($data["tp_nfe"])) ? $data["tp_nfe"] : null;
        $this->nr_serie             = (isset($data["nr_serie"])) ? $data["nr_serie"] : null;
        $this->nr_ano               = (isset($data["nr_ano"])) ? $data["nr_ano"] : null;
        $this->nr_faixa_inicial     = (isset($data["nr_faixa_inicial"])) ? $data["nr_faixa_inicial"] : null;
        $this->nr_faixa_final       = (isset($data["nr_faixa_final"])) ? $data["nr_faixa_final"] : null;
        $this->ds_justificativa     = (isset($data["ds_justificativa"])) ? $data["ds_justificativa"] : null;
        $this->ds_retorno_nfe       = (isset($data["ds_retorno_nfe"])) ? $data["ds_retorno_nfe"] : null;
        $this->cd_usuario           = (isset($data["cd_usuario"])) ? $data["cd_usuario"] : null;
        $this->dt_registro          = (isset($data["dt_registro"])) ? $data["dt_registro"] : null;

	}
}