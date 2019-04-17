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
use Application\Form\CadastroClienteAgenda;
use Application\Model\ClienteRapido;
use Application\Model\AgendamentoFranquia;
use Application\Model\Cliente;
use Application\Model\AgendamentoFranquiaServicos;
use Zend\Db\Adapter\Driver\ConnectionInterface;

/**
 *
 * @author HIGOR
 *
 */
class AgendaController extends AbstractActionController {

    protected $macaTable;
    protected $agendamentoFranquiaTable;
    protected $_intervalo = 30;
    protected $_horaInicio = '08';
    protected $_minutoInicio = '00';
    protected $_horaFim = '18';
    protected $_minutoFim = '00';

    public function getMacaTable($strService) {
        $sm = $this->getServiceLocator();
        $this->macaTable = $sm->get($strService);

        return $this->macaTable;
    }

    private function diassemana($diasemana) {
        switch ($diasemana) {
            case"0": return "Domingo";
                break;
            case"1": return "Segunda";
                break;
            case"2": return "Ter&ccedil;a";
                break;
            case"3": return "Quarta";
                break;
            case"4": return "Quinta";
                break;
            case"5": return "Sexta";
                break;
            case"6": return "S&aacute;bado";
                break;
        }
    }

    private function intervalosemana($diasemana) {
        $thisday = substr($diasemana, 0, 2);
        $thismonth = substr($diasemana, 4, 5);
        $thisyear = substr($diasemana, 6, 10);

        $indiceDia = date('w', mktime(0, 0, 0, $thismonth, $thisday, $thisyear));
        $dataInicio = strftime("%d/%m/%Y", mktime(0, 0, 0, $thismonth, $thisday - ($indiceDia), $thisyear));
        $datafim = strftime("%d/%m/%Y", mktime(0, 0, 0, $thismonth, $thisday + (6 - $indiceDia), $thisyear));

        return array('dataInicio' => $dataInicio, 'datafim' => $datafim);
    }

    private function incrementaData($dia) {
        $thisday = substr($dia, 0, 2);
        $thismonth = substr($dia, 4, 5);
        $thisyear = substr($dia, 6, 10);

        $diaRetorno = strftime("%d/%m/%Y", mktime(0, 0, 0, $thismonth, $thisday + 1, $thisyear));

        return $diaRetorno;
    }

