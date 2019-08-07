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
	public $tp_modelo;
	public $nr_serie;
	public $nr_ano;
	public $nr_faixa_inicial;
	public $nr_faixa_final;
	public $ds_x_motivo;
	public $ds_justificativa;
	public $cd_usuario;
	public $dh_recebimento;
    public $nr_versao;
	public $nr_versao_aplicacao;
	public $cd_uf;
	public $nu_cnpj;
	public $nu_protocolo;
	public $bStat;
	public $cStat;

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
        $this->tp_modelo            = (isset($data["tp_modelo"])) ? $data["tp_modelo"] : null;
        $this->nr_serie             = (isset($data["nr_serie"])) ? $data["nr_serie"] : null;
        $this->nr_ano               = (isset($data["nr_ano"])) ? $data["nr_ano"] : null;
        $this->nr_faixa_inicial     = (isset($data["nr_faixa_inicial"])) ? $data["nr_faixa_inicial"] : null;
        $this->nr_faixa_final       = (isset($data["nr_faixa_final"])) ? $data["nr_faixa_final"] : null;
        $this->ds_x_motivo          = (isset($data["ds_x_motivo"])) ? $data["ds_x_motivo"] : null;
        $this->ds_justificativa     = (isset($data["ds_justificativa"])) ? $data["ds_justificativa"] : null;
        $this->cd_usuario           = (isset($data["cd_usuario"])) ? $data["cd_usuario"] : null;
        $this->dh_recebimento       = (isset($data["dh_recebimento"])) ? $data["dh_recebimento"] : null;
        $this->nr_versao            = (isset($data["nr_versao"])) ? $data["nr_versao"] : null;
        $this->nr_versao_aplicacao  = (isset($data["nr_versao_aplicacao"])) ? $data["nr_versao_aplicacao"] : null;
        $this->cd_uf                = (isset($data["cd_uf"])) ? $data["cd_uf"] : null;
        $this->nu_cnpj              = (isset($data["nu_cnpj"])) ? $data["nu_cnpj"] : null;
        $this->nu_protocolo         = (isset($data["nu_protocolo"])) ? $data["nu_protocolo"] : null;
        $this->bStat                = (isset($data["bStat"])) ? $data["bStat"] : null;
        $this->cStat                = (isset($data["cStat"])) ? $data["cStat"] : null;

	}
}