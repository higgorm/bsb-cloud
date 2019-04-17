<?php

/**
 * Zend Framework (http://framework.zend.com/)
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Model\MercadoriaTable;
use DOMPDFModule\View\Model\PdfModel;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Zend\Mail\Message;
use PHPExcel;

/**
 *
 * @author André Luiz Geraldi <andregeraldi@gmail.com>
 *
 */
class MailController extends AbstractActionController
{

    protected $clienteTable;

    /**
     * Carrega o Table de Cliente
     * @return Object Adapter
     */
    public function getTable($table)
    {
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        return $sm->get($table);
    }

    /**
     * Tela de pesquisa e envio de emails
     */


    public function crmAction()
    {
        $request = $this->getRequest();

        $data = array();
        $arrMail = array();
        $qtd = "Qtd Atendimentos";
        if ($request->isPost()) {
            $data = $request->getPost();
            $arrMail = $this->getTable("mail_table")->pesquisaMail($data);
            
            if($data['tpAtendimento'] == 2 || $data['tpAtendimento'] == 4)
                $qtd = "Dias de Ausência";
        }

        $view = new ViewModel(array(
            'arrMail' => $arrMail,
            'post' => $data,
            'qtd' => $qtd
        ));

        return $view;
    }

    /**
     * Realiza a pesquisa dos cliente para envio de emails
     * @return Json
     */
    public function pesquisaAction()
    {
        $post = $this->getRequest()->getPost();
        echo '<pre>';
        var_dump($post);
        exit;
    }

    /*
     * Action responsavel por enviar os emails para os clientes recuperados da base
     */

