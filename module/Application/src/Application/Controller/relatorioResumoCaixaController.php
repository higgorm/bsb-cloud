<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @author andre luiz geraldi
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use DOMPDFModule\View\Model\PdfModel;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class relatorioResumoCaixaController extends RelatorioController
{
	
    protected $table;

    public function getTable($strService)
    {
        $sm = $this->getServiceLocator();
        $this->table = $sm->get($strService);

        return $this->table;
    }

    public function pesquisaAction()
    {
        self::validaAcessoGerente();

        $view = new ViewModel(array(
            "listaLoja" => $this->getTable('loja_table')->fetchAll(),
            "listaOperador" => $this->getTable('loja_table')->fetchAll(),
        ));
        $view->setTemplate("application/relatorio/resumoCaixa/pesquisa.phtml");

        return $view;
    }

    public function relatorioAction()
    {
        // get the db adapter
        $sm 		= $this->getServiceLocator();
        $dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
        $cdLoja = $session->cdLoja;

        if ($this->getRequest()->getPost()->get('pdf') == 'false') {
            $post = $this->getRequest()->getPost();

            $cdOperador = $post->get('cdOperador');
            $arrOperador = $this->getTable('functionario')->find($cdOperador);

            $dtAbertura = ($post->get('dtInicial')) ? $post->get('dtInicial') : NULL;
            $dtFechamento = ($post->get('dtFinal')) ? $post->get('dtFinal') : NULL;

            $caixa = array();

            $totalCaixa = $this->getTable('caixa-table')->calculaValorTotalCaixa('caixa',$cdLoja, date(FORMATO_ESCRITA_DATA,strtotime($dtAbertura)), date(FORMATO_ESCRITA_DATA,strtotime($dtFechamento)));
            foreach($totalCaixa as $result){
                $DT = date("d/m/Y", strtotime($result['DT_MOVIMENTO']));
                $caixa[$DT] = array(
                    'data'          =>$DT,
                    'totalPedRec'   => $result['VL_TOTAL_BRUTO'],
                    'totalOs'       => $this->getTable('caixa-table')->calculaValorTotalCaixa('totalOs',$cdLoja,$DT),
                    'frete'         => $this->getTable('caixa-table')->calculaValorTotalCaixa('frete',$cdLoja,$DT),
                    'dinheiro'      => $this->getTable('caixa-table')->calculaValorTotalCaixa('dinheiro',$cdLoja,$DT),
                    'cheque'        => $this->getTable('caixa-table')->calculaValorTotalCaixa('cheque',$cdLoja,$DT),
                    'cheque_pre'    => $this->getTable('caixa-table')->calculaValorTotalCaixa('cheque-pre',$cdLoja,$DT),
                    'cartao'        => $this->getTable('caixa-table')->calculaValorTotalCaixa('cartao',$cdLoja,$DT),
                    'boleto'        => $this->getTable('caixa-table')->calculaValorTotalCaixa('boleto',$cdLoja,$DT),
                    'deposito'      => $this->getTable('caixa-table')->calculaValorTotalCaixa('deposito',$cdLoja,$DT),
                    'convenio'      => $this->getTable('caixa-table')->calculaValorTotalCaixa('convenio',$cdLoja,$DT),
                    'outros'        => $this->getTable('caixa-table')->calculaValorTotalCaixa('outros',$cdLoja,$DT),
                    'entrada'       => $this->getTable('caixa-table')->calculaValorTotalCaixa('entrada',$cdLoja,$DT),
                    'saida'         => $this->getTable('caixa-table')->calculaValorTotalCaixa('saida',$cdLoja,$DT),
                    'totaGeral'     => ($result['VL_TOTAL_BRUTO'] + $this->getTable('caixa-table')->calculaValorTotalCaixa('entrada',$cdLoja,$DT)) -
                        $this->getTable('caixa-table')->calculaValorTotalCaixa('saida',$cdLoja,$DT)
                );

            }

            $view = new ViewModel();
            $view->setTerminal(true);
            $view->setVariable('logo', '<img src="/img/logo-orange-small.png" alt="logotipo"/>');
            $view->setVariable("cdLoja", $session->cdLoja);
            $view->setVariable("dsLoja", $session->dsLoja);
            $view->setVariable("dtAbertura", $dtAbertura);
            $view->setVariable("dtFechamento", $dtFechamento);
            $view->setVariable("operador", $arrOperador);
            $view->setVariable("listCaixa", $caixa);
            $view->setVariable('dataAtual',date("d/m/Y"));
            $view->setVariable('horaAtual',date("h:i:s"));
            $view->setTemplate("application/relatorio/resumoCaixa/relatorio.phtml");

            return $view;

        } else {

            $post = $this->getRequest()->getPost();

            $cdOperador = $post->get('pdf_cdFuncionario');
            $arrOperador = $this->getTable('functionario')->find($cdOperador);

            $dtAbertura = ($post->get('pdf_dtInicial')) ? $post->get('pdf_dtInicial') : NULL;
            $dtFechamento = ($post->get('pdf_dtFinal')) ? $post->get('pdf_dtFinal') : NULL;
            $caixa = array();

           // $totalCaixa = $this->getTable('caixa-table')->calculaValorTotalCaixa('caixa',$cdLoja, $dtAbertura, $dtFechamento);
            $totalCaixa = $this->getTable('caixa-table')->calculaValorTotalCaixa('caixa',$cdLoja, date(FORMATO_ESCRITA_DATA,strtotime($dtAbertura)), date(FORMATO_ESCRITA_DATA,strtotime($dtFechamento)));
            foreach($totalCaixa as $result){
                $DT = date("d/m/Y", strtotime($result['DT_MOVIMENTO']));
                $caixa[$DT] = array(
                    'data'          =>$DT,
                    'totalPedRec'   => $result['VL_TOTAL_BRUTO'],
                    'totalOs'       => $this->getTable('caixa-table')->calculaValorTotalCaixa('totalOs',$cdLoja,$DT),
                    'frete'         => $this->getTable('caixa-table')->calculaValorTotalCaixa('frete',$cdLoja,$DT),
                    'dinheiro'      => $this->getTable('caixa-table')->calculaValorTotalCaixa('dinheiro',$cdLoja,$DT),
                    'cheque'        => $this->getTable('caixa-table')->calculaValorTotalCaixa('cheque',$cdLoja,$DT),
                    'cheque_pre'    => $this->getTable('caixa-table')->calculaValorTotalCaixa('cheque-pre',$cdLoja,$DT),
                    'cartao'        => $this->getTable('caixa-table')->calculaValorTotalCaixa('cartao',$cdLoja,$DT),
                    'boleto'        => $this->getTable('caixa-table')->calculaValorTotalCaixa('boleto',$cdLoja,$DT),
                    'deposito'      => $this->getTable('caixa-table')->calculaValorTotalCaixa('deposito',$cdLoja,$DT),
                    'convenio'      => $this->getTable('caixa-table')->calculaValorTotalCaixa('convenio',$cdLoja,$DT),
                    'outros'        => $this->getTable('caixa-table')->calculaValorTotalCaixa('outros',$cdLoja,$DT),
                    'entrada'       => $this->getTable('caixa-table')->calculaValorTotalCaixa('entrada',$cdLoja,$DT),
                    'saida'         => $this->getTable('caixa-table')->calculaValorTotalCaixa('saida',$cdLoja,$DT),
                    'totaGeral'     => ($result['VL_TOTAL_BRUTO'] + $this->getTable('caixa-table')->calculaValorTotalCaixa('entrada',$cdLoja,$DT)) -
                        $this->getTable('caixa-table')->calculaValorTotalCaixa('saida',$cdLoja,$DT)
                );

            }

            $pdf = new PdfModel();
            $pdf->setOption('filename', 'relatorio-caixa', array('attachment' => 1)); // Triggers PDF download, automatically appends ".pdf"
            $pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
            $pdf->setOption('paperOrientation', 'landscape'); // Defaults to "portrait"

            $pdf->setVariable("logo",'<img src="/img/logo-orange-small.png" alt="logotipo"/>');
            $pdf->setVariable("cdLoja", $session->cdLoja);
            $pdf->setVariable("dsLoja", $session->dsLoja);
            $pdf->setVariable("dtAbertura", $dtAbertura);
            $pdf->setVariable("dtFechamento", $dtFechamento);
            $pdf->setVariable("operador", $arrOperador);
            $pdf->setVariable("listCaixa", $caixa);
            $pdf->setVariable('dataAtual',date("d/m/Y"));
            $pdf->setVariable('horaAtual',date("h:i:s"));

            $pdf->setTemplate("application/relatorio/resumoCaixa/relatorio.phtml");

            return $pdf;
        }
    }

}
