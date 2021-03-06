<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Application\Model\CartaoTable;
use Application\Model\TipoPagamentoTable;
use Zend\I18n\View\Helper\DateFormat;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model\Cliente;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Application\Model\TabelaTable;
/**
 *
 * @author e.Guilherme
 *
 */
class TabelaController extends OrangeWebAbstractActionController{

    public function formaPagamentoAction(){
		$sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
		$table = new TabelaTable($dbAdapter);
        $messages = $this->flashMessenger()->getMessages();

        $formasPagamento = $table->selectAll_formaPagamento();

        $parametros = array();
        $terminal = false;

        $view = new ViewModel(array(
            "messages" => $messages,
            "formasPagamento" => $formasPagamento,
			"parametros" => $parametros,
        ));

        $view->setTerminal($terminal);
        return $view;
    }

    public function formaPagamentoCadastrarAction()	{
		$sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
		$table = new TabelaTable($dbAdapter);
        $tpPagamentoTable = new TipoPagamentoTable($dbAdapter);
        $request = $this->getRequest();
		$post = $request->getPost();
		$terminal = False;

        if ($request->isPost()) {
			$array = array(
			    'CD_TIPO_PAGAMENTO'     => $tpPagamentoTable->nextId(),
				'DS_TIPO_PAGAMENTO'		=> $post->get('DS_TIPO_PAGAMENTO'),
				'ST_BANCO'			 	=> 'N',
				'ST_AGENCIA'			=> 'N',
				'ST_CONTA'				=> 'N',
				'ST_DOCUMENTO'			=> 'N',
				'ST_CPF_CLIENTE'		=> 'N',
				'ST_DS_CLIENTE'			=> 'N',
				'ST_NR_TELEFONE'		=> 'N',
				'ST_FINANCEIRA'			=> 'N',
				'ST_CARTAO'				=> 'N',
				'ST_EMISSAO'			=> 'N',
				'ST_VENCIMENTO'			=> 'N',
				'ST_PAGAMENTO'			=> 'N',
				'ST_GERA_DOCUMENTO'		=> 'N',
				'NR_DIAS_PAGAMENTO'		=> '0',
				'ST_BOLETO'				=> 'N',
				'ST_CHEQUE'				=> 'N',
				'ST_HABILITADO'			=> 'S',
				'ST_CARTA_CREDITO'		=> 'N',
				'ST_TROCO'				=> 'N'
			);

			$formaPagamento = $table->insere_formaPagamento($array);

            $message = array("success" => utf8_encode("Cadastro realizado com sucesso."));
            $this->flashMessenger()->addMessage($message);
            return $this->redirect()->toUrl("/tabela/forma-pagamento");
        }

		$view = new ViewModel;
		$view->setTerminal($terminal);

		return $view;
	}

    public function formaPagamentoEditarAction(){
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
        $table = new TabelaTable($dbAdapter);
        $request = $this->getRequest();
        $post = $request->getPost();
        $messages = $this->flashMessenger()->getMessages();

        if( $request->isPost() ) {
			$id = $post->get( 'CD_TIPO_PAGAMENTO' );
			$array = array(
				'DS_TIPO_PAGAMENTO'	=> $post->get( 'DS_TIPO_PAGAMENTO' )
			);
			$formaPagamento = $table->atualiza_formaPagamento($id,$array);

            $message = array("success" => utf8_encode("Edi��o realizada com sucesso."));
            $this->flashMessenger()->addMessage($message);
            return $this->redirect()->toUrl("/tabela/forma-pagamento");

        }else{

			$terminal = False;

			$id = (int) $this->params()->fromQuery('id');
			$formaPagamento = $table->getOne('TB_TIPO_PAGAMENTO', 'CD_TIPO_PAGAMENTO = '. $id);

			$parametros = array();
			$terminal = False;

			$view = new ViewModel(array(
				"messages" => $messages,
				"formaPagamento" => $formaPagamento,
				"parametros" => $parametros,
			));

			$view->setTerminal($terminal);
			return $view;
		}
    }

	public function cfopAction(){
		$sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
		$table = new TabelaTable($dbAdapter);
        $messages = $this->flashMessenger()->getMessages();

        $cfop = $table->selectAll_cfop();

        $parametros = array();
        $terminal = false;

        $view = new ViewModel(array(
            "messages" => $messages,
            "cfop" => $cfop,
			"parametros" => $parametros,
        ));

        $view->setTerminal($terminal);
        return $view;
    }

