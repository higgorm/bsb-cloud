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
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model\Contaspagar;


/**
 *
 * @author e.Guilherme
 *
 */
class ContasPagarController extends AbstractActionController
{

    public function getTable($strService) {
        $sm = $this->getServiceLocator();
        $this->table = $sm->get($strService);

        return $this->table;
    }

    public function indexAction()
    {
        $session = new Container("orangeSessionContainer");

        $loja = array('cd_loja' => $session->cdLoja,
            'ds_loja' => utf8_encode($session->dsLoja));

        $viewModel = new ViewModel(array(
            'listaFornecedore' => $this->getTable("fornecedor")->selectAll(),
            'listaPagamento' => $this->getTable("pedido_table")->listaTipoPagamento(),
            'loja' => $loja
        ));

        $viewModel->setTemplate("application/pagar/index.phtml");

        return $viewModel;

    }

    public function pesquisaAction(){

        $request = $this->getRequest();
        $session = new Container("orangeSessionContainer");
        $pageNumber = (int) $this->params()->fromQuery('pg');
        $terminal = $this->params()->fromQuery('modal') == 'show' ? true : false;

        $param = array();

        $loja = array('cd_loja' => $session->cdLoja,
            'ds_loja' => utf8_encode($session->dsLoja));


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

        $contasPagar = $this->getTable('contas_pagar')->fetchAll($param, $pageNumber);

        $view = new ViewModel(array(
            'post' => $post,
            'contasPagar' => $contasPagar,
            'listaFuncionario' => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
            'listaPagamento' => $this->getTable("pedido_table")->listaTipoPagamento(),
            'loja' => $loja
        ));
        $view->setTerminal($terminal);
        $view->setTemplate("application/pagar/index.phtml");
        return $view;

    }

    public function cadastrarAction(){
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
        $loja = array('cd_loja' => $session->cdLoja,
            'ds_loja' => utf8_encode($session->dsLoja));
        $form = "cadastrar";
        $request = $this->getRequest();
        $dataAtual = date('d-m-Y');

        if ($request->isPost()) {

            if(!$_POST['CD_FORNECEDOR']){
                $fornecedor = null;
            }else{
                $fornecedor = $_POST['CD_FORNECEDOR'];
            }
            $id = $this->getTable('contas_pagar')->nextId($session->cdLoja);

            $alt = array(
                'CD_LOJA'                   =>  $session->cdLoja,
                'NR_DOCUMENTO_CP'           =>  $id,
                'CD_CLASSE_FINANCEIRA'      =>  $_POST['CD_CLASSE_FINANCEIRA'],
                'CD_FORNECEDOR'             =>  $fornecedor,
                'DT_MOVIMENTO'              =>  $_POST['DT_MOVIMENTO'],
                'DS_CREDOR'                 =>  $_POST['DS_CREDOR'],
                'CD_BANCO_PAGAMENTO'        =>  $_POST['CD_BANCO_PAGAMENTO'],
                'DS_COMPL_HISTORICO'        =>  $_POST['DS_COMPL_HISTORICO'],
                'DS_DOCUMENTO'              =>  $_POST['DS_DOCUMENTO'],
                'VL_DOCUMENTO'              =>  $_POST['VL_DOCUMENTO'],
                'DS_OBSERVACAO'             =>  $_POST['DS_OBSERVACAO'],
                'ST_AUTOMATICO'             =>  'N',
                'DT_UltimaAlteracao'        =>  $dataAtual,
                'UsuarioUltimaAlteracao'    =>  $session->usuario,
                'CD_CENTRO_CUSTO'           =>  $_POST['CD_CENTRO_CUSTO']
            );

            $dbAdapter->getDriver()->getConnection()->beginTransaction();

            $result = $this->getTable('contas_pagar')->save($alt);

            //Criação ContasPagar_pagamento
            if($_POST['vencimento'] != ''){
                $vencimento = date('d-m-Y', strtotime("-1 month", strtotime($_POST['vencimento'])));
            }else{
                $vencimento = date('d-m-Y', strtotime("-1 month"));
            }
            if($_POST['qtd_parcelas'] >= 1){
                $valor = (float)$_POST['VL_DOCUMENTO']/(float)$_POST['qtd_parcelas'];
                for($i = 1; $i <= $_POST['qtd_parcelas']; $i++ ){
                    $vencimento = date('d-m-Y',strtotime("+1 month", strtotime($vencimento)));
                    $pagamento = array(
                        'CD_LOJA'                   =>  $session->cdLoja,
                        'NR_DOCUMENTO_CP'           =>  $id,
                        'NR_DOCUMENTO_CP_SEQ'       =>  $i,
                        'NR_FATURA'                 =>  '',
                        'NR_BOLETO'                 =>  '',
                        'DT_EMISSAO'                =>  $_POST['DT_MOVIMENTO'],
                        'DT_VENCIMENTO'             =>  $vencimento,
                        'VL_DOCUMENTO'              =>  $valor,
                        'VL_MULTA'                  =>  '0.0000000000',
                        'VL_JUROS'                  =>  '0.0000000000',
                        'VL_DESCONTO'               =>  '0.0000000000',
                        'VL_PAGAMENTO'              =>  '0.0000000000',
                        'CD_CONTA'                  =>  '',
                        'NR_CHEQUE'                 =>  '',
                        'CD_TIPO_PAGAMENTO'         =>  $_POST['CD_TIPO_PAGAMENTO']
                    );
                    $this->getTable('contas_pagar_pagamento')->save($pagamento);
                }
            }
            if($result){

                $dbAdapter->getDriver()->getConnection()->commit();

                $viewModel = new ViewModel(array(
                    'listaFuncionario'  => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
                    'listaPagamento'    => $this->getTable("pedido_table")->listaTipoPagamento(),
                    'listaBancos'       => $this->getTable("banco")->selectAll(),
                    'listaCartao'       => $this->getTable("cartao")->selectAll(),
                    'form'              => $form,
                    'listaClassificacao'=> $this->getTable("classificacao_financeira_table")->listaClassiFinanceira("D"),
                    'listaCentro'       => $this->getTable("centro_custo")->selectAll(),
                    'loja'              => $loja
                ));

                $viewModel->setTemplate("application/pagar/index.phtml");

                return $viewModel;
            }
        }
        $viewModel = new ViewModel(array(
            'listaFuncionario'  => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
            'listaPagamento'    => $this->getTable("pedido_table")->listaTipoPagamento(),
            'listaBancos'       => $this->getTable("banco")->selectAll(),
            'listaCartao'       => $this->getTable("cartao")->selectAll(),
            'form'              => $form,
            'listaClassificacao'=> $this->getTable("classificacao_financeira_table")->listaClassiFinanceira("D"),
            'listaCentro'       => $this->getTable("centro_custo")->selectAll(),
            'loja'              => $loja
        ));

        $viewModel->setTemplate("application/pagar/form.phtml");

        return $viewModel;

    }

