<?php

namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\Factory as InputFactory;

class TipoPedido implements InputFilterAwareInterface
{

	public $cd_tipo_pedido;
	public $ds_tipo_pedido;
	public $st_pagamento_posterior;

	protected $inputFilter;

	public function getArrayCopy() {
		return get_object_vars($this);
	}

    public function getInputFilter($param = NULL)
    {

    }

	public function setInputFilter(InputFilterInterface $inputFilter)
    {
		throw new \Exception( "Tipo Pedido Exception filter");
	}

	public function exchangeArray($data)
    {
		$this->cd_tipo_pedido = (isset($data["cd_tipo_pedido"])) ? $data["cd_tipo_pedido"] : null;
		$this->ds_tipo_pedido = (isset($data["ds_tipo_pedido"])) ? $data["ds_tipo_pedido"] : null;
		$this->st_pagamento_posterior = (isset($data["st_pagamento_posterior"])) ? $data["st_pagamento_posterior"] : null;
	}
}