	public function cfopCadastrarAction()	{
		$sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
		$table = new TabelaTable($dbAdapter);
        $request = $this->getRequest();
		$post = $request->getPost();
		$terminal = False;

        if ($request->isPost()) {
			$cfop = $post->get('CD_NATUREZA_OPERACAO');
			if( $cfop < 4000 ){
				$stSaida = 'N';
			}else{
				$stSaida = 'S';
			}
			$array = array(
				'CD_NATUREZA_OPERACAO' 	=> (int)$cfop,
				'DS_NATUREZA_OPERACAO' 	=> utf8_decode($post->get('DS_NATUREZA_OPERACAO')),
				'REGIME_ESPECIAL'		=> 'N',
				'ST_SAIDA'				=> $stSaida,
				'ST_ZERAR_IMPOSTOS'		=> 'N',
				'DS_LOCAL_DESTINO'		=> ''
			);
			$cfop = $table->insere_cfop( $array );
            $message = array("success" => utf8_encode("Cadastro realizado com sucesso."));
			$this->flashMessenger()->addMessage($message);
			return $this->redirect()->toUrl("/tabela/cfop");
        }

		$view = new ViewModel;
		$view->setTerminal($terminal);

		return $view;
	}

	public function cfopEditarAction(){
		$sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
		$table = new TabelaTable($dbAdapter);
        $request = $this->getRequest();
		$post = $request->getPost();
		$terminal = false;

        if( $request->isPost() ) {
			$id = (int)$post->get( 'CD_NATUREZA_OPERACAO' );
			if( $id < 4000 ){
				$stSaida = 'N';
			}else{
				$stSaida = 'S';
			}
			$array = array(
				'DS_NATUREZA_OPERACAO' 	=> utf8_decode($post->get('DS_NATUREZA_OPERACAO')),
				'ST_SAIDA'				=> $stSaida
			);
			$cfop = $table->atualiza_cfop($id,$array);

            $message = array("success" => utf8_encode("Edi��o realizada com sucesso."));
            $this->flashMessenger()->addMessage($message);
            return $this->redirect()->toUrl("/tabela/cfop");
        } else {
			$id = (int) $this->params()->fromQuery('id');
			$cfop = $table->getOne('TB_NATUREZA_OPERACAO', 'CD_NATUREZA_OPERACAO = '. $id);

			$parametros = array();
			$terminal = False;

			$view = new ViewModel(array(
				"messages" => $messages,
				"cfop" => $cfop,
				"parametros" => $parametros,
			));

			$view->setTerminal($terminal);
			return $view;
		}
    }

	public function cartaoAction(){
		$sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
		$table = new TabelaTable($dbAdapter);
        $messages = $this->flashMessenger()->getMessages();

        $cartoes = $table->selectAll_cartao();

        $parametros = array();
        $terminal = False;

        $view = new ViewModel(array(
            "messages" => $messages,
            "cartoes" => $cartoes,
			"parametros" => $parametros,
        ));

        $view->setTerminal($terminal);
        return $view;
    }

	public function cartaoCadastrarAction()	{
		$sm = $this->getServiceLocator();
        $dbAdapter      = $sm->get('Zend\Db\Adapter\Adapter');
        $session        = new Container("orangeSessionContainer");
		$table          = new TabelaTable($dbAdapter);
        $cartaoTable    = new CartaoTable($dbAdapter);
        $request        = $this->getRequest();
		$post           = $request->getPost();
		$terminal       = false;

        if ($request->isPost()) {
			$array = array(
                'CD_CARTAO'					=> $cartaoTable->nextId(),
				'DS_CARTAO'					=> $post->get('DS_CARTAO'),
				'NR_PERCENTUAL_DESCONTO'	=> $post->get('NR_PERCENTUAL_DESCONTO'),
				'NR_DIAS_REEMBOLSO'			=> $post->get('NR_DIAS_REEMBOLSO')
			);

			$cartao = $table->insere_cartao( $array );
            $message = array("success" => utf8_encode("Cadastro realizado com sucesso."));
            $this->flashMessenger()->addMessage($message);
            return $this->redirect()->toUrl("/tabela/cartao");
        }

		$view = new ViewModel;
		$view->setTerminal($terminal);

		return $view;
	}

	public function cartaoEditarAction(){
		$sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
		$table = new TabelaTable($dbAdapter);
        $request = $this->getRequest();
		$post = $request->getPost();
		$terminal = False;

        if( $request->isPost() ) {
			$id = $post->get( 'CD_CARTAO' );
			$array = array(
				'DS_CARTAO'					=> $post->get('DS_CARTAO'),
				'NR_PERCENTUAL_DESCONTO'	=> $post->get('NR_PERCENTUAL_DESCONTO'),
				'NR_DIAS_REEMBOLSO'			=> $post->get('NR_DIAS_REEMBOLSO')
			);
			$cartao = $table->atualiza_cartao($id,$array);
            $message = array("success" => utf8_encode("Edi��o realizada com sucesso."));
            $this->flashMessenger()->addMessage($message);
            return $this->redirect()->toUrl("/tabela/cartao");
        } else {
			$id = (int) $this->params()->fromQuery('id');
			$cartao = $table->getOne('TB_CARTAO', 'CD_CARTAO = '. $id);

			$parametros = array();
			$terminal = False;

			$view = new ViewModel(array(
				"messages" => $messages,
				"cartao" => $cartao,
				"parametros" => $parametros,
			));

			$view->setTerminal($terminal);
			return $view;
		}
    }


}
