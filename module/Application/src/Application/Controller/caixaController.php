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
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Application\Form\CaixaForm;
use Zend\Db\Adapter\Driver\ConnectionInterface;

/**
 *
 * @author Geovani v1.0
 * @author higor.martins v2.6
 *
 */
class CaixaController extends AbstractActionController {

    protected $table;
    protected $adapter;

    /**
     * @param $strService
     * @return array|object
     */
    public function getTable($strService) {
        $sm = $this->getServiceLocator();
        $this->table = $sm->get($strService);

        return $this->table;
    }

    /**
     * @return \Zend\Db\Adapter\Adapter
     */
    public function getAdapter()
    {
       if (!$this->adapter) {
            $sm = $this->getServiceLocator();
            //$this->adapter = $sm->get('Zend\Db\Adapter\Adapter');
            $config = $sm->get('config');          
            $dbAdapter = new \Zend\Db\Adapter\Adapter($config['db']);
            $this->adapter = $dbAdapter;
       } 
       return $this->adapter;
    }

    /**
     * @return array|ViewModel
     */
    public function indexAction() {
        $session = new Container("orangeSessionContainer");

        $view = new ViewModel(array(
            'listaCaixas' => $this->getTable('caixa_table')->getListaCaixasDisponíveis($session->cdLoja, date('d/m/Y')),
            'cdFuncionario' => $session->cdFuncionario,
            'usuario' => $session->usuario
        ));

        return $view;
    }

    /**
     * @return ViewModel
     */
    public function caixaAction() {
        $session    = new Container("orangeSessionContainer");
        $request    = $this->getRequest();
        $data       = $request->getPost();
        $arrPedidos = $this->getTable('pedido_table')->listaPedidosAtendidos($session->cdLoja, date('d/m/Y'));

        $view = new ViewModel(array(
            'listaPedidos' => $arrPedidos,
            'nrCaixa' => 1,
            'dtCaixa' => date('d/m/Y')
        ));

        return $view;
    }

    /**
     *
     */
    public function validaaberturacaixaAction() {
        $session = new Container("orangeSessionContainer");

        $arrPedidos = $this->getTable('caixa_funcionario_table')->validaAberturaCaixa($session->cdLoja, $this->params()->fromQuery('nrCaixa'), $this->params()->fromQuery('cdFunc'), date('d/m/Y'));
        print json_encode($arrPedidos);
        exit;
    }

    /**
     * @return bool|ViewModel
     */
    public function fechamentocaixaAction() {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');

        try {
            $session = new Container("orangeSessionContainer");
            $request = $this->getRequest();
            $data = array();

            if ($request->isPost()) {
                $dbAdapter->getDriver()->getConnection()->beginTransaction();

                $data = $request->getPost();
                if(!$this->getTable('caixa_funcionario_table')->fechamentoCaixa($session->cdLoja, $data['nr_caixa'], $data['dtCaixa']))
                    throw new Exception;

                $dbAdapter->getDriver()->getConnection()->commit();
                $this->redirect()->toUrl("/caixa/index");
            }

            $view = new ViewModel();

            $view->setTerminal(true);

            if ($data['retorna'] == 'true') {
                return true;
                exit;
            } else {
                return $view;
            }
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
            return false;
        }
    }

    /**
     * @return bool|ViewModel
     */
    public function reaberturacaixaAction() {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        try {
            $session = new Container("orangeSessionContainer");
            $request = $this->getRequest();
            $data = array();

            if ($request->isPost()) {
                $dbAdapter->getDriver()->getConnection()->beginTransaction();
                $data = $request->getPost();
                if(!$this->getTable('caixa_funcionario_table')->reaberturaCaixa($session->cdLoja, $data['nr_caixa'], $data['dtCaixa']))
                    throw new Exception;

                $dbAdapter->getDriver()->getConnection()->commit();
                $this->redirect()->toUrl("/caixa/index");
            }

            $view = new ViewModel();

            $view->setTerminal(true);

            if ($data['retorna'] == 'true') {
                return true;
                exit;
            } else {
                return $view;
            }
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
            return false;
        }
    }

