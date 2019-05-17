<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\I18n\View\Helper\DateFormat;
use Application\Form\ClienteForm;
use Application\Form\ClienteSearchForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model\Cliente;
use Zend\Db\Adapter\Driver\ConnectionInterface;
/**
 *
 * @author HIGOR
 *
 */
class ClienteController extends AbstractActionController
{

    protected $clienteTable;

    public function getTable()
    {
        if (!$this->clienteTable) {
            // get the db adapter
            $sm = $this->getServiceLocator();
            $this->clienteTable = $sm->get("cliente_table");
        }

        return $this->clienteTable;
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

    /**
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::clienteAction()
     */
    public function indexAction()
    {
        $searchForm = new ClienteSearchForm();
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

            $searchForm->setData($post);
        }
		
        $clientes = $this->getTable()->fetchAll($param, $pageNumber);
		$util     = new \Util;

        $view = new ViewModel(array(
            "form" => $searchForm,
            "messages" => $messages,
            "clientes" => $clientes,
			"util"     => $util,
        ));

        $view->setTerminal($terminal);
        return $view;
    }

    public function cadastrarAction()
    {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        try {
            $form = new ClienteForm($dbAdapter);
            $request = $this->getRequest();
            $session = new Container("orangeSessionContainer");
			$terminal = True;

            if ($request->isPost()) {
                $dbAdapter->getDriver()->getConnection()->beginTransaction();
                $model = new Cliente();
                $data = $request->getPost();

                $data["cd_cliente"] = (empty($data["cd_cliente"])) ? $this->getTable()->nextId() : $data["cd_cliente"];
                $form->setInputFilter($model->getInputFilter());
                $form->setData($data);
                $terminal = $data['modal'] == 'show' ? true : false;
				$formAction = '/cliente/cadastrar';

                $model->exchangeArray($data);                
                $model->dt_nascimento = $data['dt_nascimento_d'].'/'.$data['dt_nascimento_m'];
                $this->getTable()->save($model);

                $dbAdapter->getDriver()->getConnection()->commit();

                $message = array("success" => "Cadastro efetuado com sucesso");
                $this->flashMessenger()->addMessage($message);
                return $this->redirect()->toUrl("/cliente/index?pg=1");
            }

            $loja = $this->getTableLoja()->getLojaCidadeUf($session->cdLoja);
            $form->get("cd_uf")->setValue($loja['CD_UF']);
            $form->add(array(
                'name' => 'cd_cidade',
                'type' => 'Zend\Form\Element\Select',
                'attributes' => array(
                    'class' => 'form-control',
                    'id' => 'cd_cidade',
                    'required' => false,
                    'value' => $loja['CD_CIDADE'],
                ),
                'options' => array(
                    'label' => 'Cidade',
                    'value_options' => $this->getTables("cidade_table")->getCidadeByUf($loja['CD_UF']),
                ),
            ));

            $view = new ViewModel(array(
                "form" => $form,
                "formAction" => $formAction,
                "hdServico" => $hdServico
            ));

            $view->setTerminal($terminal);

            $view->setTemplate("application/cliente/form.phtml");

            return $view;
        } catch (\Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }
	
	public function cadastroAction(){ 
		$sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");	
        $request = $this->getRequest();
		$post = $request->getPost();
		$terminal = $this->params()->fromQuery('modal') == 'show' ? true : false;
		
		if ($request->isPost()) {
			try {
				$dbAdapter->getDriver()->getConnection()->beginTransaction();
				$data = $request->getPost();

                $data->usuarioultimaalteracao = $session->usuario;
				$this->getTable()->save($data);
				$dbAdapter->getDriver()->getConnection()->commit();

                $message = array("success" => "Cadastro efetuado com sucesso");
                $this->flashMessenger()->addMessage($message);
                return $this->redirect()->toUrl("/cliente/index?pg=1");
			
			} catch (Exception $e) {
				$dbAdapter->getDriver()->getConnection()->rollback();
			}
		}
		
		$id = (int) $this->params()->fromQuery('id');
		if( $id > 0 ){
			$cliente = $this->getTable()->getId($id);
			$uf      = $this->getServiceLocator()->get('cidade_table')->getUfByCidade($cliente->cd_cidade);
			$cidade  = $this->getServiceLocator()->get('cidade_table')->getCidade($cliente->cd_cidade);
		}else{	
			$cliente = array('');
			$uf      = '';
			$cidade  = '';
		}

		$view = new ViewModel(array(
            "cliente" 	=> $cliente,
            "ufCliente" => $uf,
            "cidade" 	=> $cidade,
		));
		$view->setTerminal($terminal);
		
		return $view;
	}

    public function agendacadastrarAction()
    {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $form = new ClienteForm($dbAdapter);
        $request = $this->getRequest();
        $session = new Container("orangeSessionContainer");

        if ($request->isPost()) {
            $model = new Cliente();
            $data = $request->getPost();

            if (!empty($data['cd_servico'])) {
                foreach ($data['cd_servico'] as $v) {
                    $hdServico .= '<input type="hidden" name="cd_servico[]" value="' . $v . '">';
                }
            }

            $data["cd_cliente"] = $this->getTable()->nextId();
            $form->setInputFilter($model->getInputFilter());
            $form->setData($data);
            $terminal = $data['modal'] == 'show' ? true : false;
            $formAction = '/agenda/' . $data['formAction'];
        }
        
        //format dt_nascimento
        $date = new DateFormat();
        $dtNascimento = new \DateTime($cliente->dt_nascimento, new \DateTimeZone($date->getTimezone()));
        $form->get("dt_nascimento_d")->setValue($dtNascimento->format('d'));
        $form->get("dt_nascimento_m")->setValue($dtNascimento->format('m'));

        $loja = $this->getTableLoja()->getLojaCidadeUf($session->cdLoja);
        $form->get("cd_uf")->setValue($loja['CD_UF']);
        $optionsCid = $this->getTables("cidade_table")->getCidadeByUf($loja['CD_UF']);
        $form->add(array(
            'name' => 'cd_cidade',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'cd_cidade',
                'required' => false,
                'value' => $loja['CD_CIDADE'],
            ),
            'options' => array(
                'label' => 'Cidade',
                'value_options' => $this->getTables("cidade_table")->getCidadeByUf($loja['CD_UF']),
            ),
        ));


        $view = new ViewModel(array(
            "form" => $form,
            "formAction" => $formAction,
            "hdServico" => $hdServico
        ));

        $view->setTerminal($terminal);

        $view->setTemplate("application/cliente/form.phtml");

        return $view;
    }

