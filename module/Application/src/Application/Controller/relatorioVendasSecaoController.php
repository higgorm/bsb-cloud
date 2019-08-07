<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Pdf\Color\GrayScale;
use Zend\Authentication\AuthenticationService;
use DOMPDFModule\View\Model\PdfModel;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class relatorioVendasSecaoController extends RelatorioController
{
	
    public function pesquisaAction()
    {
        //self::validaAcessoGerente();
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
        $statement = $dbAdapter->query("SELECT DISTINCT L.CD_LOJA, L.DS_RAZAO_SOCIAL FROM TB_LOJA L ");

        $resultLojas = $statement->execute(array($session->cdLoja));

        $view = new ViewModel();
        $view->setVariable('listaLoja', $resultLojas);
        $view->setTemplate("application/relatorio/vendasSecao/pesquisa.phtml");

        return $view;
    }

    public function relatorioAction()
    {
        $session = new Container("orangeSessionContainer");
        $post = $this->getRequest()->getPost();

        $cdLoja = $post['cd_loja'];
        $noLoja = 'TODAS';

        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');

        if (false !== empty($post['cd_loja'])) {
            $statement = $dbAdapter->query("SELECT DISTINCT L.CD_LOJA, L.DS_RAZAO_SOCIAL FROM TB_LOJA L WHERE CD_LOJA = ? ");
            $resultLojas = $statement->execute(array($cdLoja));

            foreach ($resultLojas as $k => $v) {
                if ($k == 'CD_LOJA')
                    $noLoja = $v;
            }
        }

        $anoEmissao = $this->params()->fromQuery('anoEmissao') ? $this->params()->fromQuery('anoEmissao') : $post->get('anoEmissao');
        $cdLoja = $post->get('cd_loja');

        $sql = " SELECT CD_TIPO_mercadoria,
                        DS_Tipo_Mercadoria,
                        Vendas01 = IsNull( sum( Vendas01 ), 0 ), Vendas02 = IsNull( sum( Vendas02 ), 0 ), Vendas03 = IsNull( sum( Vendas03 ), 0 ),
                        Vendas04 = IsNull( sum( Vendas04 ), 0 ), Vendas05 = IsNull( sum( Vendas05 ), 0 ), Vendas06 = IsNull( sum( Vendas06 ), 0 ),
                        Vendas07 = IsNull( sum( Vendas07 ), 0 ), Vendas08 = IsNull( sum( Vendas08 ), 0 ), Vendas09 = IsNull( sum( Vendas09 ), 0 ),
                        Vendas10 = IsNull( sum( Vendas10 ), 0 ), Vendas11 = IsNull( sum( Vendas11 ), 0 ), Vendas12 = IsNull( Sum( Vendas12 ), 0 ),
                        TotalAnual = IsNull( Sum( Vendas01 ), 0 ) +
                                     IsNull( Sum( Vendas02 ), 0 ) +
                                     IsNull( Sum( Vendas03 ), 0 ) +
                                     IsNull( Sum( Vendas04 ), 0 ) +
                                     IsNull( Sum( Vendas05 ), 0 ) +
                                     IsNull( Sum( Vendas06 ), 0 ) +
                                     IsNull( Sum( Vendas07 ), 0 ) +
                                     IsNull( Sum( Vendas08 ), 0 ) +
                                     IsNull( Sum( Vendas09 ), 0 ) +
                                     IsNull( Sum( Vendas10 ), 0 ) +
                                     IsNull( Sum( Vendas11 ), 0 ) +
                                     IsNull( Sum( Vendas12 ), 0 )
                   FROM ( SELECT P.CD_LOJA, P.NR_PEDIDO, T.CD_TIPO_MERCADORIA,
                                 DS_Tipo_Mercadoria,
                                 Vendas01 = CASE WHEN month( p.DT_PEDIDO ) =  1 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas02 = CASE WHEN month( p.DT_PEDIDO ) =  2 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas03 = CASE WHEN month( p.DT_PEDIDO ) =  3 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas04 = CASE WHEN month( p.DT_PEDIDO ) =  4 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas05 = CASE WHEN month( p.DT_PEDIDO ) =  5 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas06 = CASE WHEN month( p.DT_PEDIDO ) =  6 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas07 = CASE WHEN month( p.DT_PEDIDO ) =  7 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas08 = CASE WHEN month( p.DT_PEDIDO ) =  8 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas09 = CASE WHEN month( p.DT_PEDIDO ) =  9 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas10 = CASE WHEN month( p.DT_PEDIDO ) = 10 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas11 = CASE WHEN month( p.DT_PEDIDO ) = 11 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END,
                                 Vendas12 = CASE WHEN month( p.DT_PEDIDO ) = 12 THEN Sum( PM.VL_TOTAL_LIQUIDO ) *
                 ( select Desconto = 1 - ( tp.vl_total_bruto - tp.vl_total_Liquido ) / tp.vl_total_bruto * 1.00
                     from tb_pedido tp
                    where   tp.st_pedido = 'F'
                          and tp.cd_tipo_pedido in ( 1, 2, 5, 10 )
                          and tp.cd_loja   = P.CD_LOJA
                          and tp.nr_pedido = p.nr_pedido  )
                 END
                             FROM TB_PEDIDO P
                                  LEFT JOIN TB_PEDIDO_MERCADORIA PM    ON     P.CD_LOJA   = PM.CD_LOJA
                                                                          AND P.NR_PEDIDO = PM.NR_PEDIDO
                                  LEFT JOIN TB_MERCADORIA M            ON     PM.CD_MERCADORIA     = M.CD_MERCADORIA
                                  LEFT JOIN TB_TIPO_MERCADORIA_SECAO T ON     M.CD_TIPO_MERCADORIA = T.CD_TIPO_MERCADORIA
                            WHERE      p.st_pedido = 'F'
                                  AND P.CD_TIPO_PEDIDO IN ( 1, 2, 5, 10 )";
        if ($cdLoja != "") {
            $sql .= " AND P.CD_LOJA = {$cdLoja} ";
        } else {
            $sql .= " AND P.CD_LOJA IS NOT NULL ";
        }

        $sql .= " AND YEAR( P.DT_PEDIDO ) = {$anoEmissao}
                           GROUP BY P.CD_LOJA, P.NR_PEDIDO, T.CD_TIPO_MERCADORIA, DS_Tipo_Mercadoria, MONTH( p.DT_PEDIDO ) ) Resumo
                 GROUP BY CD_TIPO_MERCADORIA, DS_TIPO_MERCADORIA
                 ORDER BY CD_TIPO_MERCADORIA, DS_TIPO_MERCADORIA  ";
//        echo '<pre>';
//        var_dump($this->params()->fromQuery('pdf'));
//        exit;
        if ($this->params()->fromQuery('pdf') != true) {

            $statementList = $dbAdapter->query($sql);
            $results = $statementList->execute();

            $viewModel = new ViewModel();
            $viewModel->setTerminal(true);
            $viewModel->setVariable('anoEmissao', $anoEmissao);
//            $viewModel->setVariable('tiposPagamento', $tiposPagamento);
            $viewModel->setVariable('lista', $results);
            $viewModel->setVariable('ano', $anoEmissao);
            $viewModel->setVariable('dsLoja', $cdLoja ? $cdLoja . ' - ' .$noLoja : 'TODAS');
            $viewModel->setVariable('logo', '<img src="/img/logo-orange-small.png" alt="logotipo"/>');
            $viewModel->setVariable('dataAtual', date("d/m/Y"));
            $viewModel->setVariable('horaAtual', date("h:i:s"));
            $viewModel->setTemplate("application/relatorio/vendasSecao/relatorio.phtml");

            return $viewModel;
        } else {

            $statementList = $dbAdapter->query($sql);
            $results = $statementList->execute();

            $pdf = new PdfModel();
            $pdf->setOption('filename', 'relatorio-vendas-secao');
            $pdf->setOption('paperSize', 'a4');
            $pdf->setOption('paperOrientation', 'landscape');

            $pdf->setVariables(array(
                'dataAtual' => date("d/m/Y"),
                'horaAtual' => date("h:i:s"),
                'logo' => '<img src="' . realpath(__DIR__ . '/../../../../../public/img') . '/logo-orange-small.png" alt="logo"  />',
                'anoEmissao' => $anoEmissao,
                'ano' => $anoEmissao,
//                'tiposPagamento' => $tiposPagamento,
                'lista' => $results,
                'cdLoja', $cdLoja,
                'dsLoja', $cdLoja ? $cdLoja . ' - ' .$noLoja : 'TODAS'
            ));

            $pdf->setTemplate("application/relatorio/vendasSecao/relatorio.phtml");

            return $pdf;
        }
    }

}
