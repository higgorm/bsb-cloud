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


/**
 *
 * @author e.Guilherme
 *
 */
class estoqueController extends OrangeWebAbstractActionController
{

    public function getTable($strService) {
        $sm = $this->getServiceLocator();
        $this->table = $sm->get($strService);

        return $this->table;
    }

    public function indexAction(){

        $session = new Container("orangeSessionContainer");

        $loja = array('cd_loja' => $session->cdLoja,
            'ds_loja' => utf8_encode($session->dsLoja));

        $view = new ViewModel(array(
            'loja' => $loja,
            'funcionarios' => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
            'tipos' => $this->getTable("tipo_movimentacao_estoque")->selectAll(),
            'mercadorias' => $this->getTable("mercadoria_table")->listMercadoria()
        ));
        $view->setTemplate("application/estoque/movimentacao.phtml");

        return $view;
    }

    public function pesquisaAction(){

        $request = $this->getRequest();

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

        $movimentacoes = $this->getTable('movimentacao_estoque')->fetchAll($param, $pageNumber);

        $session = new Container("orangeSessionContainer");
        $loja = array('cd_loja' => $session->cdLoja,
            'ds_loja' => utf8_encode($session->dsLoja));
        $view = new ViewModel(array(
            'post' => $post,
            'movimentacoes' => $movimentacoes,
            'loja' => $loja,
            'funcionarios' => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
            'tipos' => $this->getTable("tipo_movimentacao_estoque")->selectAll(),
            'mercadorias' => $this->getTable("mercadoria_table")->listMercadoria()
        ));

        $view->setTerminal($terminal);
        $view->setTemplate("application/estoque/movimentacao.phtml");
        return $view;

    }

    public function cadastrarAction (){
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $request = $this->getRequest();
        $session = new Container("orangeSessionContainer");

        $loja = array('cd_loja' => $session->cdLoja,
            'ds_loja' => utf8_encode($session->dsLoja));

        if ($request->isPost()) {

            $estoque = $this->getTable('estoque')->getEstoqueByMercadoria($_POST['CD_MERCADORIA'], $session->cdLoja);
            $dataAtual = time();

            if($_POST['CD_MOVIMENTO'] >= 1 && $_POST['CD_MOVIMENTO'] <= 9){
                $estoque = $estoque + $_POST['NR_QTDE_MOVIMENTO'];
                $tipoTransferencia = "E";
            }else if($_POST['CD_MOVIMENTO'] >= 10 && $_POST['CD_MOVIMENTO'] <= 18){
                $estoque = $estoque - $_POST['NR_QTDE_MOVIMENTO'];
                $tipoTransferencia = "S";}
            if($_POST['DT_MOVIMENTO']){
                $date = $_POST['DT_MOVIMENTO'];
            }else{
                $date =  $dataAtual;}

            $alt = array(
                'CD_LOJA'               =>  $session->cdLoja,
                'CD_MERCADORIA'         =>  $_POST['CD_MERCADORIA'],
                'NR_SEQ_MOV_ESTOQUE'    =>  $this->getTable('movimentacao_estoque')->getNextId($session->cdLoja),
                'DT_MOVIMENTO'          =>  $date,
                'CD_MOVIMENTO'          =>  $_POST['CD_MOVIMENTO'],
                'DS_MOTIVO'             =>  $_POST['DS_MOTIVO'],
                'NR_DOCUMENTO'          =>  $_POST['NR_DOCUMENTO'],
                'NR_QTDE_MOVIMENTO'     =>  $_POST['NR_QTDE_MOVIMENTO'],
                'NR_QTDE_ESTOQUE'       =>  $estoque,
                'nomUsuario'            =>  $session->usuario,
                'NR_LOTE'               =>  $_POST['NR_LOTE'],
                'DT_VENCIMENTO'         =>  $_POST['DT_VENCIMENTO'],
                'DT_FABRICACAO'         =>  $_POST['DT_FABRICACAO'],
                'DT_ACESSO'             =>  $dataAtual,
                'TipoTransferencia'     =>  $tipoTransferencia,
                'CD_LOJA_TRANSFERENCIA' =>  '0',
                'NaoTemPedido'          =>  true,
                'ST_TIPO_DOCUMENTO'     =>  '10'
            );
            $dbAdapter->getDriver()->getConnection()->beginTransaction();
            $result = $this->getTable('movimentacao_estoque')->save($alt);
            if($result){
                $this->getTable('estoque')->attEstoque($session->cdLoja, $_POST['CD_MERCADORIA'], $estoque);
                $dbAdapter->getDriver()->getConnection()->commit();

                $view = new ViewModel(array(
                    'loja' => $loja,
                    'funcionarios' => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
                    'tipos' => $this->getTable("tipo_movimentacao_estoque")->selectAll(),
                    'mercadorias' => $this->getTable("mercadoria_table")->listMercadoria()
                ));
                $view->setTemplate("application/estoque/movimentacao.phtml");

                return $view;
            }
        }

        $view = new ViewModel(array(
            'loja' => $loja,
            'tipos' => $this->getTable("tipo_movimentacao_estoque")->selectAll(),
            'mercadorias' => $this->getTable("mercadoria_table")->listMercadoria(),
            'form' => 'cadastrar'
           // 'QTDE_estoque' => $this->getTable("estoque")->getEstoqueByMercadoria($movimentacoes->CD_MERCADORIA)
        ));

        $view->setTemplate("application/estoque/form.phtml");

        return $view;
    }