    /**
     * @return ViewModel
     */
    public function caixafuncionarioAction() {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        Try {
            $session = new Container("orangeSessionContainer");
            $request = $this->getRequest();

            if ($request->isPost()) {
                $dbAdapter->getDriver()->getConnection()->beginTransaction();

                $data = $request->getPost();

                $model = array(
                    "cd_loja" => $session->cdLoja,
                    "nr_caixa" => $data['nr_caixa'],
                    "cd_funcionario" => $data['cd_funcionario'],
                    "dt_entrada" => $data['dt_entrada'],
                    "dt_saida" => NULL,
                    "st_atividade" => "L",
                    "dt_hora_entrada" => date('d/m/Y H:i:s'),
                    "dt_hora_saida" => NULL,
                );

                if(!$this->getTable('caixa_funcionario_table')->save($model))
                    throw new \Exception;
                
                $dbAdapter->getDriver()->getConnection()->commit();

                $this->redirect()->toUrl("/caixa/index");
            }

            $arrCaixas = array();
            $arrFuncionarios = $this->getTable("functionario")->getListaFuncionarioLoja($session->cdLoja);

            $view = new ViewModel(array(
                'arrCaixas' => $arrCaixas,
                'listaFuncionario' => $arrFuncionarios
            ));

            $view->setTerminal(true);
            return $view;
        } catch (\Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }

    /**
     *  Alterado para receber apenas um pedido por vez
     *
     * @return ViewModel
     */
    public function recebepedidoAction() {
        $dbAdapter = $this->getAdapter();

        try {
            $data = array();
            $session = new Container("orangeSessionContainer");
            $request = $this->getRequest();
            $objPedido = new \Application\Model\PedidoTable($dbAdapter);

            if ($request->isPost()) {

                $dbAdapter->getDriver()->getConnection()->beginTransaction();
                $objPedido          = new \Application\Model\PedidoTable($dbAdapter);
                $objCaixa           = new \Application\Model\CaixaTable($dbAdapter);
                $objContasReceber   = new \Application\Model\ContasReceberTable($dbAdapter);

                $data = $request->getPost();

                $arrNrPedido  = explode(',', $data['nrPedido']);
                $nrPedido     = $arrNrPedido[0];
                $vlTotalTroco = 0;
                $vlTotalBruto = 0;                

                //Alterado para receber apenas um pedido por vez
                //foreach ($arrNrPedido as $value) {
                    //atualiza pedido
                    $recebe = array('dt_recebimento' => date('d/m/Y'), 'st_pedido' => 'F');
                    if(!$objPedido->recebePedido($recebe, $session->cdLoja, $nrPedido)) {
                        $dbAdapter->getDriver()->getConnection()->rollback();
                        throw new \Exception('Erro ao inserir registros do PEDIDO.');
                    }

                    $cdCliente = $objPedido->getIdClientePedido($nrPedido, $session->cdLoja);
                    $nrLancamentoCaixa = $objCaixa->getNrLancamentoCaixa();
                    $nrCaixa = ($data['nrCaixa'] == "") ? 1 : (int)$data['nrCaixa'];

                    //inserir TB_CAIXA
                    $dadosC = array();
                    $dadosC["CD_LOJA"]                  = $session->cdLoja;
                    $dadosC["NR_LANCAMENTO_CAIXA"]      = $nrLancamentoCaixa;
                    $dadosC["NR_CAIXA"]                 = $nrCaixa;
                    $dadosC["DT_MOVIMENTO"]             = date(FORMATO_ESCRITA_DATA);
                    $dadosC["CD_TIPO_MOVIMENTO_CAIXA"]  = 1;
                    $dadosC["DS_COMPL_MOVIMENTO"]       = 'Pedido de Venda';
                    $dadosC["NR_DOCUMENTO"]             = NULL;
                    $dadosC["VL_TOTAL_BRUTO"]           = 0;
                    $dadosC["VL_TOTAL_LIQUIDO"]         = 0;
                    $dadosC["ST_CANCELADO"]             = 'N';
                    $dadosC["ST_CARREG_INTERNO"]        = '';
                    $dadosC["DS_USUARIO"]               = $session->usuario;
                    $dadosC["CD_CLASSE_FINANCEIRA"]     = NULL;
                    $dadosC["SerialHD"]                 = '';

                    if(!$objCaixa->insereCaixa($dadosC))
                    {
                        $dbAdapter->getDriver()->getConnection()->rollback();
                        throw new \Exception("Erro ao inserir registro do caixa");
                    }

                    //inserir RL_CAIXA_PEDIDO
                    $rlCP                            = array();
                    $rlCP['CD_LOJA']                = $session->cdLoja;
                    $rlCP['NR_CAIXA']               = $nrCaixa;
                    $rlCP['NR_PEDIDO']              = $nrPedido;
                    $rlCP['NR_LANCAMENTO_CAIXA']    = $nrLancamentoCaixa;


                    if(!$objPedido->insereRLCaixaPedido($rlCP)) {
                        $dbAdapter->getDriver()->getConnection()->rollback();
                        throw new \Exception("Erro ao inserir registro em CaixaPedido");
                    }

                    foreach ($data['frPagamento'] as $k => $v) {
                        $nrParcela = $k + 1;

                        $vlTroco       = (float)strtr($data['vlTroco'][$k], array('.' => '', ',' => '.'));
                        $vlDocumento   = (float)strtr($data['vlPagamento'][$k], array('.' => '', ',' => '.')) - $vlTroco ;
                        $vlTotalTroco += $vlTroco;
                        $vlTotalBruto += $vlDocumento ;


                        //salva pedido pagamento
                        $dadosPP = array();
                        $dadosPP['CD_LOJA']             = $session->cdLoja;
                        $dadosPP['NR_PEDIDO']           = $nrPedido;
                        $dadosPP['CD_PLANO_PAGAMENTO']  = $v;
                        $dadosPP['NR_PARCELA']          = $nrParcela;
                        $dadosPP['CD_TIPO_PAGAMENTO']   = $data['tpPagamento'][$k];
                        $dadosPP['NR_PEDIDO_DEVOLUCAO'] = NULL;
                        $dadosPP['NR_CGC_CPF_EMISSOR']  = $data['nrCNPJCPF'][$k];
                        $dadosPP['DS_EMISSOR']          = $data['nmEmissor'][$k];
                        $dadosPP['NR_FONE_EMISSOR']     = $data['tlEmissor'][$k];
                        $dadosPP['CD_CLIENTE']          = $cdCliente;
                        $dadosPP['CD_FINANCEIRA']       = NULL;
                        $dadosPP['NR_BOLETO']           = '';
                        $dadosPP['CD_CARTAO']           = NULL;
                        $dadosPP['CD_BANCO']            = $data['cdBanco'][$k];
                        $dadosPP['CD_AGENCIA']          = $data['nrAgenia'][$k];
                        $dadosPP['NR_CONTA']            = $data['nrConta'][$k];
                        $dadosPP['NR_CHEQUE']           = $data['nrCheque'][$k];
                        $dadosPP['DT_EMISSAO']          = date('d/m/Y H:i:s', strtotime(str_replace('/', '-', $data['dtEmissao'][$k]) . ' 00:00:00'));
                        $dadosPP['DT_VENCIMENTO']       = date('d/m/Y H:i:s', strtotime(str_replace('/', '-', $data['dtVencimento'][$k]) . ' 00:00:00'));
                        //$dadosPP['VL_DOCUMENTO']        = strtr(($data['vlPagamento'][$k] - $data['vlTroco'][$k]), array('.' => '', ',' => '.'));
                        $dadosPP['VL_DOCUMENTO']        = $vlDocumento;
                        $dadosPP['ST_CANCELADO']        = 'N';
                        $dadosPP['VINCULADO']           = -1;
                        $dadosPP['NR_QTDE_PARCELAS']    = 0;
                        $dadosPP['NR_Carta_Credito']    = NULL;
                        $dadosPP['ST_ACORDOP1']         = 0;
                        $dadosPP['vl_troco']            = $vlTroco;
                        //$dadosPP['vl_troco']            = $data['vlTroco'][$k];
                        $dadosPP['NR_BOLETO_PARAMETRO'] = NULL;

                        if(!$objPedido->recebePedidoPagamento($dadosPP))
                        {
                            $dbAdapter->getDriver()->getConnection()->rollback();
                            throw new \Exception("Erro ao inserir registro do pagamento do pedido");
                        }
                        
                        //inserir TB_CAIXA_PAGAMENTO
                        $dadosCP = array();
                        $dadosCP['CD_LOJA']             = $session->cdLoja;
                        $dadosCP['NR_CAIXA']            = $nrCaixa;
                        $dadosCP['NR_LANCAMENTO_CAIXA'] = $nrLancamentoCaixa;
                        $dadosCP['CD_PLANO_PAGAMENTO']  = $v;
                        $dadosCP['NR_PARCELA']          = $nrParcela;
                        $dadosCP['CD_TIPO_PAGAMENTO']   = $data['tpPagamento'][$k];
                        $dadosCP['NR_PEDIDO_DEVOLUCAO'] = NULL;
                        $dadosCP['NR_CGC_CPF_EMISSOR']  = $data['nrCNPJCPF'][$k];
                        $dadosCP['DS_EMISSOR']          = $data['nmEmissor'][$k];
                        $dadosCP['NR_FONE_EMISSOR']     = $data['tlEmissor'][$k];
                        $dadosCP['CD_CLIENTE']          = $cdCliente;
                        $dadosCP['CD_FINANCEIRA']       = NULL;
                        $dadosCP['NR_BOLETO']           = '';
                        $dadosCP['CD_CARTAO']           = NULL;
                        $dadosCP['CD_BANCO']            = $data['cdBanco'][$k];
                        $dadosCP['CD_AGENCIA']          = $data['nrAgenia'][$k];
                        $dadosCP['NR_CONTA']            = $data['nrConta'][$k];
                        $dadosCP['NR_CHEQUE']           = $data['nrCheque'][$k];
                        $dadosCP['DT_EMISSAO']          = $dadosPP['DT_EMISSAO'];
                        $dadosCP['DT_VENCIMENTO']       = $dadosPP['DT_VENCIMENTO'];
                        $dadosCP['VL_DOCUMENTO']        = $dadosPP['VL_DOCUMENTO'];
                        $dadosCP['ST_CANCELADO']        = 'N';
                        $dadosCP['NR_QTDE_PARCELAS']    = 0;
                        $dadosCP['NR_Carta_Credito']    = NULL;
                        $dadosCP['vl_troco']            = $dadosPP['vl_troco'];

                        if(!$objCaixa->insereCaixaPagamento($dadosCP)) {
                            $dbAdapter->getDriver()->getConnection()->rollback();
                            throw new \Exception("Erro ao inserir registro de pagamento no caixa");
                        }


                        //inserir TB_CONTAS A RECEBER
                        $dadosCR = array(
                            'NR_LANCAMENTO_CR'          =>  $this->getTable('contas_receber_table')->nextId($session->cdLoja),
                            'DS_COMPL_HISTORICO'        =>  'Pedido de Venda ',
                            'CD_CLASSE_FINANCEIRA'      =>  $dadosC["CD_CLASSE_FINANCEIRA"] ,
                            'NR_DOCUMENTO_CR'           =>  $nrPedido,
                            'DT_MOVIMENTO'              =>  date(FORMATO_ESCRITA_DATA),
                            'CD_TIPO_PAGAMENTO'         =>  $dadosCP['CD_TIPO_PAGAMENTO'],
                            'NR_CGC_CPF_EMISSOR'        =>  $dadosCP['NR_CGC_CPF_EMISSOR'],
                            'DS_EMISSOR'                =>  $dadosCP['DS_EMISSOR'],
                            'NR_FONE_EMISSOR'           =>  $dadosCP['NR_FONE_EMISSOR'],
                            'CD_CLIENTE'                =>  $cdCliente,
                            'CD_CARTAO'                 =>  $dadosCP['CD_CARTAO'],
                            'CD_BANCO'                  =>  $dadosCP['CD_BANCO'],
                            'CD_AGENCIA'                =>  $dadosCP['CD_AGENCIA'],
                            'NR_CONTA'                  =>  $dadosCP['NR_CONTA'],
                            'NR_CHEQUE'                 =>  $dadosCP['NR_CHEQUE'],
                            'NR_NOTA'                   =>  NULL,
                            'DT_EMISSAO'                =>  $dadosCP['DT_EMISSAO'],
                            'DT_VENCIMENTO'             =>  $dadosCP['DT_VENCIMENTO'],
                            'VL_DOCUMENTO'              =>  $dadosCP['VL_DOCUMENTO'],
                            'ST_CANCELADO'              =>  'N',
                            'CD_LOJA_FUNCIONARIO'       =>  $session->cdLoja,
                            'CD_FUNCIONARIO'            =>  NULL,
                            'CD_LOJA'                   =>  $session->cdLoja,
                            'DS_OBSERVACAO'             =>  NULL,
                            'ST_TipoDocumento_CR'       =>  'C',
                            'DT_UltimaAlteracao'        =>  date(FORMATO_ESCRITA_DATA),
                            'UsuarioUltimaAlteracao'    =>  $session->usuario,
                            'CD_CENTRO_CUSTO'           =>  NULL
                        );

                        if(!$objContasReceber->save($dadosCR)) {
                            $dbAdapter->getDriver()->getConnection()->rollback();
                            throw new \Exception("Erro ao inserir registro de  contas a receber");
                        }
                            
                    }
                //}
                
                if (!$objCaixa->atualizaValoresCaixa($vlTotalBruto, (float)($vlTotalBruto-$vlTotalTroco), $session->cdLoja, $nrLancamentoCaixa, $nrCaixa)) {
                    $dbAdapter->getDriver()->getConnection()->rollback();
                    throw new \Exception("Erro ao atualizar valores do caixa");
                }

                $message = array("success" =>"Pedido ". $nrPedido ." recebido!");
                $this->flashMessenger()->addMessage($message);

                //Commit na transação
                $dbAdapter->getDriver()->getConnection()->commit();

                //Se funcionou tudo, cria uma NFe apartir do pedido, salva e envia
                $this->redirect()->toUrl("/nota/gera-nfe-pedido?nrPedido=".$nrPedido);
            }

            $view = new ViewModel(array(
                'cdCliente'     => $this->params()->fromQuery('cdCliente'),
                'nrPedido'      => $this->params()->fromQuery('nrPedido'),
                'vlPedido'      => $this->params()->fromQuery('vlPedido'),
                'nrCaixa'       => $this->params()->fromQuery('nrCaixa'),
                'listaFormaPagamento'   => $objPedido->listaFormaPagamento(),
                'listaTipoPagamento'    => $objPedido->listaTipoPagamento(),
                'listaCartao'           => $objPedido->listaCartao(),
                'listaBanco'            => $objPedido->listaBanco()
            ));

            $view->setTerminal(true);
            return $view;
        } catch (\Exception $e) {            
            $dbAdapter->getDriver()->getConnection()->rollback();
            $message = array("danger" =>$e->getMessage());
            $this->flashMessenger()->addMessage($message);
            $this->redirect()->toUrl("/caixa/caixa");
        }
    }

    /**
     * @return ViewModel
     */
    public function movimentacaocaixaAction()
    {                               
        $view = new ViewModel(array('nrCaixa' => $this->params()->fromQuery('nrCaixa')));
        $view->setTerminal(true);
        return $view;
    }

    /**
     *
     */
    public function gravamovimentacaocaixaAction()
    {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        try {
            $session = new Container("orangeSessionContainer");
            $request = $this->getRequest();
            $data = array();
            $retorna = 'false';
            
            if ($request->isPost()) {
                $dbAdapter->getDriver()->getConnection()->beginTransaction();
                $data = $request->getPost();
                $retorna = $data['retorna'];

                $nrLancamentoCaixa = $this->getTable("caixa_table")->getNrLancamentoCaixa();                                   

                //inserir TB_CAIXA
                $dadosC = array();
                $dadosC["CD_LOJA"] = $session->cdLoja;
                $dadosC["NR_LANCAMENTO_CAIXA"] = $nrLancamentoCaixa;
                $dadosC["NR_CAIXA"] = $data['nrCaixa'];
                $dadosC["DT_MOVIMENTO"] = date('d/m/Y H:i:s', strtotime(str_replace('/', '-', $data['dtCaixa']) . ' 00:00:00'));
                $dadosC["CD_TIPO_MOVIMENTO_CAIXA"] = $data['cdTipoMovimentacaoCaixa'];
                $dadosC["DS_COMPL_MOVIMENTO"] = $data['txtComplemento'];
                $dadosC["NR_DOCUMENTO"] = (!empty($data['nrDocumento'])) ? $data['nrDocumento'] : NULL;
                $dadosC["VL_TOTAL_BRUTO"] = strtr($data['vlMovimento'], array('.' => '', ',' => '.'));
                $dadosC["VL_TOTAL_LIQUIDO"] = strtr($data['vlMovimento'], array('.' => '', ',' => '.'));
                $dadosC["ST_CANCELADO"] = 'N';
                $dadosC["ST_CARREG_INTERNO"] = '';
                $dadosC["DS_USUARIO"] = $session->usuario;
                $dadosC["CD_CLASSE_FINANCEIRA"] = $data['cdClasseFinanceira'];
                $dadosC["SerialHD"] = '';
                if(!$this->getTable("caixa_table")->insereCaixa($dadosC))
                {
                    $dbAdapter->getDriver()->getConnection()->rollback();
                    throw new Exception;
                }                

                $dbAdapter->getDriver()->getConnection()->commit();
                print json_encode(array('success'=>true,'nrLancamentoCaixa'=>$nrLancamentoCaixa));
                exit;
            }
        } catch (Exception $ex) {
            $dbAdapter->getDriver()->getConnection()->rollback();
            print json_encode(array('success'=>false));
            exit;
        }
    }

    /**
     *
     */
    public function pesquisamovimentacaocaixaAction()
    {
        $session = new Container("orangeSessionContainer");
        $request = $this->getRequest();
        $data = array();
        $listaresultado = array();
        
        if ($request->isPost()) {            
            $data = $request->getPost();            
            $listaresultado = $this->getTable("caixa_table")->pesquisaMovimentacaoCaixa($session->cdLoja, $data['nrCaixa'], $data['dtCaixa'], 
                                                                                        $data['cdPesquisaPor'], $data['txtProcurar']);
        }
        
        $c = (count($listaresultado) > 0) ? true : false;
        
        print json_encode(array('success'=>$c, 'result' => $listaresultado));
        exit;
    }
    
}