    public function sendMailAction()
    {
        ini_set('max_execution_time', 250);

        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $data = $request->getPost();
                
                $session = new Container("orangeSessionContainer");

                // recuperando informacoes de email do cliente
                $arrConfig = $this->getTable("mail_table")->getConfiguracaoEmailLoja($session->cdLoja);

                // Inicia a classe PHPMailer
                $mail = new \PHPMailer();

                // Define os dados do servidor e tipo de conexão
                // =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
                $mail->IsSMTP(); // Define que a mensagem será SMTP
                $mail->SMTPAuth = true; // Usa autenticação SMTP? (opcional)
//                $mail->Host = "ssl://{$arrConfig['DS_SMTP_Host']}"; // COMO NÃO SE SABE DE PARA ENVIO DE EMAIL TODOS SAO SSL, SEGUE O QUE ESTIVER NA BASE
                $mail->Host = $arrConfig['DS_SMTP_Host']; // Endereço do servidor SMTP
                $mail->Port = $arrConfig['DS_SMTP_Port'];
                $mail->Username = $arrConfig['DS_SMTP_User']; // Usuário do servidor SMTP
                $mail->Password = $arrConfig['DS_SMTP_Pass']; // Senha do servidor SMTP
                // Define o remetente
                // =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
                $mail->From = ""; // Seu e-mail
                $mail->FromName = ""; // Seu nome
                // Define os dados técnicos da Mensagem
                // =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
                $mail->IsHTML(true); // Define que o e-mail será enviado como HTML
                //$mail->CharSet = 'iso-8859-1'; // Charset da mensagem (opcional)
                // Define a mensagem (Texto e Assunto)
                // =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
                $mail->Subject = $data['txtAssunto']; // Assunto da mensagem
                $mail->Body = $data['txtMensagem'];
                //$mail->AltBody = ;
                // Define os anexos (opcional)
                // =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
                $i = 0;
                $files = $_FILES['anexo'];

                if (isset($files)) {
                    foreach ($files as $k => $f) {
                        if ($files['error'][$i] != 0)
                            continue;

                        $mail->AddAttachment($files['tmp_name'][$i], $files['name'][$i]);  // Insere um anexo
                        $i++;
                    }
                }

                $enviado = true;
                $arrMail = $this->getTable("mail_table")->getClientes($data['selectCliente']);
                $c = 0;
                foreach ($arrMail as $m) {
                    if (!empty($m['DS_EMAIL'])) {
                        // Define os destinatário(s)
                        // =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
                        $mail->ClearAllRecipients();
                        $mail->AddAddress($m['DS_EMAIL'], $m['DS_NOME_RAZAO_SOCIAL']);
                        //$mail->AddCC('ciclano@site.net', 'Ciclano'); // Copia
                        //$mail->AddBCC('fulano@dominio.com.br', 'Fulano da Silva'); // Cópia Oculta
                        // Envia o e-mail
                        $mail->Timeout = 180;
                        $enviado = $mail->Send();
                        $mail->SMTPDebug = true;
                    }
                }

                // Limpa os destinatários e os anexos
                $mail->ClearAllRecipients();
                $mail->ClearAttachments();
                // Exibe uma mensagem de resultado
                //if ($enviado) {
                //    echo "E-mail enviado com sucesso!";
                //} else {
                //    echo "Não foi possível enviar o e-mail.<br /><br />";
                //    echo "<b>Informações do erro:</b> <br />" . $mail->ErrorInfo;
                //}
                $this->redirect()->toUrl("/mail/crm");
            }
        } catch (Exception $ex) {
            print $ex;
            exit;
        }
    }

    public function gerarPlanilhaAction()
    {
        $data = $this->getRequest()->getPost();
        $arrMail = $this->getTable("mail_table")->getClientes($data->get('selectCliente'));

        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'DS_FANTASIA')
            ->setCellValue('B1', 'DS_FONE1')
            ->setCellValue('C1', 'DS_FONE2')
            ->setCellValue('D1', 'DS_EMAIL');

        $count = 2;
        foreach ($arrMail as $res) {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $count, $res['DS_NOME_RAZAO_SOCIAL']);
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('B' . $count, $res['DS_FONE1']);
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('C' . $count, $res['DS_FONE2']);
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('D' . $count, $res['DS_EMAIL'])
            ;
            $count++;
        }

        $objPHPExcel->getActiveSheet()->setTitle('ARQ');
        $objPHPExcel->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="01simple.xls"');
        header('Cache-Control: max-age=0');

        header('Cache-Control: max-age=1');

        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function relatorioAction()
    {
        ini_set('max_execution_time', 120);
        ini_set('memory_limit', '256M');
        
        $data = $this->getRequest()->getPost();
        $session = new Container("orangeSessionContainer");
        $arrMail = $this->getTable("mail_table")->getClientes($data->get('selectCliente'));

        $pdf = new PdfModel();
        $pdf->setOption('filename', 'relatorio');
        $pdf->setOption('paperSize', 'a4');
        $pdf->setOption('paperOrientation', 'landscape');

        $arrTpAtendimento = array('Clientes Top 100', 'Todos os Cliente Atendidos', 'Todos os Cliente');
        $arrCdAtendimento = array('1', '2', '3');
        $tipoAtendimento = str_replace($arrCdAtendimento, $arrTpAtendimento, $data->get('tpAtendimento'));
        $pdf->setVariables(array(
            'dsLoja' => $session->dsLoja,
            'dsPesquisa' => $tipoAtendimento,
            'logo' => '<img src="' . realpath(__DIR__ . '/../../../../../public/img') . '/logo-orange-small.png" alt="logo"  />',
            'arrMail' => $arrMail,
            'dataAtual' => date("d/m/Y"),
            'horaAtual' => date("h:i:s"),
        ));

        $pdf->setTemplate("application/mail/relatorio.phtml");

        return $pdf;
    }

    public function analiseAction(){

        $session = new Container("orangeSessionContainer");
        $array = $this->getTable("mail_table")->getAnalise($session->cdLoja);

        $total =  count($array['aquisicao']) + count($array['retencao']) +  count($array['perda']);
        if($array['aquisicao'] > 0){
            $aquisicao = array(
                'total'            => count($array['aquisicao']),
                'porcentagem'      => (count($array['aquisicao'])/$total)*100,
            );
        }else{
            $aquisicao = array(
                'total'            => 0,
                'porcentagem'      => 0,
            );
        }
        if($array['retencao'] > 0){
            $retencao = array(
                'total'             => count($array['retencao']),
                'porcentagem'       => (count($array['retencao'])/$total)*100,
            );
        }else{
            $retencao = array(
                'total'             => 0,
                'porcentagem'       => 0,
            );
        }
        if($array['perda'] > 0){
            $perda = array(
                'total'             => count($array['perda']),
                'porcentagem'       => (count($array['perda'])/$total)*100,
            );
        }else{
            $perda = array(
                'total'             => 0,
                'porcentagem'       => 0,
            );
        }
        $view = new ViewModel(array(
            'aquisicao'             => $aquisicao,
            'retencao'              => $retencao,
            'perda'                 => $perda,
            'total'                 => $total
        ));

        return $view;
    }

    public function verClientesAction(){

        $session = new Container("orangeSessionContainer");

        $array = $this->getTable("mail_table")->getAnalise($session->cdLoja);
        $arrMail = array();
        try {
            $tipo = $this->params()->fromQuery('tipo');

            if (is_array($array)) {
                if(isset($array[$tipo])) {
                    foreach($array[$tipo] as $id){
                        $arrMail[$id] = $this->getTable("cliente_table")->getId($id);
                    }
                }
            }

            $view = new ViewModel(array(
                'arrMail' => $arrMail
            ));

            $view->setTerminal(true);
            $view->setTemplate("application/mail/verClientes.phtml");

            return $view;

        } catch (Exception $e) {

            return false;
        }
    }

}