    public function cadastrarclienterapidoagendamentoAction()
    {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        try {
            $dbAdapter->getDriver()->getConnection()->beginTransaction();
            $form = new ClienteForm($dbAdapter);

            $request = $this->getRequest();

            //if ($request->isPost()) {
            //instance of model
            $model = new Cliente();

            //get Post of request
            $data = $request->getPost();
            $data["cd_cliente"] = $this->getTable()->nextId();

            //set filter of model
            $form->setInputFilter($model->getInputFilter());
            $form->setData($data);

            if ($form->isValid()) {
                $model->exchangeArray($data);
                $this->getTable()->save($model);
            }
            //}
            $param = array();

            $param['cd_servico'] = $data['cd_servico'];
            foreach ($param['cd_servico'] as $mercadoria) {
                $rowResult = $this->getTables("agendamento_franquia_servicos")->recuperaprecoservico($mercadoria);
                $param['total_venda'] = $param['total_venda'] + $rowResult["VL_PRECO_VENDA"];
            }

            $session = new Container("orangeSessionContainer");
            $param = array('cd_loja' => $session->cdLoja,
                'nr_maca' => $this->params()->fromQuery('maca'),
                'dt_horario' => $this->params()->fromQuery('data'),
                'total_venda' => $param['total_venda'],
                'cd_servico' => $param['cd_servico'],
                'cd_cliente' => $this->params()->fromQuery('cd_cliente'),
                'vl_desconto' => $this->params()->fromQuery('vl_desconto'),
                'hratendimento' => date('d/m/Y H:i:s', strtotime(str_replace('/', '-', $this->params()->fromQuery('data') . ' ' . $this->params()->fromQuery('hora'))))
            );

            AgendaController::salvaPedidoAgendamento($param);

            $dbAdapter->getDriver()->getConnection()->commit();

            return $this->redirect()->toUrl("/agenda/index");
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }

    public function editarAction()
    {
        // get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        try {
            $id = (int) $this->params()->fromQuery('id');
            $cliente = $this->getTable()->getId($id);

            $form = new ClienteForm($dbAdapter);
            $form->setBindOnValidate(false);
            $form->bind($cliente);
            $form->get('submit')->setLabel("Alterar");
            
            //format dt_nascimento
            $date = new DateFormat();
            $dtNascimento = new \DateTime($cliente->dt_nascimento, new \DateTimeZone($date->getTimezone()));
            $form->get("dt_nascimento_d")->setValue($dtNascimento->format('d'));
            $form->get("dt_nascimento_m")->setValue($dtNascimento->format('m'));

            //format dt_exclusão
            if(!empty($cliente->dt_exclusao))
            {
            $dtExclusao = new \DateTime($cliente->dt_exclusao, new \DateTimeZone($date->getTimezone()));
            $form->get("dt_exclusao")->setValue($dtExclusao->format('d/m/Y'));
            }
            
            $request = $this->getRequest();

            if ($request->isPost()) {
                //instance of model
                $model = new Cliente();

                //get Post of request
                $data = $request->getPost();

                //set filter of model
                $form->setData($data);

                if ($form->isValid()) {
                    $dbAdapter->getDriver()->getConnection()->beginTransaction();
                    $form->bindValues();

                    $model->exchangeArray($data);
                    $this->getTable()->save($model);

                    $dbAdapter->getDriver()->getConnection()->commit();
                    $message = array("success" => "Altera&ccedil;&atilde;o efetuada com sucesso");
                    $this->flashMessenger()->addMessage($message);
                    return $this->redirect()->toUrl("/cliente/index?pg=1");
                }
            }

            $view = new ViewModel(array(
                "form" => $form,
                "formAction" => '/cliente/cadastrar'
            ));

            $cd_uf = $this->getTables("cidade_table")->getUfByCidade($cliente->cd_cidade);
            $form->get("cd_uf")->setValue($cd_uf);
            $form->add(array(
                'name' => 'cd_cidade',
                'type' => 'Zend\Form\Element\Select',
                'attributes' => array(
                    'class' => 'form-control',
                    'id' => 'cd_cidade',
                    'required' => false,
                    'value' => $cliente->cd_cidade,
                ),
                'options' => array(
                    'label' => 'Cidade',
                    'value_options' => $this->getTables("cidade_table")->getCidadeByUf($cd_uf),
                ),
            ));

            $view->setTemplate("application/cliente/form.phtml");

            return $view;
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }

    public function removerAction()
    {	
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

    public function buscarclienteAction()
    {
        $term = $this->params()->fromQuery('q');
		
        $clientes = $this->getTable()->buscarcliente($term);
        $resultado = array();
        foreach ($clientes as $cliente) {
            $resultado[] = array(
				'CD_CLIENTE' 			=> $cliente['CD_CLIENTE'],
                'DS_NOME_RAZAO_SOCIAL' 	=> $cliente['DS_NOME_RAZAO_SOCIAL'],
                'DS_FONE1' 				=> $cliente['DS_FONE1'],
                'DS_FONE2' 				=> $cliente['DS_FONE2'],
				'NR_CGC_CPF' 			=> $cliente['NR_CGC_CPF']
			);
        }

        $aColumns = array(
			0  => 'DS_NOME_RAZAO_SOCIAL'
            ,1  => 'DS_FONE1'
            ,2  => 'DS_FONE2'
            ,3  => 'CD_CLIENTE'
			,4  => 'NR_CGC_CPF'	
        );

        $sIndexColumn = "CD_CLIENTE";

        $output = array(
            "sEcho" => intval($term),
            "q" => intval($term),
            "iTotalRecords" => sizeof($resultado),
            "iTotalDisplayRecords" => sizeof($resultado),
            "aaData" => array()
        );

        if (is_array($resultado)) {

            foreach ($resultado as $rst) {
                $row = array();

                foreach ($rst as $aRow => $aVal) {
                    if (in_array($aRow, $aColumns)) {
                        if ($aRow == $sIndexColumn) {
                            $row['cdcliente'] = $aVal; //utf8_encode("<input type='hidden' value='$aVal' name='CD_PESSOA_RH[]'/>");
                            $row['id'] = $aVal;
                        } else if ($aRow == "DS_NOME_RAZAO_SOCIAL") {
                            $row['name'] = utf8_encode($aVal); //utf8_encode("<input type='hidden' value='$aVal' name='NM_PESSOA[]'/>$aVal");
                        } else if ($aRow == "DS_FONE1") {
                            $row['fone1'] = $aVal; //utf8_encode("<input type='hidden' value='$aVal' name='NM_PESSOA[]'/>$aVal");
                        } else if ($aRow == "DS_FONE2") {
                            $row['fone2'] = $aVal; //utf8_encode("<input type='hidden' value='$aVal' name='NM_PESSOA[]'/>$aVal");
						} else if ($aRow == 'NR_CGC_CPF') {
							$row['cnpj'] = $aVal; //utf8_encode("<input type='hidden' value='$aVal' name='NM_PESSOA[]'/>$aVal");
						} else {
                            $row[] = ($aVal == "0" || $aVal == "") ? utf8_encode('&nbsp;') : utf8_encode($aVal);
                        }
                    }
                }
                $output['results'][] = $row;
            }
        }
			
        echo json_encode($output);
        exit;
    }

	/**
     *
     */
    public function modalPesquisaClienteAction(){
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true);
        return $viewModel;
    }
	
	/**
     *
     */
    public function recuperaClientePorCodigoAction(){
        
		$viewModel = new ViewModel();
        $viewModel->setTerminal(false);
		$request = $this->getRequest();
		$data = $request->getPost();

        $arrCliente = $this->getTable()->getId($data['cd_cliente']);

        if (count($arrCliente)) {
            array_walk_recursive($arrCliente, function(&$item) { $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1252'); });
            echo json_encode(array('result' => 'success', 'data' => $arrCliente));
        } else {
            echo json_encode(array('result' => 'erro', 'message' => $data['cd_cliente']));
        }

        exit;
    }
	
	public function pesquisaClientePorParamentroAction(){
        @$post = $this->getRequest()->getPost();
        $arrParams = array();
        foreach (@$post as $k => $v) {
            $arrParams[$k] = $v;
        }
        $arrPedido = $this->getTable()->pesquisaClientePedidoPorParametro($arrParams);

        if ($arrPedido) {
            array_walk_recursive($arrPedido, function(&$item) { $item = mb_convert_encoding($item, 'UTF-8', 'Windows-1252'); });
            echo json_encode(array('result' => 'success', 'data' => $arrPedido));

        } else {
            echo json_encode(array('result' => 'erro', 'message' => 'Cliente não encontrado.'));
        }

        exit;
    }
}