    public function editarAction(){

        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
        $loja = array('cd_loja' => $session->cdLoja,
            'ds_loja' => utf8_encode($session->dsLoja));

        $request = $this->getRequest();

        try {
            $id = (int) $this->params()->fromQuery('id');
            $contas = $this->getTable("contas_pagar")->selectById($session->cdLoja,$id);
            if($contas){
                foreach($contas as $conta){
                    $post = $conta;
                }
            }
            $dataAtual = date('d-m-Y');

            if ($request->isPost()) {

                $alt = array(
                    'CD_CLASSE_FINANCEIRA'      =>  $_POST['CD_CLASSE_FINANCEIRA'],
                    'CD_FORNECEDOR'             =>  $_POST['CD_FORNECEDOR'],
                    'DT_MOVIMENTO'              =>  $_POST['DT_MOVIMENTO'],
                    'DS_CREDOR'                 =>  $_POST['DS_CREDOR'],
                    'CD_BANCO_PAGAMENTO'        =>  $_POST['CD_BANCO_PAGAMENTO'],
                    'DS_COMPL_HISTORICO'        =>  $_POST['DS_COMPL_HISTORICO'],
                    'DS_DOCUMENTO'              =>  $_POST['DS_DOCUMENTO'],
                    'VL_DOCUMENTO'              =>  $_POST['VL_DOCUMENTO'],
                    'DS_OBSERVACAO'             =>  $_POST['DS_OBSERVACAO'],
                    'DT_UltimaAlteracao'        =>  $dataAtual,
                    'UsuarioUltimaAlteracao'    =>  $session->usuario,
                    'CD_CENTRO_CUSTO'           =>  $_POST['CD_CENTRO_CUSTO']
                );

                $dbAdapter->getDriver()->getConnection()->beginTransaction();

                $result = $this->getTable('contas_pagar')->change($alt,$session->cdLoja,$id);

                if($_POST['pagamento'] != ""){
                    //Criação ContasPagar_pagamento
                    if($_POST['vencimento'] != ''){
                        $vencimento = date('d-m-Y', strtotime("-1 month", strtotime($_POST['vencimento'])));
                    }else{
                        $vencimento = date('d-m-Y', strtotime("-1 month"));
                    }
                    $valor = (float)$_POST['VL_DOCUMENTO']/(float)$_POST['qtd_parcelas'];
                    for($i = 1; $i <= $_POST['qtd_parcelas']; $i++ ){
                        $vencimento = date('d-m-Y',strtotime("+1 month", strtotime($vencimento)));
                        $pagamento = array(
                            'CD_LOJA'                   =>  $session->cdLoja,
                            'NR_DOCUMENTO_CP'           =>  $id,
                            'NR_DOCUMENTO_CP_SEQ'       =>  $i,
                            'NR_FATURA'                 =>  '',
                            'NR_BOLETO'                 =>  '',
                            'DT_EMISSAO'                =>  $_POST['DT_MOVIMENTO'],
                            'DT_VENCIMENTO'             =>  $vencimento,
                            'VL_DOCUMENTO'              =>  $valor,
                            'VL_MULTA'                  =>  '0.0000000000',
                            'VL_JUROS'                  =>  '0.0000000000',
                            'VL_DESCONTO'               =>  '0.0000000000',
                            'VL_PAGAMENTO'              =>  '0.0000000000',
                            'CD_CONTA'                  =>  '',
                            'NR_CHEQUE'                 =>  '',
                            'CD_TIPO_PAGAMENTO'         =>  $_POST['CD_TIPO_PAGAMENTO']
                        );
                        $this->getTable('contas_pagar_pagamento')->save($pagamento);
                    }
                }
                if($result){
                    $dbAdapter->getDriver()->getConnection()->commit();
                }else{
                    $dbAdapter->getDriver()->getConnection()->rollback();
                }
                $viewModel = new ViewModel(array(
                    'listaFuncionario'  => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
                    'listaPagamento'    => $this->getTable("pedido_table")->listaTipoPagamento(),
                    'listaBancos'       => $this->getTable("banco")->selectAll(),
                    'listaCartao'       => $this->getTable("cartao")->selectAll(),
                    'listaClassificacao'=> $this->getTable("classificacao_financeira_table")->listaClassiFinanceira("D"),
                    'listaCentro'       => $this->getTable("centro_custo")->selectAll(),
                    'post'              => $post,
                    'loja'              => $loja
                ));

                $viewModel->setTemplate("application/pagar/index.phtml");

                return $viewModel;
            }
            $ds_cliente = $this->getTable("cliente_table")->getId($post['CD_CLIENTE']);
            $form = "editar?id=".$id;

            $viewModel = new ViewModel(array(
                'listaFuncionario'  => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
                'listaPagamento'    => $this->getTable("pedido_table")->listaTipoPagamento(),
                'listaBancos'       => $this->getTable("banco")->selectAll(),
                'listaCartao'       => $this->getTable("cartao")->selectAll(),
                'form'              => $form,
                'listaClassificacao'=> $this->getTable("classificacao_financeira_table")->listaClassiFinanceira("D"),
                'listaCentro'       => $this->getTable("centro_custo")->selectAll(),
                'loja'              => $loja,
                'post'              => $post,
                'pg'                => $this->getTable("contas_pagar")->selectQtdParcelas($session->cdLoja,$id),
                'ds_cliente'        => $ds_cliente,
                'parcelas'          => $this->getTable("contas_pagar_pagamento")->selectParcelas($session->cdLoja,$id)
            ));
            $viewModel->setTemplate("application/pagar/form.phtml");

            return $viewModel;
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }

    public function baixaAction(){

        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
        $loja = array('cd_loja' => $session->cdLoja,
            'ds_loja' => utf8_encode($session->dsLoja));
        $request = $this->getRequest();
        $dataAtual = date('d-m-Y');

        try {
            $id = (int) $this->params()->fromQuery('id');
            $form = "editar?id=".$id;

            if ($request->isPost()) {


                $array = array (
                    'DT_PAGAMENTO'      =>  $dataAtual,
                    'CD_CONTA'          =>  $_POST['CD_CONTA'],
                );
            }

            $parcelas = $this->getTable("contas_pagar_pagamento")->selectById($session->cdLoja,$id);
            if($parcelas){
                foreach($parcelas as $parcela){
                    $post = $parcela;
                }
            }
            $viewModel = new ViewModel(array(
                'listaBancos'       => $this->getTable("conta_corrente")->selectAll($session->cdLoja),
                'form'              => $form,
                'post'              => $post
            ));
            $viewModel->setTerminal(true);
            $viewModel->setTemplate("application/pagar/baixa.phtml");

            return $viewModel;

        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }
}