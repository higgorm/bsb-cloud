<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model\TabelaTable;
use Zend\I18n\View\Helper\DateFormat;  
use Zend\Db\Adapter\Driver\ConnectionInterface;

/**
 *
 * @author e.Guilherme
 *
 */
class MercadoriaController extends AbstractActionController{
	
	protected $mercadoriaTable;

    public function getTable()
    {
        if (!$this->mercadoriaTable) {
            // get the db adapter
            $sm = $this->getServiceLocator();
            $this->mercadoriaTable = $sm->get("mercadoria_table");
        }

        return $this->mercadoriaTable;
    }
	
	public function getTableLoja()
    {
        $sm = $this->getServiceLocator();
        return $sm->get("loja_table");
    }

    public function getTables($table)
    {
        $sm = $this->getServiceLocator();
        return $sm->get($table);
    }
	
	 public function indexAction()
    {
        $request = $this->getRequest();
        $messages = $this->flashMessenger()->getMessages();
        $parametros = array();
        $pageNumber = (int) $this->params()->fromQuery('pg');
        $terminal = $this->params()->fromQuery('modal') == 'show' ? true : false;
        $param = array();

        if ($pageNumber == 0) {
            $pageNumber = 1;
        }

        if ($request->isPost()) {
            $post = $request->getPost();

            foreach ($post as $key => $value) {
                if (!empty($value)) {
                    $param[$key] = trim($value);
                }
            }
        }

        $mercadorias = $this->getTable()->fetchAll($param, $pageNumber);
		$util     = new \Util;
        $view = new ViewModel(array(
            "messages" => $messages,
            "mercadorias" => $mercadorias,
			//"precos"    => $precosVenda,
			"util"     => $util,
        ));

        $view->setTerminal($terminal);
        return $view;
    }
	
	public function pesquisarAction(){
		// get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
		
		$request = $this->getRequest();
		$post = $request->getPost();	
		
		$value = $post->get('campo_pesquisa');
		$arrayPesquisa = array(
			'DS_MERCADORIA' => $value
		);
        $mercadorias = $this->getTable()->fetchAll($arrayPesquisa);

        $viewModel = new ViewModel();
		$viewModel->setTemplate('application/mercadoria/index');
        $viewModel->setTerminal(false);
        $viewModel->setVariable('mercadorias', $mercadorias);

        return $viewModel;
	}
		
	
	public function editarAction(){
		$sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
		$tabelaTable = new TabelaTable($dbAdapter);
        $session = new Container("orangeSessionContainer");	
        $request = $this->getRequest();
		$post = $request->getPost();	
		if( $request->isPost() ) {
			$id = $post->get( 'CD_MERCADORIA' );
			$array = array(
				'DS_MERCADORIA'					=> utf8_encode($post->get('MERCADORIA')),
				'CD_UNIDADE_VENDA'				=> $post->get('UNIDADE_VENDA'),
				'DS_CFOP_EXTERNO'				=> $post->get('CFOP_EXTERNO'),
				'DS_CFOP_INTERNO'				=> $post->get('CFOP_INTERNO'),
				'DS_CSOSN'						=> $post->get('CSOSN'),
				'DS_NCM'						=> $post->get('NCM'),
				'NR_PERCENTUAL_ICMS_EXTERNO'	=> $post->get('ICMS_EXTERNO'),
				'NR_PERCENTUAL_ICMS_INTERNO'	=> $post->get('ICMS_INTERNO'),
				'VL_ISS'						=> $post->get('vl_iss'),
				'VL_RET_ISS'					=> $post->get('VL_RET_ISS'),
				'VL_PIS'						=> $post->get('pis'),
				'ISSQN_indISS'					=> $post->get('indIss'),
				'ISSQN_indIncentivo'			=> $post->get('indIncentivo'),
				'cListServ'						=> $post->get('cListServ'),
				'NR_MVA'						=> $post->get('stMva'),
				'PIS_CST'						=> $post->get('stPis'),
				'COFINS_CST'					=> $post->get('stCofins'),
				'IPI_CST'						=> $post->get('ipi_cst'),
				'NR_IPI'						=> $post->get('nr_ipi'),
				'ICMS_Orig'						=> $post->get('icms_orgiem'),
				'ICMS_CST'						=> $post->get('situacaoTributaria'),
				'ICMS_modBC'					=> $post->get('ICMS_modBC'),
				'ST_SERVICO'					=> $post->get('flg_tipo'),
				'DT_UltimaAlteracao'			=> date(FORMATO_ESCRITA_DATA_HORA),
				'UsuarioUltimaAlteracao'		=> 'OrangeWeb'
			);
			
			$mercadoria = $this->getTable()->atualiza_mercadoria($id, $array);
			
			$array = array(
				'VL_PRECO_VENDA'				=> $post->get('vl_venda')
			);
			$preco = $this->getTable()->atualiza_preco($id, $array );
			
			$array = array(
				'VL_PRECO_COMPRA'				=> $post->get('vl_compra'),
				'VL_PRECO_VENDA'				=> $post->get('vl_venda')
			);
			$livro_preco = $this->getTable()->atualiza_livro_preco($id, $array );

			//$viewModel = new ViewModel;
			//$viewModel->setTemplate("application/mercadoria/lista.phtml");

            //return $viewModel;	
			return $this->redirect()->toUrl("/mercadoria/index");
			
		}else{
			$id = (int) $this->params()->fromQuery('id');
			$mercadoria = $this->getTable()->find($id);
			$cfop       = $tabelaTable->selectAll_cfop();
			
			$viewModel = new ViewModel();
			$viewModel->setTerminal(false);
			$viewModel->setVariable('mercadoria', $mercadoria);
			$viewModel->setVariable('cfop', $cfop);
			return $viewModel;
		}
	}
	