    public function editarAction (){
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $request = $this->getRequest();
        $session = new Container("orangeSessionContainer");

        $loja = array('cd_loja' => $session->cdLoja,
            'ds_loja' => utf8_encode($session->dsLoja));
        try {
            $id = (int) $this->params()->fromQuery('id');

            $movimentacao = $this->getTable("movimentacao_estoque")->selectById($id,$session->cdLoja);

            if($movimentacao){
                foreach($movimentacao as $mov){
                    $movimento = $mov;
                }
            }
            if ($request->isPost()) {

                $dataAtual = time();
                $estoque = $this->getTable('estoque')->getEstoqueByMercadoria($_POST['CD_MERCADORIA'], $session->cdLoja);
                if($_POST['CD_MERCADORIA'] != $movimento['CD_MERCADORIA']){
                    if($_POST['CD_MOVIMENTO'] > 0 && $_POST['CD_MOVIMENTO'] < 10){
                        $estoque = $estoque + $_POST['NR_QTDE_MOVIMENTO'];
                        $tipoTransferencia = "E";
                    }else if($_POST['CD_MOVIMENTO'] > 9 && $_POST['CD_MOVIMENTO'] < 19){
                        $estoque = $estoque - $_POST['NR_QTDE_MOVIMENTO'];
                        $tipoTransferencia = "S";}


                    $alt = array(
                        'CD_LOJA'               =>  $session->cdLoja,
                        'CD_MERCADORIA'         =>  $_POST['CD_MERCADORIA'],
                        'NR_SEQ_MOV_ESTOQUE'    =>  $id,
                        'DT_MOVIMENTO'          =>  $_POST['DT_MOVIMENTO'],
                        'CD_MOVIMENTO'          =>  $_POST['CD_MOVIMENTO'],
                        'DS_MOTIVO'             =>  $_POST['DS_MOTIVO'],
                        'NR_DOCUMENTO'          =>  $_POST['NR_DOCUMENTO'],
                        'NR_QTDE_MOVIMENTO'     =>  $_POST['NR_QTDE_MOVIMENTO'],
                        'NR_QTDE_ESTOQUE'       =>  $estoque,
                        'nomUsuario'            =>  $session->usuario,
                        'NR_LOTE'               =>  $_POST['NR_LOTE'],
                        'DT_VENCIMENTO'         =>  $_POST['DT_VENCIMENTO'],
                        'DT_FABRICACAO'         =>  $_POST['DT_FABRICACAO'],
                        'DT_ACESSO'             =>  $dataAtual,
                        'TipoTransferencia'     =>  $tipoTransferencia,
                        'CD_LOJA_TRANSFERENCIA' =>  '0',
                        'NaoTemPedido'          =>  true,
                        'ST_TIPO_DOCUMENTO'     =>  '10'
                    );

                    $dbAdapter->getDriver()->getConnection()->beginTransaction();

                    $result = $this->getTable('movimentacao_estoque')->save($alt);
                }else{
                    if($_POST['CD_MOVIMENTO'] > 0 && $_POST['CD_MOVIMENTO'] < 10){
                        $estoque = $estoque + $_POST['NR_QTDE_MOVIMENTO'] - $movimento['NR_QTDE_MOVIMENTO'];
                        $tipoTransferencia = "E";
                    }else if($_POST['CD_MOVIMENTO'] > 9 && $_POST['CD_MOVIMENTO'] < 19){
                        $estoque = $estoque - $_POST['NR_QTDE_MOVIMENTO'] + $movimento['NR_QTDE_MOVIMENTO'];
                        $tipoTransferencia = "S";}
                    $alt = array(
                        'CD_LOJA'               =>  $session->cdLoja,
                        'CD_MERCADORIA'         =>  $_POST['CD_MERCADORIA'],
                        'NR_SEQ_MOV_ESTOQUE'    =>  $id,
                        'DT_MOVIMENTO'          =>  $_POST['DT_MOVIMENTO'],
                        'CD_MOVIMENTO'          =>  $_POST['CD_MOVIMENTO'],
                        'DS_MOTIVO'             =>  $_POST['DS_MOTIVO'],
                        'NR_DOCUMENTO'          =>  $_POST['NR_DOCUMENTO'],
                        'NR_QTDE_MOVIMENTO'     =>  $_POST['NR_QTDE_MOVIMENTO'],
                        'NR_QTDE_ESTOQUE'       =>  $estoque,
                        'nomUsuario'            =>  $session->usuario,
                        'NR_LOTE'               =>  $_POST['NR_LOTE'],
                        'DT_VENCIMENTO'         =>  $_POST['DT_VENCIMENTO'],
                        'DT_FABRICACAO'         =>  $_POST['DT_FABRICACAO'],
                        'DT_ACESSO'             =>  $dataAtual,
                        'TipoTransferencia'     =>  $tipoTransferencia,
                        'CD_LOJA_TRANSFERENCIA' =>  '0',
                        'NaoTemPedido'          =>  true,
                        'ST_TIPO_DOCUMENTO'     =>  '10'
                    );

                    $dbAdapter->getDriver()->getConnection()->beginTransaction();

                    $result = $this->getTable('movimentacao_estoque')->change($alt,$session->cdLoja,$id);
                }
                if($result){
                    $this->getTable('estoque')->attEstoque($session->cdLoja, $_POST['CD_MERCADORIA'], $estoque);
                    $this->getTable('movimentacao_estoque')->attEstoque($session->cdLoja, $_POST['CD_MERCADORIA'], $estoque);
                    $dbAdapter->getDriver()->getConnection()->commit();

                    $message = "Altera&ccedil;&atilde;o efetuada com sucesso";
                    $this->flashMessenger()->addMessage($message);

                    $view = new ViewModel(array(
                        'loja' => $loja,
                        'funcionarios' => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
                        'tipos' => $this->getTable("tipo_movimentacao_estoque")->selectAll(),
                        'mercadorias' => $this->getTable("mercadoria_table")->listMercadoria(),
                        'messages' => $message
                    ));
                    $view->setTemplate("application/estoque/movimentacao.phtml");

                    return $view;
                }
            }

            $view = new ViewModel(array(
                'loja' => $loja,
                'funcionarios' => $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja),
                'tipos' => $this->getTable("tipo_movimentacao_estoque")->selectAll(),
                'mercadorias' => $this->getTable("mercadoria_table")->listMercadoria(),
                'mov' => $movimento,
                'form' => 'editar?id='.$movimento['NR_SEQ_MOV_ESTOQUE']
            ));
            $view->setTemplate("application/estoque/form.phtml");

            return $view;

        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }

    public function remover(){
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");

        try {
            $id = (int) $this->params()->fromQuery('id');

        }catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }
}