    /**
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
    public function indexAction() {

        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');

        $session = new Container("orangeSessionContainer");
        if( @$session->cdBase ){
            $statement = $dbAdapter->query("USE BDGE_".$session->cdBase);
            $statement->execute();
        }

        $form = new CadastroClienteAgenda($dbAdapter);
        $terminal = $this->params()->fromQuery('modal') == 'show' ? true : false;

		
        $dataAtual = $this->params()->fromQuery('dataAtual');
        $dataInicio = $datafim = (!empty($dataAtual)) ? $dataAtual : date('d/m/Y');

        $listaOperador = $this->getMacaTable("functionario")->getListaFuncionarioLoja($session->cdLoja);

        $cdop = $this->params()->fromQuery('cdop');
        if (!empty($cdop)) {
            $datas = $this->intervalosemana($dataInicio);
            $dataInicio = $datas['dataInicio'];
            $datafim = $datas['datafim'];

            $macas = $this->getMacaTable("franquia_maca_table")->getMacaFunctionario((int) $session->cdLoja, $cdop);
            $data = $this->incrementaData($dataInicio);
            $arrmacas[] = array('id' => $macas->nr_maca, 'maca' => $macas->ds_identificacao, 'name' => 'Segunda', 'data' => $data);
            $data = $this->incrementaData($data);
            $arrmacas[] = array('id' => $macas->nr_maca, 'maca' => $macas->ds_identificacao, 'name' => 'Ter&ccedil;a', 'data' => $data);
            $data = $this->incrementaData($data);
            $arrmacas[] = array('id' => $macas->nr_maca, 'maca' => $macas->ds_identificacao, 'name' => 'Quarta', 'data' => $data);
            $data = $this->incrementaData($data);
            $arrmacas[] = array('id' => $macas->nr_maca, 'maca' => $macas->ds_identificacao, 'name' => 'Quinta', 'data' => $data);
            $data = $this->incrementaData($data);
            $arrmacas[] = array('id' => $macas->nr_maca, 'maca' => $macas->ds_identificacao, 'name' => 'Sexta', 'data' => $data);
            $data = $this->incrementaData($data);
            $arrmacas[] = array('id' => $macas->nr_maca, 'maca' => $macas->ds_identificacao, 'name' => 'S&aacute;bado', 'data' => $data);
            $data = $this->incrementaData($data);
            $arrmacas[] = array('id' => $macas->nr_maca, 'maca' => $macas->ds_identificacao, 'name' => 'Domingo', 'data' => $data);
        } else {
            $macas = $this->getMacaTable("franquia_maca_table")->getMacas((int) $session->cdLoja, $cdop);
            foreach ($macas as $maca) {
                $arrmacas[] = array('id' => $maca->nr_maca, 'name' => $maca->ds_identificacao, 'data' => $dataInicio);
            }
        }

        $agendamentos = $this->getMacaTable("franquia_maca_table")->getAgendamentoMacas((int) $session->cdLoja, $dataInicio, $datafim, $cdop);
        $listaAgendamentos = array();
        foreach ($agendamentos as $agendamento) {
            if (!empty($cdop)) {
                $maca = $this->diassemana(date('w', strtotime($agendamento['DT_HORARIO'])));
            } else {
                $maca = $agendamento['DS_IDENTIFICACAO'];
            }

            $hora = date("H:i", strtotime($agendamento['DT_HORARIO']));

            $listaAgendamentos[$maca][$hora] = array(
                'id' => $agendamento['NR_MACA'],
                'maca' => $agendamento['DS_IDENTIFICACAO'],
                'idcliente' => $agendamento['CD_CLIENTE'],
                'title' => $agendamento['DS_CLIENTE'],
                'start' => strtr($agendamento['DT_HORARIO'], array('.000' => '')),
                'data' => date('d/m/Y', strtotime($agendamento['DT_HORARIO'])),
                'hora' => $hora,
                'st_cliente_rapido' => $agendamento['ST_CLIENTE_RAPIDO'],
                'nr_pedido' => $agendamento['NR_PEDIDO'],
                'st_pedido' => $agendamento['ST_PEDIDO'],
                'backgroundColor' => $agendamento['COR']
            );
        }

        $view = new ViewModel(
            array(
                'form' => $form,
                'intervalo' => $this->_intervalo,
                'horaInicio' => $this->_horaInicio,
                'minutoInicio' => $this->_minutoInicio,
                'horaFim' => $this->_horaFim,
                'minutoFim' => $this->_minutoFim,
                'listaFuncionarios' => $listaOperador,
                'listaMacas' => $arrmacas,
                'listaAgendamentos' => $listaAgendamentos,
                'cdFuncionario' => $this->params()->fromQuery('cdop'),
                'cdLoja' => $session->cdLoja,
                'dataAtual' => $dataInicio,
                'dataInicio' => $dataInicio,
                'datafim' => $datafim,
            )
        );

        $view->setTerminal($terminal);
        return $view;
    }

    public function agendaAction() {
        $session = new Container("orangeSessionContainer");
        $macas = $this->getMacaTable("franquia_maca_table")->getMacas((int) $session->cdLoja, $this->params()->fromQuery('cdFunc'));

        $arrRes = array();
        foreach ($macas as $maca) {
            $arrRes[] = array('name' => $maca->ds_identificacao,
                'id' => $maca->nr_maca
            );
        }

        print json_encode($arrRes);
        exit;
    }

    public function horariosAgendaAction() {
        $start = gmdate("d/m/Y", $this->params()->fromQuery('start'));
        $end = gmdate("d/m/Y", $this->params()->fromQuery('end'));
        $session = new Container("orangeSessionContainer");

        $agendamentos = $this->getMacaTable("franquia_maca_table")->getAgendamentoMacas((int) $session->cdLoja, $start, $end);
        $arrResult = array();
        foreach ($agendamentos as $agendamento) {
            $arrResult[] = array(
                'id' => $agendamento['NR_MACA'],
                'idcliente' => $agendamento['CD_CLIENTE'],
                'title' => $agendamento['DS_CLIENTE'],
                'start' => strtr($agendamento['DT_HORARIO'], array('.000' => '')),
                'end' => strtr($agendamento['DT_HORARIO'], array('.000' => '')),
                'allDay' => false,
                'resource' => $agendamento['NR_MACA'],
                'backgroundColor' => $agendamento['COR']
            );
        }

        print json_encode($arrResult);
        exit;
    }

    public function cadastrarClienteAgendaAction() {
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');

        $session = new Container("orangeSessionContainer");
        if( @$session->cdBase ){
            $statement = $dbAdapter->query("USE BDGE_".$session->cdBase);
            $statement->execute();
        }

        $form = new CadastroClienteAgenda($dbAdapter);

        $session = new Container("orangeSessionContainer");
        $messages = $this->flashMessenger()->getMessages();
        $dados = array();
        $servicos = array();

        if ($this->params()->fromQuery('idcliente')) {
            // recupera informacoes do cliente
            $dtHorario = date('d/m/Y H:i:s', strtotime(str_replace('/', '-', $this->params()->fromQuery('data') . ' ' . $this->params()->fromQuery('hora'))));
            $sm = $this->getServiceLocator($dbAdapter);
            $infoCliente = $sm->get("agendamento_franquia_table")->recuperaAgendamentoCliente((int) $this->params()->fromQuery('idcliente'), $dtHorario);
            foreach ($infoCliente as $cliente) {
                foreach ($cliente as $k => $v) {
                    $dados[$k] = $v;
                }
            }

            $servCliente = $sm->get("agendamento_franquia_servicos")->recuperaAgendamentoAgendamento($session->cdLoja, $dtHorario, $this->params()->fromQuery('id'));
            foreach ($servCliente as $k => $v) {
                $servicos[$k] = $v;
            }
        }

        $listaOperador = $this->getMacaTable("functionario")->getListaFuncionarioLoja($session->cdLoja);
        $listaSupervisor = $this->getMacaTable("functionario")->getListaSupervidoresLoja($session->cdLoja);

        $form->get("list_servico")->setValue('');

        $view = new ViewModel(array(
                "form" => $form,
                "messages" => $messages,
                "params" => $this->params()->fromQuery(),
                "listaFuncionarios" => $listaOperador,
                "listaSupervisor" => $listaSupervisor,
                "dados" => $dados,
                "servico" => $servicos,
                "stclienterapido" => $this->params()->fromQuery('stclienterapido')
            )
        );

        $view->setTerminal(true);

        return $view;
    }

    public function agendamentoClienteAction() {
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');

        $session = new Container("orangeSessionContainer");
        if( @$session->cdBase ){
            $statement = $dbAdapter->query("USE BDGE_".$session->cdBase);
            $statement->execute();
        }

        try {
            $form = new CadastroClienteAgenda($dbAdapter);

            if ($this->getRequest()->isPost()) {
                $dbAdapter->getDriver()->getConnection()->beginTransaction();

                $param = array();
                $param['total_venda'] = 0;

                foreach ($this->getRequest()->getPost() as $key => $value) {
                    if (!empty($value)) {
                        $param[$key] = $value;
                    }
                }
                $param['st_contatado'] = 'N';
                $param['cd_funcionario'] = $param['cdop'];

                $param['cd_loja'] = $session->cdLoja;
                $param['dt_horario'] = date('d/m/Y H:i:s', strtotime(str_replace('/', '-', $param['dt_atendimento'] . ' ' . $param['hr_atendimento'])));
                $param['dt_atendimento'] = date('d/m/Y H:i:s', strtotime(str_replace('/', '-', $param['dt_atendimento'] . ' ' . $param['hr_atendimento'])));

                //cadastra o cliente
                //cliente rapido
                if ($param['st_cliente_rapido'] == 'S') {
                    $param['ds_nome'] = $this->getRequest()->getPost('ds_nome_razao_social');
                    $modelCliente = new ClienteRapido();
                    $modelCliente->exchangeArray($param);
                    $param['cd_cliente'] = $param['cd_cliente_rapido'] = $sm->get("cliente_rapido_table")->save($modelCliente);
                } else {
                    // salva registro no CLIENTE
                    $modelCliente = new Cliente();
                    $modelCliente->exchangeArray($param);
                    $param['cd_cliente'] = $sm->get("cliente_table")->save($modelCliente);
                }

                // realiza o agendamento
                $modelCliente = new AgendamentoFranquia();
                $modelCliente->exchangeArray($param);
                $sm->get("agendamento_franquia_table")->save($modelCliente);


                // agendamento mercadoria
                $modelCliente = new AgendamentoFranquiaServicos();
                foreach ($param['cd_servico'] as $mercadoria) {
                    $arrServico = array(
                        'nr_maca' => $param['nr_maca'],
                        'dt_horario' => $param['dt_atendimento'],
                        'cd_loja' => $param['cd_loja'],
                        'cd_mercadoria' => $mercadoria
                    );

                    $sm->get("agendamento_franquia_servicos")->delete($arrServico);
                    $modelCliente->exchangeArray($arrServico);
                    $sm->get("agendamento_franquia_servicos")->save($modelCliente);
                }

                $form->setData($param);

                $dbAdapter->getDriver()->getConnection()->commit();
            }

            $this->flashMessenger()->addMessage('Agendamento realizado com sucesso.');
            $this->redirect()->toUrl("/agenda/index");
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
        }
    }

    public function recuperaServicoPorCodigoAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();

        $sm = $this->getServiceLocator();
        $serviceServico = $sm->get('mercadoria_table');
        $resServico = $serviceServico->getComboPrecoServico($request->getPost('cd_servico'));
        $response->setContent(\Zend\Json\Json::encode($resServico));
        return $response;
    }

    public function atendeclienteAction() {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');

        try {
            $dbAdapter->getDriver()->getConnection()->beginTransaction();
            $redirect = false;
            $request = $this->getRequest();

            if ($request->isPost()) {
                $data = $request->getPost();
                $model = new Cliente();
                $data["cd_cliente"] = $this->getMacaTable('cliente_table')->nextId();
                $model->exchangeArray($data);
                $this->getMacaTable('cliente_table')->save($model);

                $redirect = true;
            } else {
                $data = $this->params()->fromQuery();
            }

            $param['cd_servico'] = $data['cd_servico'];

            foreach ($param['cd_servico'] as $mercadoria) {
                $rowResult = $this->getMacaTable("agendamento_franquia_servicos")->recuperaprecoservico($mercadoria);
                $param['total_venda'] = $param['total_venda'] + $rowResult["VL_PRECO_VENDA"];
            }
            $session = new Container("orangeSessionContainer");
            $param = array('cd_loja' => $session->cdLoja,
                'nr_maca' => $data['maca'],
                'dt_horario' => $data['data'],
                'cd_funcionario' => (empty($data['funcionario1'])) ? $data['funcionario2'] : $data['funcionario1'],
                'total_venda' => $param['total_venda'],
                'cd_servico' => $param['cd_servico'],
                'cd_cliente' => $data['cd_cliente'],
                'vl_desconto' => $data['vl_desconto'],
                'nr_pedido' => $data["nr_pedido"],
                'hratendimento' => date('d/m/Y H:i:s', strtotime(str_replace('/', '-', $data['data'] . ' ' . $data['hora'])))
            );

            $retorno = $this->salvaPedidoAgendamento($param);

            if ($retorno) {
                $dbAdapter->getDriver()->getConnection()->commit();

                if ($redirect) {
                    $this->redirect()->toUrl("/agenda/index");
                } else {
                    print json_encode(array('valido' => $retorno));
                    exit;
                }
            } else {
                $dbAdapter->getDriver()->getConnection()->rollback();

                print json_encode(array('valido' => $retorno));
                exit;
            }
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
            exit;
        }
    }

    public function salvaPedidoAgendamento($post) {

        try {
            $sm = $this->getServiceLocator();
            $session = new Container("orangeSessionContainer");

            //3 - Apago as mercadorias na  tabela agendamento franquia servicos
            $sm->get('agendamento_franquia_servicos')->removeAgendamenteFranquiaServico(array($post['cd_loja'], $post["nr_maca"], $post["dt_horario"]));

            //6 - Atualiza a tabela de pedido, ou seja a "cabeça do pedido"
            $pedido = array();
            $total_venda_desconto = (isset($post['vl_desconto'])) ? (float) ($post['total_venda'] - ($post['total_venda'] * ((float) $post['vl_desconto'] / 100))) : $post['total_venda'];

            if (!empty($post['nr_pedido'])) {
                $nrPedido = $post['nr_pedido'];
            } else {
                $nrPedido = $sm->get("pedido_table")->getNextNumeroPedido();

                //PARAMETROS
                $pedido["CD_LOJA"] = $post['cd_loja'];
                $pedido["NR_PEDIDO"] = $nrPedido;
                $pedido["CD_LIVRO"] = 1;
                $pedido["CD_PRAZO"] = 1;
                $pedido["ST_PEDIDO"] = 'A';
                $pedido["CD_TIPO_PEDIDO"] = 1;
                $pedido["ORCAMENTO_PEDIDO"] = "P";
                $pedido["NR_CONTROLE"] = -1;
                $pedido["ST_CONSIGNADO"] = "N";
                $pedido["ST_APROVEITA_CREDITO"] = "S";
                $pedido["CD_FUNCIONARIO"] = (empty($post['cd_funcionario'])) ? $session->cdFuncionario :  $post['cd_funcionario'];
                $pedido["VL_TOTAL_BRUTO"] = $post['total_venda'];
                $pedido["VL_TOTAL_LIQUIDO"] = $total_venda_desconto;
                $pedido["UsuarioUltimaAlteracao"] = $session->cdFuncionario;//login do usuario que fez a alteracao
                $pedido["CD_CLIENE"] = $post['cd_cliente'];
                $pedido["DT_PEDIDO"] = $post['dt_horario'];
                $sm->get("pedido_table")->inserePedido($pedido);
            }
            //5- Obtenhos os valores das mercadorias, para inserir no corpo do pedido
            $sm->get("pedido_table")->deletaPedidoMercadoria($post['cd_loja'], $nrPedido);
            $sm->get("agendamento_franquia_servicos")->removeAgendamenteFranquiaServico(array($post['cd_loja'], $post["nr_maca"], $post["hratendimento"]));

            $totalTotalVenda = 0;
            $totalTotalLiquido = 0;
            foreach ($post['cd_servico'] as $cdMercadoria) {
                $cdMercadoria = (int) $cdMercadoria;

                //6 - Recuperando o valor do pre?o de venda normal
                $precoNormal = $sm->get('mercadoria_table')->getValorPrecoVenda($cdMercadoria);

                //7 - Recuperando o valor do pre?o de venda em promo??o
                $precoPromocao = $sm->get('mercadoria_table')->getValorPromocao($cdMercadoria);

                //array set
                $mercadoria = array();
                $mercadoria["CD_LOJA"] = $post['cd_loja'];
                $mercadoria["NR_PEDIDO"] = $nrPedido;
                $mercadoria["CD_MERCADORIA"] = $cdMercadoria;
                $mercadoria["CD_LIVRO"] = 1;
                $mercadoria["CD_PRAZO"] = 1;
                $mercadoria["VL_PRECO_VENDA"] = $precoNormal;
                $mercadoria["VL_PRECO_CUSTO"] = $precoPromocao;
                $mercadoria["NR_QTDE_PEDIDA"] = 1;
                $mercadoria["NR_QTDE_VENDIDA"] = 1;
                $mercadoria["VL_TOTAL_BRUTO"] = $precoPromocao;
                $mercadoria["VL_TOTAL_LIQUIDO"] = $precoPromocao;
                $mercadoria["VL_PRECO_VENDA_TAB"] = $precoPromocao;
                $mercadoria["ST_PROMOCAO"] = ($precoNormal > $precoPromocao) ? "S" : "N";
                $mercadoria["VL_DESCONTO_MERC"] = 0;
                $mercadoria["DS_LOCAL_RETIRADA"] = "";
                $mercadoria["DS_OBSERVACAO"] = "";

                //corre��o de bug para pre�o promocional
                $mercadoria["VL_PRECO_VENDA"] = ($precoNormal > $precoPromocao) ? $precoPromocao : $precoNormal;

                $totalTotalVenda += $precoNormal;
                $totalTotalLiquido += $precoPromocao;

                $sm->get("pedido_table")->inserePedidoMercadoria($mercadoria);

                $sm->get("agendamento_franquia_servicos")->saveMercadoriaAgendamento(array(
                    'cd_loja' => $post['cd_loja'],
                    'nr_maca' => $post['nr_maca'],
                    'dt_horario' => $post['hratendimento'],
                    'cd_mercadoria' => $cdMercadoria,
                ));
            }

            $pedidoAtu["CD_LIVRO"] = 1;
            $pedidoAtu["CD_PRAZO"] = 1;
            $pedidoAtu["ST_PEDIDO"] = 'A';
            $pedidoAtu["CD_TIPO_PEDIDO"] = 1;
            $pedidoAtu["ORCAMENTO_PEDIDO"] = "P";
            $pedidoAtu["NR_CONTROLE"] = -1;
            $pedidoAtu["ST_CONSIGNADO"] = "N";
            $pedidoAtu["ST_APROVEITA_CREDITO"] = "S";
            $pedidoAtu["CD_FUNCIONARIO"] = (empty($post['cd_funcionario'])) ? $session->cdFuncionario :  $post['cd_funcionario'];
            $pedidoAtu["VL_TOTAL_BRUTO"] = $totalTotalVenda;
            $pedidoAtu["VL_TOTAL_LIQUIDO"] = $totalTotalLiquido;
            $pedidoAtu["UsuarioUltimaAlteracao"] = $session->cdFuncionario; //login do usuario que fez a alteracao
            $pedidoAtu["NR_PEDIDO"] = $nrPedido;
            $pedidoAtu["CD_LOJA"] = $post['cd_loja'];
            $sm->get("pedido_table")->atualizaPedido($pedidoAtu);
            //ATUALIZA TB_AGENDAMENTO_FRANQUIA
            $sm->get("agendamento_franquia")->atualizaAgendamentoFranquia(array($nrPedido, $post['cd_cliente'], $post['cd_loja'], $post["nr_maca"], $post["hratendimento"]));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function situacaoagendamentoAction() {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $retorno = false;
        try {
            $dbAdapter->getDriver()->getConnection()->beginTransaction();
            $session = new Container("orangeSessionContainer");

            $retorno = $this->getMacaTable("pedido_table")->cancelaPedido($this->params()->fromQuery('nr_pedido'), $session->cdFuncionario, $session->cdLoja);
            $dbAdapter->getDriver()->getConnection()->commit();

            print json_encode(array('valido' => $retorno));
            exit;
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
            print json_encode(array('valido' => $retorno));
            exit;
        }
    }

    public function limpaagendamentoAction() {
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        try {
            $dbAdapter->getDriver()->getConnection()->beginTransaction();
            $horario = date('d/m/Y H:i:s', strtotime(str_replace('/', '-', $this->params()->fromQuery('data') . ' ' . $this->params()->fromQuery('hora'))));

            $sm = $this->getServiceLocator();
            $serviceServico = $sm->get('agendamento_franquia_table');
            $session = new Container("orangeSessionContainer");
            $resServico = $serviceServico->limpaAgendamento($this->params()->fromQuery('maca'), $horario, $session->cdLoja);

            $dbAdapter->getDriver()->getConnection()->commit();
            print json_encode(array('valido' => $resServico));
            exit;
        } catch (Exception $e) {
            $dbAdapter->getDriver()->getConnection()->rollback();
            exit;
        }
    }

    public function verificaagendaAction() {
        $hora = $this->params()->fromQuery('h');
        $minuto = $this->params()->fromQuery('m');

        $data = date('d/m/Y');
        if ($minuto == 55) {
            $horaInicio = $hora . ':' . $minuto . ':00';
            $horaFim = $hora + 1 . ':00:00';
        } elseif ($minuto == 25) {
            $horaInicio = $hora . ':' . $minuto . ':00';
            $horaFim = $hora . ':' . (int) ($minuto + 5) . ':00';
        }

        $dtInicio = $data . ' ' . $horaInicio;
        $dtFim = $data . ' ' . $horaFim;
        $session = new Container("orangeSessionContainer");

        $clientes = $this->getMacaTable("franquia_maca_table")->verificaAgenda($session->cdLoja, $dtInicio, $dtFim);
        $arrResult = array();
        foreach ($clientes as $cliente) {
            $arrResult[] = array($cliente['DS_NOME_RAZAO_SOCIAL'] => $cliente['DS_IDENTIFICACAO']);
        }

        print json_encode($arrResult);
        exit;
    }

}