	public function cadastrarAction()	{
		$sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
		$tabelaTable = new TabelaTable($dbAdapter);
        $session = new Container("orangeSessionContainer");	
        $request = $this->getRequest();
		$post = $request->getPost();
		$terminal = False;
		if( $post->get('flg_tipo') == 'S' ) {
			$unidade = 'UN';
		}else{
			$unidade = $post->get('UNIDADE_VENDA');
		}

        if ($request->isPost()) {
			$array = array(
				'CD_MERCADORIA'					=> $this->getTable()->getNextId() + 1,
				'DS_MERCADORIA'					=> utf8_encode($post->get('MERCADORIA')),
				'CD_UNIDADE_VENDA'				=> $unidade,
				'DS_CFOP_EXTERNO'				=> $post->get('CFOP_EXTERNO'),
				'DS_CFOP_INTERNO'				=> $post->get('CFOP_INTERNO'),
				'DS_CSOSN'						=> $post->get('CSOSN'),
				'DS_NCM'						=> $post->get('NCM'),
				'CEST'							=> $post->get('CEST'),
				'NR_PERCENTUAL_ICMS_EXTERNO'	=> ( $post->get('ICMS_EXTERNO') == '' ? 0 : $post->get('ICMS_EXTERNO') ),
				'NR_PERCENTUAL_ICMS_INTERNO'	=> ( $post->get('ICMS_INTERNO') == '' ? 0 : $post->get('ICMS_INTERNO') ),
				'VL_ISS'						=> $post->get('vl_iss'),
				'VL_RET_ISS'					=> $post->get('VL_RET_ISS'),
				'VL_PIS'						=> $post->get('pis'),
				'ISSQN_indISS'					=> $post->get('indIss'),
				'ISSQN_indIncentivo'			=> $post->get('indIncentivo'),
				'cListServ'						=> $post->get('cListServ'),
				'NR_MVA'						=> $post->get('stMva'),
				'PIS_CST'						=> $post->get('stPis'),
				'COFINS_CST'					=> $post->get('stCofins'),
				'IPI_CST'						=> $post->get('ipi_cst'),
				'NR_IPI'						=> $post->get('nr_iss'),
				'ICMS_Orig'						=> $post->get('icms_orgiem'),
				'ICMS_CST '		                => $post->get('situacaoTributaria'),
				'ICMS_modBC'					=> $post->get('ICMS_modBC'),
				'ST_SERVICO'					=> $post->get('flg_tipo'),
				'DT_UltimaAlteracao'			=> date(FORMATO_ESCRITA_DATA_HORA),
				'UsuarioUltimaAlteracao'		=> 'OrangeWeb',
				'CD_GRUPO'						=> '01',
				'CD_SUBGRUPO'					=> '01',
				'NR_PESO'						=> '0',
				'CD_TIPO_MERCADORIA'			=> '1',
				'ST_IMPORTADO'					=> 'N',
				'ST_REVENDA'					=> 'S',
				'NR_LASTRO'						=> '0',
				'NR_ALTURA'						=> '0',
				'NR_PERCENTUAL_COMISSAO_INTERNO'=> '0',
				'NR_PERCENTUAL_COMISSAO_EXTERNO'=> '0',
				'IMPRIME_COMPOSICAO'			=> 'P'
			);
			//die(var_dump($array));
			$mercadoria = $this->getTable()->insere( $array );
			
			$array = array(
				'CD_LIVRO'						=> '1',
				'CD_MERCADORIA'					=> $this->getTable()->getNextId(),
				'VL_PRECO_COMPRA'				=> ( $post->get('vl_compra') == '' ? 0 : $post->get('vl_compra') ),
				'VL_PRECO_VENDA'	 			=> $post->get('vl_venda'),
				'ST_FOLHA_ROSTO'				=> '',
				'DT_ALTERACAO'					=> date(FORMATO_ESCRITA_DATA_HORA),
				'DT_UltimaAlteracao'			=> date(FORMATO_ESCRITA_DATA_HORA),
				'UsuarioUltimaAlteracao'		=> 'OrangeWeb'
			);
			$livro_preco = $this->getTable()->insere_livro_preco( $array );
			
			$array = array(
				'CD_LIVRO'						=> '1',
				'CD_MERCADORIA'					=> $this->getTable()->getNextId(),
				'CD_PRAZO'						=> '1',
				'VL_PRECO_VENDA'				=> $post->get('vl_venda'),
				'VL_PRECO_VENDA_PROMOCAO'		=> '0',
				'NR_PERCENTUAL_ACRESCIMO'		=> '0',
				'ST_TIPO_REAJUSTE'				=> '2',
				'DT_UltimaAlteracao'			=> date(FORMATO_ESCRITA_DATA_HORA),
				'UsuarioUltimaAlteracao'		=> 'OrangeWeb'
			);
			$preco = $this->getTable()->insere_preco( $array );
			
			return $this->redirect()->toUrl("/mercadoria/index");
        }
		
		$cfop       = $tabelaTable->selectAll_cfop();
		
		$view = new ViewModel;
		$view->setTemplate('Application\mercadoria\editar');
		$view->setTerminal($terminal);
		$view->setVariable('cfop', $cfop);
		$view->setVariable('mercadoria', array(''));
		
		return $view;			
	}
	
