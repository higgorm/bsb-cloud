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
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Model\ContasReceber;


/**
 *
 * @author e.Guilherme
 */
class ContasReceberController extends OrangeWebAbstractActionController
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

        $contasReceber = $this->getTable('contas_receber_table')->fetchAll(array(), 1);


        $viewModel = new ViewModel(array(
            'contasReceber' => $contasReceber,
            'listaFuncionario' => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
            'listaPagamento' => $this->getTable("pedido_table")->listaTipoPagamento(),
            'loja' => $loja
        ));

        $viewModel->setTemplate("application/receber/index.phtml");

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

        $contasReceber = $this->getTable('contas_receber_table')->fetchAll($param, $pageNumber);


        $view = new ViewModel(array(
            'post' => $post,
            'contasReceber' => $contasReceber,
            'listaFuncionario' => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
            'listaPagamento' => $this->getTable("pedido_table")->listaTipoPagamento(),
            'loja' => $loja
        ));
        $view->setTerminal($terminal);
        $view->setTemplate("application/receber/index.phtml");
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
        $dataAtual = date(FORMATO_ESCRITA_DATA);

        if ($request->isPost()) {

            if(!$_POST['DS_EMISSOR']){
                $dsEmissor = ($_POST['destNome']);
            }else{
                $dsEmissor = ($_POST['DS_EMISSOR']);
            }

            $alt = array(
                'NR_LANCAMENTO_CR'          =>  $this->getTable('contas_receber_table')->nextId($session->cdLoja),
                'DS_COMPL_HISTORICO'        =>  $_POST['DS_COMPL_HISTORICO'],
                'CD_CLASSE_FINANCEIRA'      =>  !empty($_POST['CD_CLASSE_FINANCEIRA']) ? $_POST['CD_CLASSE_FINANCEIRA']  : NULL,
                'NR_DOCUMENTO_CR'           =>  $_POST['NR_DOCUMENTO_CR'],
                'DT_MOVIMENTO'              =>  !empty($_POST['DT_MOVIMENTO']) ?  date(FORMATO_ESCRITA_DATA_HORA, strtotime($_POST['DT_MOVIMENTO']))  : NULL,
                'CD_TIPO_PAGAMENTO'         =>  $_POST['CD_TIPO_PAGAMENTO'],
                'NR_CGC_CPF_EMISSOR'        =>  $_POST['NR_CGC_CPF_EMISSOR'],
                'DS_EMISSOR'                =>  $dsEmissor,
                'NR_FONE_EMISSOR'           =>  $_POST['NR_FONE_EMISSOR'],
                'CD_CLIENTE'                =>  !empty($_POST['codCliente']) ? (int)$_POST['codCliente']  : NULL,
                'CD_CARTAO'                 =>  !empty($_POST['CD_CARTAO']) ? (int)$_POST['CD_CARTAO']  : NULL,
                'CD_BANCO'                  =>  $_POST['CD_BANCO'],
                'CD_AGENCIA'                =>  $_POST['CD_AGENCIA'],
                'NR_CONTA'                  =>  $_POST['NR_CONTA'],
                'NR_CHEQUE'                 =>  $_POST['NR_CHEQUE'],
                'NR_NOTA'                   =>  !empty($_POST['NR_NOTA']) ? (int)$_POST['NR_NOTA']  : NULL,
                'DT_EMISSAO'                =>  !empty($_POST['DT_EMISSAO']) ? date(FORMATO_ESCRITA_DATA_HORA, strtotime($_POST['DT_EMISSAO']))  : NULL,
                'DT_VENCIMENTO'             =>  !empty($_POST['DT_VENCIMENTO']) ? date(FORMATO_ESCRITA_DATA_HORA, strtotime($_POST['DT_VENCIMENTO']))  : NULL,
                'VL_DOCUMENTO'              =>  !empty($_POST['VL_DOCUMENTO']) ? str_ireplace( ',', '.' ,str_ireplace( '.', '' ,$_POST['VL_DOCUMENTO']))  : 0.0,
                'ST_CANCELADO'              =>  'N',
                'CD_LOJA_FUNCIONARIO'       =>  $session->cdLoja,
                'CD_FUNCIONARIO'            =>  !empty($_POST['CD_FUNCIONARIO']) ? (int)$_POST['CD_FUNCIONARIO']  : NULL,
                'CD_LOJA'                   =>  $session->cdLoja,
                'DS_OBSERVACAO'             =>  $_POST['DS_OBSERVACAO'],
                'ST_TipoDocumento_CR'       =>  'C',
                'DT_UltimaAlteracao'        =>  $dataAtual,
                'UsuarioUltimaAlteracao'    =>  $session->usuario,
                'CD_CENTRO_CUSTO'           => !empty($_POST['CD_CENTRO_CUSTO']) ? $_POST['CD_CENTRO_CUSTO']  : NULL,
            );
            $dbAdapter->getDriver()->getConnection()->beginTransaction();

            $result = $this->getTable('contas_receber_table')->save($alt);

            if($result){
                $dbAdapter->getDriver()->getConnection()->commit();

                $viewModel = new ViewModel(array(
                    'listaFuncionario'  => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
                    'listaPagamento'    => $this->getTable("pedido_table")->listaTipoPagamento(),
                    'listaBancos'       => $this->getTable("banco")->selectAll(),
                    'listaCartao'       => $this->getTable("cartao")->selectAll(),
                    'form'              => $form,
                    'listaClassificacao'=> $this->getTable("classificacao_financeira_table")->listaClassiFinanceira("C"),
                    'listaCentro'       => $this->getTable("centro_custo")->selectAll(),
                    'loja'              => $loja
                ));

                $viewModel->setTemplate("application/receber/index.phtml");

                return $viewModel;
            }
        }
        $viewModel = new ViewModel(array(
            'listaFuncionario'  => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
            'listaPagamento'    => $this->getTable("pedido_table")->listaTipoPagamento(),
            'listaBancos'       => $this->getTable("banco")->selectAll(),
            'listaCartao'       => $this->getTable("cartao")->selectAll(),
            'form'              => $form,
            'listaClassificacao'=> $this->getTable("classificacao_financeira_table")->listaClassiFinanceira("C"),
            'listaCentro'       => $this->getTable("centro_custo")->selectAll(),
            'loja'              => $loja
        ));

        $viewModel->setTemplate("application/receber/form.phtml");

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
            $contas = $this->getTable("contas_receber_table")->selectById($id,$session->cdLoja);
            if($contas){
                foreach($contas as $conta){
                    $post = $conta;
                }
            }
            $dataAtual = date('d-m-Y');

            if ($request->isPost()) {

                if(!$_POST['DS_EMISSOR']){
                    $dsEmissor = $_POST['destNome'];
                }else{
                    $dsEmissor = $_POST['DS_EMISSOR'];
                }

                $alt = array(
                    'DS_COMPL_HISTORICO'        =>  $_POST['DS_COMPL_HISTORICO'],
                    'CD_CLASSE_FINANCEIRA'      =>  !empty($_POST['CD_CLASSE_FINANCEIRA']) ? $_POST['CD_CLASSE_FINANCEIRA']  : NULL,
                    'NR_DOCUMENTO_CR'           =>  $_POST['NR_DOCUMENTO_CR'],
                    'DT_MOVIMENTO'              =>  !empty($_POST['DT_MOVIMENTO']) ? date(FORMATO_ESCRITA_DATA_HORA, strtotime($_POST['DT_MOVIMENTO']))  : NULL,
                    'CD_TIPO_PAGAMENTO'         =>  $_POST['CD_TIPO_PAGAMENTO'],
                    'NR_CGC_CPF_EMISSOR'        =>  $_POST['NR_CGC_CPF_EMISSOR'],
                    'DS_EMISSOR'                =>  $dsEmissor,
                    'NR_FONE_EMISSOR'           =>  $_POST['NR_FONE_EMISSOR'],
                    'CD_CLIENTE'                =>  !empty($_POST['codCliente']) ? (int)$_POST['codCliente']  : NULL,
                    'CD_CARTAO'                 =>  !empty($_POST['CD_CARTAO']) ? (int)$_POST['CD_CARTAO']  : NULL,
                    'CD_BANCO'                  =>  $_POST['CD_BANCO'],
                    'CD_AGENCIA'                =>  $_POST['CD_AGENCIA'],
                    'NR_CONTA'                  =>  $_POST['NR_CONTA'],
                    'NR_CHEQUE'                 =>  $_POST['NR_CHEQUE'],
                    'NR_NOTA'                   =>  !empty($_POST['NR_NOTA']) ? (int)$_POST['NR_NOTA']  : NULL,
                    'DT_EMISSAO'                =>  !empty($_POST['DT_EMISSAO']) ? date(FORMATO_ESCRITA_DATA_HORA, strtotime($_POST['DT_EMISSAO']))  : NULL,
                    'DT_VENCIMENTO'             =>  !empty($_POST['DT_VENCIMENTO']) ? date(FORMATO_ESCRITA_DATA_HORA, strtotime($_POST['DT_VENCIMENTO']))  : NULL,
                    'VL_DOCUMENTO'              =>  !empty($_POST['VL_DOCUMENTO']) ? str_ireplace( ',', '.' ,str_ireplace( '.', '' ,$_POST['VL_DOCUMENTO']))  : 0.0,
                    'CD_LOJA_FUNCIONARIO'       =>  $session->cdLoja,
                    'CD_FUNCIONARIO'            =>  !empty($_POST['CD_FUNCIONARIO']) ? (int)$_POST['CD_FUNCIONARIO']  : NULL,
                    'CD_LOJA'                   =>  $session->cdLoja,
                    'DS_OBSERVACAO'             =>  $_POST['DS_OBSERVACAO'],
                    'DT_UltimaAlteracao'        =>  $dataAtual,
                    'UsuarioUltimaAlteracao'    =>  $session->usuario,
                    'CD_CENTRO_CUSTO'           => !empty($_POST['CD_CENTRO_CUSTO']) ? $_POST['CD_CENTRO_CUSTO']  : NULL,
                );


                $dbAdapter->getDriver()->getConnection()->beginTransaction();

                $result = $this->getTable('contas_receber_table')->change($alt,$session->cdLoja,$id);
                if($result){
                    $dbAdapter->getDriver()->getConnection()->commit();
                }else{
                    $dbAdapter->getDriver()->getConnection()->rollback();
                }

                $this->redirect()->toUrl("/contas-receber/index");
            }
            $ds_cliente = $this->getTable("cliente_table")->getId($post['CD_CLIENTE']);
            $form = "editar?id=".$id;

            $viewModel = new ViewModel(array(
                'listaFuncionario'  => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
                'listaPagamento'    => $this->getTable("pedido_table")->listaTipoPagamento(),
                'listaBancos'       => $this->getTable("banco")->selectAll(),
                'listaCartao'       => $this->getTable("cartao")->selectAll(),
                'form'              => $form,
                'listaClassificacao'=> $this->getTable("classificacao_financeira_table")->listaClassiFinanceira("C"),
                'listaCentro'       => $this->getTable("centro_custo")->selectAll(),
                'loja'              => $loja,
                'post'              => $post,
                'ds_cliente'        => $ds_cliente
            ));

            $viewModel->setTemplate("application/receber/form.phtml");

            return $viewModel;
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }

    public function buscarclienteAction()
    {
        $term = $this->params()->fromQuery('q');

        $clientes = $this->getTable('cliente_table')->buscarcliente($term);
        $resultado = array();
        foreach ($clientes as $cliente) {
            $resultado[] = array('CD_CLIENTE' => $cliente['CD_CLIENTE'],
                'DS_NOME_RAZAO_SOCIAL' => $cliente['DS_NOME_RAZAO_SOCIAL'],
                'DS_FONE1' => $cliente['DS_FONE1'],
                'DS_FONE2' => $cliente['DS_FONE2']);
        }

        $aColumns = array(0 => 'DS_NOME_RAZAO_SOCIAL'
        , 1 => 'DS_FONE1'
        , 2 => 'DS_FONE2'
        , 3 => 'CD_CLIENTE'
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
}