	 /**
     *
     */
    public function modalPesquisaListaMercadoriaAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }
	
	/**
     *
     */
    public function modalPesquisaMercadoriaAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }
	
	/**
     *
     */
    public function recuperaMercadoriaPorCodigoAction(){
        
		$viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        $arrMercadoria = $this->getServiceLocator()->get('mercadoria_table')->getComboPrecoServico(
                $this->getRequest()->getPost()->get('CD_MERCADORIA'));

        if (count($arrMercadoria)) {
            echo json_encode(array('result' => 'success', 'data' => $arrMercadoria));
            exit;
        }

        echo json_encode(array('result' => 'erro', 'message' => 'Código não cadastrado.'));
        exit;
    }
	
	public function pesquisaMercadoriaPorParamentroAction(){
        @$post = $this->getRequest()->getPost();
        $arrParams = array();
        foreach (@$post as $k => $v) {
            $arrParams[$k] = $v;
        }
        $arrPedido = $this->getServiceLocator()->get('mercadoria_table')->pesquisaMercadoriaPorParamentro($arrParams);

        if ($arrPedido) {
            echo json_encode(array('result' => 'success', 'data' => $arrPedido));
            exit;
        }

        echo json_encode(array('result' => 'erro', 'message' => 'Mercadoria não encontrada.'));
        exit;
    }
	
	public function removerAction(){	
		// get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        try {

            $dbAdapter->getDriver()->getConnection()->beginTransaction();
            $id = (int) $this->params()->fromQuery('id');

            if ($this->getTable()->remove($id)) {
                $message = array("success" => "Removido com sucesso");
            } else {
                $message = array("error" => "Não foi possível, este registro está em uso!");
            }

            $dbAdapter->getDriver()->getConnection()->commit();

            $this->flashMessenger()->addMessage($message);
            return $this->redirect()->toUrl("index?pg=1");
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }
}