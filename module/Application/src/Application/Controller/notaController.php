<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Application\Service\NotaFiscalService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Authentication\AuthenticationService;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Zend\File\Transfer\Adapter\Http;
use Zend\Mail;
use Util;
use NFePHP\Extras\Danfe;
use NFePHP\Extras\Danfce;
use NFePHP\NFe\ToolsNFe;
use NFePHP\Common\Files\FilesFolders;
use NFePHP\NFe\MakeNFe;
use NFePHP\Common\Certificate\open_pkcs12_read;
use Application\Model\NotaTable;
use Application\Model\NotaInutilizadaTable;
use Application\Model\TabelaTable;
use Application\Model\MercadoriaTable;
use Application\Model\PedidoTable;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 *
 * @author e.Guilherme
 *
 */
class NotaController extends OrangeWebAbstractActionController{
	/**
    * @return \Zend\Http\Response
    */

	public function onDispatch(\Zend\Mvc\MvcEvent $e){
		$session = new Container("orangeSessionContainer");
		if(!$session->cdBase){
			$this->redirect()->toRoute('index');
		}
        return parent::onDispatch($e);
	}

	public function listaAction(){

	    $notaFiscalService = new NotaFiscalService( $this->getServiceLocator());

        $request    = $this->getRequest();
        $messages   = $this->flashMessenger()->getMessages();
        $pageNumber = (int) $this->params()->fromQuery('pg');
        $param      = array();

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

        $listaNfe   = $notaFiscalService->getList($param, $pageNumber);
        $listaNfeInutilizadas   = $notaFiscalService->getListNumeroInutilizadas();

        $viewModel  = new ViewModel();
        $viewModel->setTerminal(false);
        $viewModel->setVariable('listaNfe', $listaNfe);
        $viewModel->setVariable('listaNfeInutilizadas', $listaNfeInutilizadas);

		if( $this->params()->fromQuery('error') )
			$viewModel->setVariable('error', $this->params()->fromQuery('error'));
		if( $this->params()->fromQuery('success') )
			$viewModel->setVariable('success', $this->params()->fromQuery('success'));

        return $viewModel;
	}

    /**
     * @return ViewModel
     */
    public function listaInutilizadasAction(){
        // get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');

        $request    = $this->getRequest();
        $messages   = $this->flashMessenger()->getMessages();
        $param      = array();

        if ($request->isPost()) {
            $post = $request->getPost();

            foreach ($post as $key => $value) {
                if (!empty($value)) {
                    $param[$key] = trim($value);
                }
            }
        }
        $notaInutilizada  = new NotaInutilizadaTable($dbAdapter);
        $listaNfe         = $notaInutilizada->listHistorico();

        $viewModel  = new ViewModel();
        $viewModel->setTerminal(true);
        $viewModel->setVariable('listaNfe', $listaNfe);

        return $viewModel;
    }

    /**
     * @return ViewModel
     */
	public function avulsaAction(){

		// get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        //get session
        $session = new Container("orangeSessionContainer");

		$cfopTable  = new TabelaTable($dbAdapter);
		$table      = new NotaTable($dbAdapter);
        $cfop       = $cfopTable->selectAll_cfop();
		$config     = $table->getConfig('1');
		$nfe		= array(
		                    'RET_Base_PIS' => '0.00',
                            'RET_Aliq_PIS' => $config[0]['RETENCAO_pPIS'],   //aliquota na nota avulsa, apartir da configuração padrão
                            'RET_Base_COFINS' => '0.00',
                            'RET_Aliq_COFINS' => $config[0]['RETENCAO_pCOFINS'],   //aliquota na nota avulsa, apartir da configuração padrão
                            'RET_Base_CSLL' => '0.00',
                            'RET_Aliq_CSLL' => $config[0]['RETENCAO_pCSLL'],   //aliquota na nota avulsa, apartir da configuração padrão
                            'RET_Base_IRRF' => '0.00',
                            'RET_Aliq_IRRF' => $config[0]['RETENCAO_pIRRF'],   //aliquota na nota avulsa, apartir da configuração padrão
                            'RET_Base_PREV' => '0.00',
                            'RET_Aliq_PREV' => $config[0]['RETENCAO_pPrevidencia'],   //aliquota na nota avulsa, apartir da configuração padrão
                    );
		$util     	= new \Util;

		$viewModel = new ViewModel();
        $viewModel->setTerminal(false);
		$viewModel->setVariable('nfe', array($nfe));
		$viewModel->setVariable('cfop', $cfop);
		$viewModel->setVariable('config', $config);
		$viewModel->setVariable('util', $util);

        return $viewModel;
	}

    /**
     * @return \Zend\Http\Response
     */
	public function cartaCorrecaoAction(){
		@$session = new Container("orangeSessionContainer");
		// get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
		$post = $this->getRequest()->getPost();
		$table = new notaTable($dbAdapter);

		if( !is_dir( getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\cartacorrecao\\' ))
			@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\cartacorrecao\\');

		$pageNumber = (int) $this->params()->fromQuery('pg');
        $param = array();

		$viewModel = new ViewModel();

		try{
            $nfe    	= new ToolsNFe(getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');
            $modelo 	= $post['modCCe'];
            $tpAmb  	= $post['tpAmbCCe'];
            $chave  	= $post['chaveCCe'];
            $xCorrecao  = $post['xCorrecao'];
            $nSeqEvento = 1;

            $aResposta = array();

            $nfe->setModelo($modelo);
            $retorno = $nfe->sefazCCe($chave, $tpAmb, $xCorrecao, $nSeqEvento, $aResposta);

            if( $aResposta['evento']['0']['nProt'] != ''){
                $msg = 'sucess=Carta de Correção enviada com sucesso!';
            }else{
                $msg = 'error=Erro na Carta de Correção! '.$aResposta['evento']['0']['xMotivo'];
            }

        } catch(\Exception $e) {
            $msg = 'error='.$e->getMessage();
        }

		return $this->redirect()->toUrl("/nota/lista?".$msg);
	}

    /**
     * @return ViewModel
     * @throws \ErrorException
     */
    public function inutilizarAction(){
        $sm                     = $this->getServiceLocator();
        $dbAdapter              = $sm->get('Zend\Db\Adapter\Adapter');
        $session                = new Container("orangeSessionContainer");
        $defaultconfigfolder    = getcwd() . '\vendor\config';
        $defaultpathConfig      = $defaultconfigfolder .'\config_'.$session->cdBase.'.json';
        $viewModel              = new ViewModel();
        $request                = $this->getRequest();
        $post                   = $request->getPost();
        $msg                    = "";

        //Verifica se existe  o diretorio "inutilizadas", senão cria
        if( !is_dir( getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\inutilizadas\\' ))
            @mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\inutilizadas\\');

        //Dados da NFe (ide)
        try {
            $table      = new NotaTable($dbAdapter);
            $config     = $table->getConfig('1');
            $tpAmb      = $config[0]['tp_amb'];      // 1 - produção // 2 - Homologação
        } catch (\Exception $e) {
            throw new \ErrorException('Erro ao obter dados da configurção.',503);
        }

        if ($request->isPost()) {
            try {
                $aResposta  = array();
                $data       = $request->getPost();
                $nfe        = new ToolsNFe(getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');

                $nfe->setModelo((int)$data->mod);   //55 nfe - 65 nfce

                $xml = @$nfe->sefazInutiliza($data->nr_serie, $data->nr_inicio, $data->nf_final, $data->ds_justificativa, $tpAmb, $aResposta);

                if ( $aResposta['nProt'] != '' &&  $aResposta['cStat']== "102") {
                    $msg = array("success" => "Enviado com sucesso: ". $aResposta['xMotivo']);
                } else if ( $aResposta['nProt'] != '') {
                    $msg = array("danger" =>  $aResposta['xMotivo']);
                } else{
                    $msg = array("danger" =>  $aResposta['xMotivo']);
                }

                $data->cd_loja = $session->cdBase;
                $data->cd_usuario = $session->cdUsuario;
                $notaInutilizada  = new NotaInutilizadaTable($dbAdapter);
                $notaInutilizada->save($data,$aResposta);

            } catch (\Exception $e) {
                $dbAdapter->getDriver()->getConnection()->rollback();
                $msg =  array("danger" =>"Erro: ". $e->getMessage());
            }
        }

        $viewModel->setVariable('messages', array($msg));
        $viewModel->setVariable('config', $config[0]);
        $viewModel->setTerminal(false);
        return $viewModel;
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
	public function configurarAction(){

		$sm = $this->getServiceLocator();
		$uploadAdapter = new Http();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $session = new Container("orangeSessionContainer");
		$table = new notaTable($dbAdapter);
		$cfopTable = new TabelaTable($dbAdapter);
        $request = $this->getRequest();
		$viewModel = new ViewModel();
		$viewModel->setTerminal(false);

		@$post = @$this->getRequest()->getPost();

		if( @$post->get('salvar') == true ){
			$array = array(
				"DS_RAZAO_SOCIAL" 		=> $post->get('razaosocial'),
				"NR_CGC"				=> $post->get('cnpj'),
				"CD_UF"					=> $post->get('siglaUF'),
				"DS_REGIME_TRIBUTARIO"	=> $post->get('regime'),
				"DS_CNAE"				=> $post->get('cnae'),
				"PIS_pPIS"				=> $post->get('pis'),
				"COFINS_pCOFINS"		=> $post->get('cofins'),
				"TP_AMBIENTE"			=> $post->get('tp_amb'),
				"CD_IBGE_CIDADE"		=> $post->get('cMunFG'),
				"DS_NOTA_PADRAO"		=> $post->get('mod'),
				"CD_FORMA_PAGAMENTO"	=> $post->get('formaPagamento'),
				"DS_FANTASIA"			=> $post->get('fantasia'),
				"NR_INSC_ESTADUAL"		=> $post->get('IE'),
				"DS_DANFE_eMail"		=> $post->get('email'),
				"DS_FONE"				=> $post->get('telefone'),
				"DS_LOGRADOURO"			=> $post->get('logradouro'),
				"DS_NUMERO"				=> $post->get('numero'),
				"DS_COMPLEMENTO"		=> $post->get('complemento'),
				"DS_BAIRRO"				=> $post->get('bairro'),
				"CD_IBGE_CIDADE"		=> $post->get('cidade_ibge'),
				"NR_CEP"				=> $post->get('cep'),
				"NR_NFE"				=> $post->get('ultimaNota'),
				"CD_NATUREZA_OPERACAO"	=> $post->get('natOpPad'),
				"RETENCAO_pPIS"			=> $post->get('rtPis'),
				"RETENCAO_pCOFINS"		=> $post->get('rtCofins'),
				"RETENCAO_pCSLL"		=> $post->get('rtCsll'),
				"RETENCAO_pIRRF"		=> $post->get('rtIrrf'),
				"RETENCAO_pPrevidencia"	=> $post->get('rtPrev'),
				"ST_OBRIGA_RETENCAO"	=> $post->get('obrigaRT'),
				"ST_OBRIGA_PACIENTE"    => $post->get('obrigaPaciente'),
				"NR_pTRIBUTOS"			=> $post->get('nr_pTributos'),
				"NR_pTRIBUTOS_EST"		=> $post->get('nr_pTributos_est'),
				"NR_pTRIBUTOS_MUN"		=> $post->get('nr_pTributos_mun'),
				"ST_ZERAR_BC"			=> $post->get('ST_ZERAR_BC'),
                "DS_TOKEN_CSC01"        => $post->get('DS_TOKEN_CSC01'),
                "DS_IDTOKEN_CSC01"      => $post->get('DS_IDTOKEN_CSC01'),
                "DS_TOKEN_CSC02"        => $post->get('DS_TOKEN_CSC02'),
                "DS_IDTOKEN_CSC02"      => $post->get('DS_IDTOKEN_CSC02')

			);

            if( @$post->get('certPassword') != '' ) {
                $certPassword 	     = @$post->get('certPassword');
                $array["DS_SENHA" ]  = $certPassword;
            }

			$table->atualiza_config( '1', $array );
			//$configfolder = @$post->get('configfolder');

			$defaultconfigfolder = getcwd() . '\vendor\config';
			$defaultpathConfig = $defaultconfigfolder .'\config_'.$session->cdBase.'.json';

			$tpAmb = $post->get('tp_amb'); // 1 - produção // 2 - Homologação

            if (  $post->get('mod') == 'NFE') {
                $pathXmlUrlFileNFe  = 'wsnfe_4.00_mod55.xml';
            } else {
                $pathXmlUrlFileNFe  = 'wsnfe_4.00_mod65.xml';
            }

			$pathXmlUrlFileNFSe = '';
            $pathXmlUrlFileCTe  = '';
			$pathXmlUrlFileMDFe = '';
            $pathXmlUrlFileCLe  = '';

			if( !is_dir( getcwd() . '\public\clientes\\'.$session->cdBase.'\\' ))
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\\');

			$pathNFeFiles   = getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\\';
			if( !is_dir( getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\\' )){
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\assinadas\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\entradas\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\enviadas\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\enviadas\aprovadas\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\saidas\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\temporarias\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\canceladas\\');
			}

			$pathNFSeFiles  = getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\\';
			if( !is_dir( getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\\' )){
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\assinadas\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\entradas\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\enviadas\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\enviadas\aprovadas\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\PDF\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\saidas\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\temporarias\\');
				@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFSe\canceladas\\');
			}

            $pathCTeFiles   = "";
            $pathMDFeFiles  = "";
            $pathCLeFiles   = "";

			if(is_uploaded_file($_FILES['logo']['tmp_name'])){
				//Fazer Upload
				$uploadAdapter->setDestination( getcwd() . '\public\clientes\\'.$session->cdBase.'\\' );
				$uploadAdapter->receive('logo');
				//Fim Upload
				rename( getcwd() . '\public\clientes\\'.$session->cdBase.'\\'.$_FILES['logo']['name'], getcwd() . '\public\clientes\\'.$session->cdBase.'\logo.jpg' );
			}

			$pathCertsFiles = getcwd() . '\vendor\Certs\\';
			$siteUrl        = 'bsbgestao.com.br/bsbcloud'; //Verificar
			$schemesNFe     = "\PL_009_V4\\";
			$schemesNFSe    = "";
            $schemesCTe     = "";
            $schemesMDFe    = "";
            $schemesCLe     = "";

			$razaosocial = @$post->get('razaosocial');
			$siglaUF 	 = @$post->get('siglaUF');
			$cnpj 		 = @$post->get('cnpj');
			//$tokenIBPT 	 = @$post->get('tokenIBPT');
            $tokenIBPT 	 = "";
            $tokenNFCe 	 = $post->get('DS_TOKEN_CSC01');   //"1A0F469E-50B1-4283-B402-33F2E1DE878E";
            $tokenNFCeId = $post->get('DS_IDTOKEN_CSC01'); //"000003";

			if(is_uploaded_file($_FILES['certificado']['tmp_name'])){
				//Fazer Upload
				$uploadAdapter->setDestination( getcwd() . '\vendor\Certs\\' );
				$uploadAdapter->receive('certificado');
				//Fim Upload
				rename( getcwd() . '\vendor\Certs\\'.$_FILES['certificado']['name'], getcwd() . '\vendor\Certs\\'.$session->cdBase.'.pfx' );
			}
			$certPfxName 	= $session->cdBase.'.pfx';
			if( @$post->get('certPassword') != '' ) {
                $certPassword 	= @$post->get('certPassword');
            }

			//@$post->get('certPhrase'); //Verificar
            $certPhrase 	= '';

            $format = 'P';
			$paper = 'A4';
			$southpaw = true;
			$pathLogoFile = getcwd() . '\public\clientes\\'.$session->cdBase.'\logo.jpg';
			$logoPosition = 'L';
			$font = 'Times';
			$printer = '';

			/*$mailAuth = @$post->get('mailAuth');
			$mailFrom = @$post->get('mailFrom');
			$mailSmtp = @$post->get('mailSmtp');
			$mailUser = @$post->get('mailUser');
			$mailPass = @$post->get('mailPass');
			$mailProtocol = @$post->get('mailProtocol');
			$mailPort = @$post->get('mailPort');*

			/*$mailFromMail = @$post->get('mailFromMail');
			$mailFromName = @$post->get('mailFromName');
			$mailReplayToMail = @$post->get('mailReplayToMail');
			$mailReplayToName = @$post->get('mailReplayToName');
			$mailImapHost = @$post->get('mailImapHost');
			$mailImapPort = @$post->get('mailImapPort');
			$mailImapSecurity = @$post->get('mailImapSecurity');
			$mailImapNocerts = @$post->get('mailImapNocerts');
			$mailImapBox = @$post->get('mailImapBox');

			/*$proxyIp = @$post->get('proxyIp');
			$proxyPort = @$post->get('proxyPort');
			$proxyUser = @$post->get('proxyUser');
			$proxyPass = @$post->get('proxyPass');*/

			$aDocFormat = array(
			    'format'=> $format,
			    'paper' => $paper,
			    'southpaw' => $southpaw,
			    'pathLogoFile' => $pathLogoFile,
			    'logoPosition' => $logoPosition,
			    'font' => $font,
			    'printer' => $printer
			);

			$aMailConf = array(
			    'mailAuth' => false,
			    'mailFrom' => 'nao-responder@bsbgestao.com.br',
			    'mailSmtp' => 'email-ssl.com.br',
			    'mailUser' => 'nao-responder@bsbgestao.com.br',
			    'mailPass' => 'suporte123456',
			    'mailProtocol' => 'SSL',
			    'mailPort' => '465',
			    'mailFromMail' => 'nao-responder@bsbgestao.com.br',
			    'mailFromName' => 'BSB Gestão - Não responder',
			    'mailReplayToMail' => '',
			    'mailReplayToName' => '',
			    'mailImapHost' => '',
			    'mailImapPort' => '',
			    'mailImapSecurity' => '',
			    'mailImapNocerts' => '',
			    'mailImapBox' => ''
			);

			/*$aProxyConf = array(
			    'proxyIp'=> $proxyIp,
			    'proxyPort'=> $proxyPort,
			    'proxyUser'=> $proxyUser,
			    'proxyPass'=> $proxyPass
			);*/

			$aConfig = array(
			    'atualizacao' => date('Y-m-d h:i:s'),
			    'tpAmb' => $tpAmb,
			    'pathXmlUrlFileNFe' => $pathXmlUrlFileNFe,
			    'pathXmlUrlFileCTe' => $pathXmlUrlFileCTe,
			    'pathXmlUrlFileMDFe' => $pathXmlUrlFileMDFe,
			    'pathXmlUrlFileCLe' => $pathXmlUrlFileCLe,
			    'pathXmlUrlFileNFSe' => $pathXmlUrlFileNFSe,
			    'pathNFeFiles' => $pathNFeFiles,
			    'pathCTeFiles'=> $pathCTeFiles,
			    'pathMDFeFiles'=> $pathMDFeFiles,
			    'pathCLeFiles'=> $pathCLeFiles,
			    'pathNFSeFiles'=> $pathNFSeFiles,
			    'pathCertsFiles' => $pathCertsFiles,
			    'siteUrl' => $siteUrl,
			    'schemesNFe' => $schemesNFe,
			    'schemesCTe' => $schemesCTe,
			    'schemesMDFe' => $schemesMDFe,
			    'schemesCLe' => $schemesCLe,
			    'schemesNFSe' => $schemesNFSe,
			    'razaosocial' => $razaosocial,
			    'siglaUF'=> $siglaUF,
			    'cnpj' => $cnpj,
			    'tokenIBPT' => $tokenIBPT,
			    'tokenNFCe' => $tokenNFCe,
			    'tokenNFCeId' => $tokenNFCeId,
			    'certPfxName' => $certPfxName,
			    'certPassword' => $certPassword,
			    'certPhrase' => $certPhrase,
			    'aDocFormat' => $aDocFormat,
			    'aMailConf' => $aMailConf,
			   // 'aProxyConf' => $aProxyConf
			);

			$content = json_encode($aConfig);
			$msg = 'SUCESSO !! arquivo de configuração confg.json SALVO.';
			if (! $resdefault = FilesFolders::saveFile($defaultconfigfolder, 'config_'.$session->cdBase.'.json', $content)) {
				$msg = "Falha ao salvar o config.json na pasta $defaultconfigfolder \n";
			}
			//if ($configfolder != $defaultconfigfolder) {
			//    if (! $res = FilesFolders::saveFile($configfolder, 'config.json', $content)) {
			//        $msg = "Falha ao salvar o config.json na pasta $configfolder \n";
			//    }
			//	}

			return $this->redirect()->toUrl("/nota/lista?pg=1");

		} else {

			$config = $table->getConfig('1');
			$cfop = $cfopTable->selectAll_cfop();

			$viewModel->setVariable('config', $config);
			$viewModel->setVariable('cfop', $cfop);
            $viewModel->setVariable('logotipoExistente', file_exists(getcwd() . '\public\clientes\\'.$session->cdBase.'\logo.jpg'));
            $viewModel->setVariable('logotipoCliente', '\\clientes\\'.$session->cdBase.'\logo.jpg');

			return $viewModel;
		}
	}

    /**
     *
     * Metódo que realmente ira cadastrar / gerar e enviar a NFE
     * Utilizado por outros metodos como:  geraNfePedidoAction e geraNfeAction
     *
     * @param string|int|null $nrPedido
     * @param Zend\Stdlib\Parameters $post
     *
     */
	private function _gerarEnviarNfe( $nrPedido = "", $post = null) {

        //array insercao no BD
        $nfeGe = array();

        //get Post
        if( empty($post) ) {
            $post = $this->getRequest()->getPost();
        }

        // get the db adapter
        $sm         = $this->getServiceLocator();
        $dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');

        //get session
        $session    = new Container("orangeSessionContainer");
        $table      = new notaTable($dbAdapter);
        $nfe        = new MakeNFe();
        $nfeTools   = new ToolsNFe( getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');

        //bkp e xml
        $bkpMail = 'bkp-nfe@bsbgestao.com.br';

        //Iniciar totais
        $totalISSQN 		= 0;
        $totalRetISS    	= 0;
        $totalICMS  		= 0;
        $totalBcISSQN 		= 0;
        $totalBCICMS  		= 0;
        $totalPISISSQN 		= 0;
        $totalPISICMS 		= 0;
        $totalCOFINSISSQN	= 0;
        $totalCOFINSICMS	= 0;
        $totalNota    		= 0;
        $totalNotaSemDesc   = 0;
        $totalDesconto      = 0;
        $totalServ      	= 0;
        $totalProd          = 0;
        $nfeGe['CD_LOJA']   = $session->cdLoja;
        $nfeGe['NR_PEDIDO'] = !empty($nrPedido) ? $nrPedido : $post->get('NR_PEDIDO');

        //Copiar CFOP para serviços/Mercadorias
        $copiarCFOP               =  ($post->get('copiarCFOP')=='S') ;

        //Preencher campos DMED
        $nfeGe['DMED_NOME'] 	  = $post->get('nome_paciente');
        $nfeGe['DMED_CPF'] 		  = $post->get('cpf_paciente');

        if($post->get('nascimento_paciente') != "") {
            $nfeGe['DMED_NASCIMENTO'] = date('Ymd',$post->get('nascimento_paciente'));
        }

        //modelo da NFe 55 ou 65 essa última NFCe
        $mod            = $post->get('mod');
        $nfeGe['mod']   = $mod;

        //Dados da NFe (ide)
        //Recupera parametros de configuração da NFE persitidos no Banco
        //$remetente = $statement->execute(array($session->cdLoja));
        $remetente = $table->getConfig($session->cdLoja);

        foreach ($remetente as $rem){
            $cUF            = substr( $rem['CD_IBGE_CIDADE'], 0, 2 ); //codigo numerico do estado
            $nfeGe['cUF']   = $cUF;
            $tpImp          = $mod == '65' ? '4' :'1';  //0=Sem geração de DANFE;
                                                        //1=DANFE normal, Retrato;
                                                        //2=DANFE normal, Paisagem;
                                                        //3=DANFE Simplificado; 4=DANFE NFC-e; 5=DANFE NFC-e em mensagem eletrônica
                                                        //(o envio de mensagem eletrônica pode ser feita de forma simultânea com a impressão do DANFE;
                                                        //usar o tpImp=5 quando esta for a única forma de disponibilização do DANFE).

             $tpEmis        = '1';                      //1=Emissão normal (não em contingência);
                                                        //2=Contingência FS-IA, com impressão do DANFE em formulário de segurança;
                                                        //3=Contingência SCAN (Sistema de Contingência do Ambiente Nacional);
                                                        //4=Contingência DPEC (Declaração Prévia da Emissão em Contingência);
                                                        //5=Contingência FS-DA, com impressão do DANFE em formulário de segurança;
                                                        //6=Contingência SVC-AN (SEFAZ Virtual de Contingência do AN);
                                                        //7=Contingência SVC-RS (SEFAZ Virtual de Contingência do RS);
                                                        //9=Contingência off-line da NFC-e (as demais opções de contingência são válidas também para a NFC-e);

            //Nota: Para a NFC-e somente estão disponíveis e são válidas as opções de contingência 5 e 9.
            $nfeGe['tpEmis']    = $tpEmis;
            $tpAmb              = $rem['TP_AMBIENTE']; //1=Produção; 2=Homologação
            $nfeGe['tpAmb']     = $tpAmb;
            $procEmi            = '0'; 	//0=Emissão de NF-e com aplicativo do contribuinte;
                                        //1=Emissão de NF-e avulsa pelo Fisco;
                                        //2=Emissão de NF-e avulsa, pelo contribuinte com seu certificado digital, através do site do Fisco;
                                        //3=Emissão NF-e pelo contribuinte com aplicativo fornecido pelo Fisco.

            $verProc            = '1.00';	//versão do aplicativo emissor
            $cMunFG             = $rem['CD_IBGE_CIDADE'];
            $cMunicipio         = $cMunFG;
            $nfeGe['cMunFG']    = $cMunFG;

            $pCOFINS            = $rem['COFINS_pCOFINS'];
            $pPIS               = number_format( $rem['PIS_pPIS'], 2, '.', '');
            $pTributos          = $rem['NR_pTRIBUTOS'];
            $pTributosEst       = $rem['NR_pTRIBUTOS_EST'];
            $pTributosMun       = $rem['NR_pTRIBUTOS_MUN'];

            //Dados do emitente
            $CNPJ               = $rem['NR_CGC'];
            $CPF                = ''; // Utilizado para CPF na nota
            $xNome              = $rem['DS_RAZAO_SOCIAL'];
            $xFant              = $rem['DS_FANTASIA'];
            $IE                 = $rem['NR_INSC_ESTADUAL'];
            $IEST               = ''; //NFC-e não deve informar IE de Substituto Tributário
            $IM                 = $rem['NR_INSC_ESTADUAL'];
            $CNAE               = $rem['DS_CNAE'];
            $CRT                = $rem['DS_REGIME_TRIBUTARIO'];
            $resp               = $nfe->tagemit($CNPJ, $CPF, $xNome, $xFant, $IE, $IEST, $IM, $CNAE, $CRT);

            //Passar para o array do DB
            $nfeGe['Emi_CNPJCPF']   = $CNPJ;
            $nfeGe['Emi_xNome']     = $xNome;
            $nfeGe['Emi_xFant']     = $xFant;
            $nfeGe['Emi_IEST']      = $IEST;
            $nfeGe['Emi_IE']        = $IE;

            //endereço do emitente
            $xLgr                   = $rem['DS_LOGRADOURO'];
            $nro                    = $rem['DS_NUMERO'];
            $xCpl                   = $rem['DS_COMPLEMENTO'];
            $xBairro                = $rem['DS_BAIRRO'];
            $cMun                   = $rem['CD_IBGE_CIDADE'];
            $xMun                   = $rem['DS_IBGE_CIDADE'];
            $UF                     = $rem['CD_UF'];
            $CEP                    = $rem['NR_CEP'];
            $cPais                  = '1058';
            $xPais                  = 'BRASIL';
            $fone                   = $rem['DS_FONE'];
            $resp                   = $nfe->tagenderEmit($xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $UF, $CEP, $cPais, $xPais, $fone);

            //Passar para o array do DB
            $nfeGe['Emi_xLgr']      = $xLgr;
            $nfeGe['Emi_nro']       = $nro;
            $nfeGe['Emi_xCpl']      = $xCpl;
            $nfeGe['Emi_xBairro']   = $xBairro;
            $nfeGe['Emi_cMun']      = $cMun;
            $nfeGe['Emi_xMun']      = $xMun;
            $nfeGe['Emi_UF']        = $UF;
            $nfeGe['Emi_CEP']       = $CEP;
            $nfeGe['Emi_fone']      = $fone;

            if ( $post['infNFE'] != '' && $post['DS_PROTOCOLO'] == '' ) {
                $bReenvia   = true;
                $nr_nota    = $post['infNFE'];
            } else {
                $bReenvia = false;

                if( $rem['NR_NFE'] > 0 ) {  //Pegar ultima nota
                    $nr_nota = $rem['NR_NFE'] + 1;
                } else {
                    $nr_nota = 1;
                }
            }

            $zeraBC 	= $rem['ST_ZERAR_BC'];
            $regime 	= $rem['DS_REGIME_TRIBUTARIO'];
            $remEmail 	= $rem['DS_DANFE_eMail'];

        } //end foreach remetente



        if ( $mod == '55' ) {
            //para versão 3.10 '2014-02-03T13:22:42-3.00' não informar para NFCe
            $dhEmi          = str_replace(" ", "T", date("Y-m-d H:i:sP", strtotime($post['dhEmi'])));
            $nfeGe['dEmi']  = date(FORMATO_ESCRITA_DATA_HORA, strtotime($post['dhEmi']));
            $indFinal       = '1'; //0=Não; 1=Consumidor final;
            $indPres        = '9'; //0=Não se aplica (por exemplo, Nota Fiscal complementar ou de ajuste);
                                    //1=Operação presencial;
                                    //2=Operação não presencial, pela Internet;
                                    //3=Operação não presencial, Teleatendimento;
                                    //4=NFC-e em operação com entrega a domicílio;
                                    //9=Operação não presencial, outros.
        } else {
            //Formato: “AAAA-MM-DDThh:mm:ssTZD” (UTC - Universal Coordinated Time).
            $dhEmi          = date("Y-m-d\TH:i:sP");;
            $nfeGe['dEmi']  = date(FORMATO_ESCRITA_DATA_HORA, strtotime($dhEmi));

            $indFinal       = '1';  //0=Não; 1=Consumidor final;
            $indPres        = '1';  //0=Não se aplica (por exemplo, Nota Fiscal complementar ou de ajuste);
                                    //1=Operação presencial;
                                    //2=Operação não presencial, pela Internet;
                                    //3=Operação não presencial, Teleatendimento;
                                    //4=NFC-e em operação com entrega a domicílio;
                                    //9=Operação não presencial, outros.
        }
        $nfeGe['indPres']   = $indPres;

        //destinatário
        $statement = $dbAdapter->query("SELECT TOP 1 	CI.CD_UF,
														CI.CD_IBGE,
														CI.DS_MUNICIPIO,
														C.*
										FROM TB_CLIENTE C
										LEFT JOIN TB_CIDADE_IBGE CI ON CI.CD_CIDADE = C.CD_CIDADE
										WHERE C.CD_CLIENTE  = ? ");

        //Parametros do Banco
        $destinatario   = $statement->execute(array($post->get('codCliente')));
        $destCNPJ       = '';
        $destCPF        = '';
        foreach( $destinatario as $dest ){

            if ( $post[ 'cfop' ] < 2000 ) {
                $idDest = '1'; //1=Operação interna; 2=Operação interestadual; 3=Operação com exterior.
                $tpNF = '0';
                $bInterna = True;
            } elseif( $post[ 'cfop' ] < 3000 ) {
                $idDest = '2'; //1=Operação interna; 2=Operação interestadual; 3=Operação com exterior.
                $tpNF = '0';
                $bInterna = false;
            } elseif( $post[ 'cfop' ] < 4000 ) {
                $idDest = '3'; //1=Operação interna; 2=Operação interestadual; 3=Operação com exterior.
                $tpNF = '0';
                $bInterna = false;
            } elseif( $post[ 'cfop' ] < 6000 ) {
                $idDest = '1'; //1=Operação interna; 2=Operação interestadual; 3=Operação com exterior.
                $tpNF = '1';
                $bInterna = True;
            } elseif( $post[ 'cfop' ] < 7000 ) {
                $idDest = '2'; //1=Operação interna; 2=Operação interestadual; 3=Operação com exterior.
                $tpNF = '1';
                $bInterna = false;
            } elseif( $post[ 'cfop' ] < 8000 ) {
                $idDest = '3'; //1=Operação interna; 2=Operação interestadual; 3=Operação com exterior.
                $tpNF = '1';
                $bInterna = false;
            }

            //UF do remetente diferente do UF do cliente
            // Com operação interna
            if ( $dest['CD_UF'] <> $UF && $idDest < 2 ) {
                $idDest     = 2;
                $bInterna   = false;
            }

            $nfeGe['tpNF']      = $tpNF;
            $cNF                = date('Ymd'); //numero aleatório da NF
            $natOp              = $post->get('xNatOp'); //natureza da operação
            $nfeGe['natOp']     = utf8_decode($natOp);
            $serie              = '1'; //serie da NFe
            $nfeGe['serie']     = $serie;
            $nNF                = $nr_nota;
            $nfeGe['infNFE']    = $nr_nota;// numero da NFe
            $dhSaiEnt           = str_replace(" ", "T", date("Y-m-d H:i:sP", strtotime($post['dhEmi']))); //versão 2.00, 3.00 e 3.10
            $nfeGe['dSaiEnt']   = date(FORMATO_ESCRITA_DATA_HORA, strtotime($post['dhEmi']));
            //$cDV                = '4';         //digito verificador
            $finNFe             = $post->get('finNFe'); //1=NF-e normal; 2=NF-e complementar; 3=NF-e de ajuste; 4=Devolução/Retorno.
            $nfeGe['finNFe']    = $finNFe;
            $dhCont             = ''; //entrada em contingência AAAA-MM-DDThh:mm:ssTZD
            $xJust              = ''; //Justificativa da entrada em contingência

            //Numero e versão da NFe (infNFe)
            $tempData   = explode("-", $dhEmi);
            $ano        = $tempData[0] - 2000;
            $mes        = $tempData[1];
            $cnpj       = $CNPJ;
            $chave      = $nfe->montaChave($cUF, $ano, $mes, $cnpj, $mod, $serie, $nNF, $tpEmis, $cNF);
            $versao     = '4.00';
            $resp       = $nfe->taginfNFe($chave, $versao);

            $cDV = substr($chave, -1); //digito verificador

            //tag IDE
            $resp = $nfe->tagide($cUF, $cNF, $natOp, $mod, $serie, $nNF, $dhEmi, $dhSaiEnt, $tpNF, $idDest, $cMunFG, $tpImp, $tpEmis, $cDV, $tpAmb, $finNFe, $indFinal, $indPres, $procEmi, $verProc, $dhCont, $xJust);
            //Passar para o array DB
            $nfeGe['DS_NFE_CHAVE'] = $chave;

            //refNFe NFe referenciada
            $refNFe = $post->get('refNFe');

            //refNFe NFe referenciada
            //$refNFe = '12345678901234567890123456789012345678901234';
            //$resp = $nfe->tagrefNFe($refNFe);

            //refNF Nota Fiscal 1A referenciada
            //$cUF = '35';
            //$AAMM = '1312';
            //$CNPJ = '12345678901234';
            //$mod = '1A';
            //$serie = '0';
            //$nNF = '1234';
            //$resp = $nfe->tagrefNF($cUF, $AAMM, $CNPJ, $mod, $serie, $nNF);

            //NFPref Nota Fiscal Produtor Rural referenciada
            //$cUF = '35';
            //$AAMM = '1312';
            //$CNPJ = '12345678901234';
            //$CPF = '123456789';
            //$IE = '123456';
            //$mod = '1';
            //$serie = '0';
            //$nNF = '1234';
            //$resp = $nfe->tagrefNFP($cUF, $AAMM, $CNPJ, $CPF, $IE, $mod, $serie, $nNF);

            //CTeref CTe referenciada
            //$refCTe = '12345678901234567890123456789012345678901234';
            //$resp = $nfe->tagrefCTe($refCTe);

            //ECFref ECF referenciada
            //$mod = '90';
            //$nECF = '12243';
            //$nCOO = '111';
            //$resp = $nfe->tagrefECF($mod, $nECF, $nCOO);

            $cpfCnpj = str_replace(array('.',',','/','-'),array('','','',''),$post->get('destCNPJ'));

            if( strlen($cpfCnpj) > 11)
                $destCNPJ = $cpfCnpj;
            else
                $destCPF = $cpfCnpj;

            $idEstrangeiro  = '';
            $xNome          = $dest['DS_NOME_RAZAO_SOCIAL'];
            $indIEDest      = $dest['indIE'];
            $IE             = $dest['NR_INSC_ESTADUAL'];
            $ISUF           = $dest['DS_SUFRAMA'];
            $email          = $dest['DS_EMAIL'];

            if( strlen($dest['NR_INSC_MUNICIPAL']) > 1 ){
                $IM = $dest['NR_INSC_MUNICIPAL'];
            }else{
                $IM = $dest['NR_INSC_ESTADUAL']; //Verificar
            }

            if ( $mod == '55' ) {
                $resp = $nfe->tagdest($destCNPJ, $destCPF, $idEstrangeiro, $xNome, $indIEDest, $IE, $ISUF, $IM, $email);
            }

            //Passar para o array DB
            $nfeGe['CD_CLIENTE']         = $post->get('codCliente');
            $nfeGe['Dest_CNPJCPF']       = ( $destCNPJ != '' ? $destCNPJ : $destCPF );
            $nfeGe['Dest_xNome']         = $xNome;
            $nfeGe['Dest_IE']            = $IE;
            $nfeGe['Dest_indIEDest']     = $indIEDest;
            $nfeGe['Dest_idEstrangeiro'] = $idEstrangeiro;

            //Endereço do destinatário
            $xLgr                       = $dest['DS_ENDERECO'];
            $nro                        = $dest['DS_NUMERO'];

            if( $dest['DS_COMPLEMENTO'] )
                $xCpl = $dest['DS_COMPLEMENTO'];
            else
                $xCpl = '';

            $xBairro    = $dest['DS_BAIRRO'];
            $cMun       = $dest['CD_IBGE'];
            $xMun       = $dest['DS_MUNICIPIO'];
            $destUF     = $dest['CD_UF'];
            $CEP        = str_replace(array('.',',','/','-'),array('','','',''),$dest['NR_CEP']);
            $cPais      = '1058';
            $xPais      = 'BRASIL';
            $fone       = str_replace(array('.',',','/','-','(',')'),array('','','','','',''),$dest['DS_FONE1']);

            if ( $mod == '55' ) {
                $resp = $nfe->tagenderDest($xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $destUF, $CEP, $cPais, $xPais, $fone);
            }

            //Passar para o array DB
            $nfeGe['Dest_xLgr']         = utf8_decode($xLgr);
            $nfeGe['Dest_nro']          = $nro;
            $nfeGe['Dest_xCpl']         = $xCpl;
            $nfeGe['Dest_xBairro']      = utf8_decode($xBairro);
            $nfeGe['Dest_cMun']         = $cMun;
            $nfeGe['Dest_xMun']         = utf8_decode($xMun);
            $nfeGe['Dest_UF']           = $UF;
            $nfeGe['Dest_CEP']          = $CEP;
            $nfeGe['Dest_fone']         = $fone;
            $nfeGe['Dest_CD_CIDADE']    = $dest['CD_CIDADE'];

        } // end foreach  destinatarios

        //Identificação do local de retirada (se diferente do emitente)
            //$CNPJ = '12345678901234';
            //$CPF = '';
            //$xLgr = 'Rua Vanish';
            //$nro = '000';
            //$xCpl = 'Ghost';
            //$xBairro = 'Assombrado';
            //$cMun = '3509502';
            //$xMun = 'Campinas';
            //$UF = 'SP';
            //$resp = $nfe->tagretirada($CNPJ, $CPF, $xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $UF);

        //Identificação do local de Entrega (se diferente do destinatário)
            //$CNPJ = '12345678901234';
            //$CPF = '';
            //$xLgr = 'Viela Mixuruca';
            //$nro = '2';
            //$xCpl = 'Quabrada do malandro';
            //$xBairro = 'Favela Mau Olhado';
            //$cMun = '3509502';
            //$xMun = 'Campinas';
            //$UF = 'SP';
            //$resp = $nfe->tagentrega($CNPJ, $CPF, $xLgr, $nro, $xCpl, $xBairro, $cMun, $xMun, $UF);

        //Identificação dos autorizados para fazer o download da NFe (somente versão 3.1)
        //$aAut = array('11111111111111','2222222','33333333333333');
            //foreach ($aAut as $aut) {
            //    if (strlen($aut) == 14) {
            //        $resp = $nfe->tagautXML($aut);
            //    } else {
            //        $resp = $nfe->tagautXML('', $aut);
            //    }
            //}

        //Recupera lista de mercadoria da tabela
        $cdsMercadoria = $post->get('cdMercadoria');
        $i = 1;
        //produtos
        foreach ($cdsMercadoria as $cdMercadoria) {
            //Buscar dados da mercadoria
            $statement = $dbAdapter->query("SELECT TOP 1 M.* , PLP.VL_PRECO_VENDA
											FROM TB_MERCADORIA AS M
												LEFT JOIN RL_PRAZO_LIVRO_PRECOS AS PLP ON M.CD_MERCADORIA = PLP.CD_MERCADORIA
													AND PLP.CD_LIVRO = 1 AND PLP.CD_PRAZO = 1
											WHERE		M.CD_MERCADORIA = ?");
            $results = $statement->execute(array($cdMercadoria));
            $rowResult = $results->current();
            //$precoPromocao = $rowResult["VL_PRECO_VENDA"];

            if( $bInterna ){
                $sCFOP  = $rowResult['DS_CFOP_INTERNO'];
                $picms  = $rowResult['NR_PERCENTUAL_ICMS_INTERNO'];
            }else{
                $sCFOP  = $rowResult['DS_CFOP_EXTERNO'];
                $picms  = $rowResult['NR_PERCENTUAL_ICMS_EXTERNO'];
            }

            $qtdVendida   = $post->get('qtdVendida-'.$cdMercadoria);
            $dsMercadoria = $post->get('ds_mercadoria-'.$cdMercadoria);
            $vlPrecoVenda = $post->get('vl_preco_unitario-'.$cdMercadoria);
            $vlDesconto   = $post->get('vl_preco_desconto-'.$cdMercadoria);

            if (empty($vlDesconto)) { //fix, se o preço com desconto estiver vazio, assume o valor de venda
                $vlDesconto = $vlPrecoVenda;
            }

            //Quando for emitida uma NFC-e em Homologação e a Descrição do primeiro item (xProd)
            //for diferente de "NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL",
            //será retornado a rejeição "373 - Descrição do primeiro item diferente de NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL".
            $xProdNfceHomologacao = "NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL";

            //Montar array da mercadoria
            $aP[] = array(
                'nItem'		=> $i,
                'cProd'     => $cdMercadoria,
                //GTIN (Global Trade Item Number) do produto, antigo código EAN ou código de barras
                'cEAN'      => (empty($rowResult["CD_BARRAS"]) ? 'SEM GTIN' : $rowResult["CD_BARRAS"]),
                'xProd'     => (($mod == '65') && ($tpAmb == '2')) ? $xProdNfceHomologacao : $dsMercadoria,
                'NCM'       => $rowResult["DS_NCM"],
                'NVE'       => "",
                'CEST'      => $rowResult["CEST"],
                'EXTIPI'    => '',
                'CFOP'      => ($copiarCFOP) ?  $post->get('cfop') : $sCFOP,
                'uCom'      => $rowResult["CD_UNIDADE_VENDA"],
                'qCom'      => number_format( $qtdVendida, 2, '.', ''),
                'vUnCom'    => number_format( $vlPrecoVenda, 2, '.', ''),
                'vProd'     => number_format( $vlPrecoVenda * $qtdVendida, 2, '.', '' ),
                // GTIN (Global Trade Item Number) da unidade tributável, antigo código EAN ou código de barras
                'cEANTrib'  => (empty($rowResult["CD_BARRAS"]) ? 'SEM GTIN' : $rowResult["CD_BARRAS"]),
                'uTrib'     => $rowResult["CD_UNIDADE_VENDA"],
                'qTrib'     => (int) $qtdVendida,
                'vUnTrib'   => number_format( $vlPrecoVenda,2, '.', ''),
                'vFrete'    => '',
                'vSeg'      => '',
                'vDesc'     => number_format( (($vlPrecoVenda - $vlDesconto) * (int)$qtdVendida),2, '.', ''),
                'vOutro'    => '',
                'indTot'    => '1',
                'xPed'      => '1',
                'nItemPed'  => $i,
                'nFCI'      => ''
            );

            //Serviço
            if ( $rowResult['ST_SERVICO'] == 'S' ) {
                $issqn[] = array(
                    'nItem' 		=> $i,
                    'vBC' 			=> ( $zeraBC != 'S' ? number_format(  $vlDesconto * $qtdVendida, 2, '.', ''): '0.00' ),
                    'vAliq' 		=> ( $zeraBC != 'S' ? number_format( $rowResult['VL_ISS'], 2, '.', '' ) : '0.00' ),
                    'vISSQN' 		=> ( $zeraBC != 'S' ? number_format( ( ( $vlDesconto * $qtdVendida ) * $rowResult['VL_ISS'] )/100, 2, '.', '') : '0.00' ),
                    'cMunFG' 		=> $cMunicipio,
                    'CLISTSERV' 	=> substr($rowResult['CLISTSERV'], 0, 2) . '.' . substr($rowResult['CLISTSERV'], 2 ),
                    'vDeducao' 		=> '', //Verificar
                    'vOutro' 		=> '', //Verificar
                    'vDescIncond' 	=> '',
                    'vDescCond' 	=> '',
                    'vISSRet' 		=> ( $rowResult['VL_RET_ISS'] > 0 ? number_format( ( ( $vlDesconto * $qtdVendida ) * $rowResult['VL_RET_ISS'] )/100, 2, '.', '') : '' ),
                    'indISS' 		=> $rowResult['ISSQN_indISS'],
                    'cServico' 		=> '',//Verificar
                    'cMun' 			=> '',//Verificar
                    'cPais' 		=> '',//Verificar
                    'nProcesso' 	=> '',//Verificar
                    'indIncentivo' 	=> $rowResult['ISSQN_indIncentivo']
                );
                $totalServ  = $totalServ + ( $vlPrecoVenda * $qtdVendida );

                $nfeMercadoria[] = array(
                    'infNFE'			=> $nr_nota,
                    'NR_PEDIDO'         => $nfeGe['NR_PEDIDO'],
                    'CD_MERCADORIA'     => $cdMercadoria,
                    'nItem'				=> $i,
                    'CD_LOJA'			=> '1',
                    'xProd'				=> $dsMercadoria,
                    'qCom'      		=> number_format( $qtdVendida, 2, '.', ''),
                    'vUnCom'    		=> number_format( $vlPrecoVenda, 4, '.', ''),
                    'vProd'    	 		=> number_format( $vlDesconto * $qtdVendida, 2, '.', '' ),
                    'vDesc'             => number_format( $vlDesconto, 2, '.', '' ), //valor liquido
                    'ISSQN_cMunFG'		=> $cMunicipio
                );
            } else {
               // Mercadoria
                $nfeMercadoria[] = array(
                    'infNFE'			=> $nr_nota,
                    'NR_PEDIDO'         => $nfeGe['NR_PEDIDO'],
                    'CD_MERCADORIA'     => $cdMercadoria,
                    'nItem'				=> $i,
                    'CD_LOJA'			=> '1',
                    'xProd'				=> $dsMercadoria,
                    'qCom'      		=> number_format( $qtdVendida, 2, '.', ''),
                    'vUnCom'    		=> number_format( $vlPrecoVenda, 4, '.', ''),
                    'vProd'    	 		=> number_format( $vlDesconto * $qtdVendida, 2, '.', '' ),
                    'vDesc'             => number_format( $vlDesconto, 2, '.', '' )                     //valor liquido
                );
                $totalProd  = $totalProd + ( $vlPrecoVenda * $qtdVendida );  //total sem descontos

                //montar array do ICMS de acordo com Situação Tributaria
                $cst = $rowResult['ICMS_CST'];
                $BaseCalculo = ( $zeraBC != 'S' ? number_format( $vlDesconto * $qtdVendida, 2, '.', '') : '0.00' );
                $ValorICMS   = ( $zeraBC != 'S' ? number_format( ( ( $vlDesconto * $qtdVendida ) * $picms)/100, 2, '.', '') : '0.00' );

                if ( $regime == '1' ) {
                    $icms[] = array(
                        'nItem' 	=> $i,
                        'orig'		=> $rowResult['ICMS_Orig'],
                        'CSOSN'		=> $rowResult['DS_CSOSN']
                    );
                } else {
                    if ($cst == '00') {
                        $icms[] = array(
                            'nItem' 	=> $i,
                            'orig'		=> $rowResult['ICMS_Orig'],
                            'cst'		=> '00',
                            'modBC'		=> $rowResult['ICMS_modBC'],
                            'vBC'		=> $BaseCalculo,
                            'pICMS'		=> number_format( $picms, 2, '.', ''),
                            'vICMS'		=> $ValorICMS
                        );
                    } else if($cst == '10') {
                        $icms[] = array(
                            'nItem' 	=> $i,
                            'orig'		=> $rowResult['ICMS_Orig'],
                            'cst'		=> '10',
                            'modBC'		=> $rowResult['ICMS_modBC'],
                            'vBC'		=> $BaseCalculo,
                            'pICMS'		=> number_format( $picms, 2, '.', ''),
                            'vICMS'		=> $ValorICMS,
                            'modBCST'	=> '4', //VERIFICAR
                            'pMVAST'	=> $rowResult['NR_MVA'],
                            'pRedBCST'	=> '', //Verificar
                            'vBCST'		=> '', //Verificar
                            'pICMSST'	=> '', //Verificar
                            'vICMSST'	=> ''  //Verificar
                        );
                    }else if($cst == '20'){
                        $icms[] = array(
                            'nItem' 	=> $i,
                            'orig'		=> $rowResult['ICMS_Orig'],
                            'cst'		=> '20',
                            'modBC'		=> $rowResult['ICMS_modBC'],
                            'vBC'		=> $BaseCalculo,
                            'pICMS'		=> number_format( $picms, 2, '.', ''),
                            'vICMS'		=> $ValorICMS,
                            'vICMSDeson'=> $rowResult['ICMS_vICMSDeson'],//Verificar
                            'motDesICMS'=> $rowResult['ICMS_motDesICMS'] //Verificar
                        );
                    }else if($cst == '30'){
                        $icms[] = array(
                            'nItem' 	=> $i,
                            'orig'		=> $rowResult['ICMS_Orig'],
                            'cst'		=> '30',
                            'modBCST'	=> '4', //VERIFICAR
                            'pMVAST'	=> $rowResult['NR_MVA'],
                            'pRedBCST'	=> '', //Verificar
                            'vBCST'		=> '', //Verificar
                            'pICMSST'	=> '', //Verificar
                            'vICMSST'	=> '', //Verificar
                            'pICMSST'	=> '', //Verificar
                            'vICMSST'	=> '' //Verificar
                        );
                    }else if($cst == '40'){
                        $icms[] = array(
                            'nItem' 	=> $i,
                            'orig'		=> $rowResult['ICMS_Orig'],
                            'cst'		=> '40',
                            'vICMSDeson'=> $rowResult['ICMS_vICMSDeson'],//Verificar
                            'motDesICMS'=> $rowResult['ICMS_motDesICMS'] //Verificar
                        );
                    }else if($cst == '51'){
                        $icms[] = array(
                            'nItem' 	=> $i,
                            'orig'		=> $rowResult['ICMS_Orig'],
                            'cst'		=> '51',
                            'modBC'		=> $rowResult['ICMS_modBC'],
                            'pRedBC'	=> '', //Verificar
                            'vBC'		=> $BaseCalculo,
                            'pICMS'		=> number_format( $picms, 2, '.', ''),
                            'vICMSOp'	=> $ValorICMS,
                            'vICMS'		=> $ValorICMS
                        );
                    }else if($cst == '60'){

                    }else if($cst == '70'){

                    }else if($cst == '90'){ //Tributacao ICMS : outros

                        $icms[] = array(
                            'nItem' 	=> $i,
                            'orig'		=> $rowResult['ICMS_Orig'],
                            'cst'		=> '51',
                            //'modBC'		=> $rowResult['ICMS_modBC'],
                            //'vBC'		=> $BaseCalculo,
                            //'pICMS'		=> number_format( $picms, 2, '.', ''),
                            //'vICMS'		=> $ValorICMS
                        );
                    }
                }

                //Preencher array IPI
                $nr_ipi = number_format( $rowResult['NR_IPI'], 2, '.', '');
                if( $nr_ipi > 0 ){
                    $ipi[] = array(
                        'nItem' 	=> $i,
                        'cst' 		=> (trim($rowResult['IPI_CST']) == '') ? '' : trim($rowResult['IPI_CST']) ,
                        'clEnq'	 	=> '',
                        'cnpjProd'	=> '',
                        'cSelo'		=> '',
                        'qSelo'		=> '',
                        'cEnq'		=> '',
                        'vBC'		=> number_format( $vlDesconto * $qtdVendida, 2, '.', ''),
                        'pIPI'		=> $nr_ipi,
                        'qUnid'		=> '',
                        'vUnid'		=> '',
                        'vIPI'		=> number_format( ((( $vlDesconto * $qtdVendida ) * $nr_ipi) / 100), 2, '.', '')
                    );
                }
                //Preencher array II

            }
            //Preencher array PIS
            $pisCST = $rowResult['PIS_CST'];
            $BaseCalculoPIS = ( $zeraBC != 'S' ? number_format( $vlDesconto * $qtdVendida, 2, '.', '') : '0.00' );
            $ValorPIS       = ( $zeraBC != 'S' ? number_format( ( ( $vlDesconto * $qtdVendida ) * $pPIS ) / 100, 2, '.', '') : '0.00');

            if( $pisCST == "01" || $pisCST == "02" ) {
                $pis[] = array(
                    'nItem'		=> $i,
                    'cst'		=> $pisCST,
                    'vBC'		=> $BaseCalculoPIS,
                    'pPIS'		=> number_format( $pPIS, 2 ),
                    'vPIS'		=> $ValorPIS,
                    'qBCProd'	=> '',
                    'vAliqProd'	=> ''
                );
               if ( $rowResult['ST_SERVICO'] == 'S') {
                   $totalPISISSQN = $totalPISISSQN + $ValorPIS ;
               } else {
                   $totalPISICMS  = $totalPISICMS + $ValorPIS;
               }
            } else if(  $pisCST == "03" ){
                $pis[] = array(
                    'nItem'		=> $i,
                    'cst'		=> $pisCST,
                    'qBCProd'	=> '',
                    'vAliqProd'	=> ''
                );
            }else if(
                $pisCST == "04" ||
                $pisCST == "05" ||
                $pisCST == "06" ||
                $pisCST == "07" ||
                $pisCST == "08" ||
                $pisCST == "09" ){
                $pis[] = array(
                    'nItem'		=> $i,
                    'cst'		=> $pisCST
                );
            }
            //Preencher array COFINS
            $cofinsCST = $rowResult['COFINS_CST'];
            $BaseCalculoCofins = ( $zeraBC != 'S' ? number_format( $vlDesconto * $qtdVendida, 2, '.', '') : '0.00' );
            $ValorCofins       = ( $zeraBC != 'S' ? number_format( ( ( $vlDesconto * $qtdVendida ) * $pCOFINS ) / 100, 2, '.', '' ) : '0.00' );
            if( $cofinsCST == "01" ||
                $cofinsCST == "01" ){
                $cofins[] = array(
                    'nItem' 	=> $i,
                    'cst' 		=> $cofinsCST,
                    'vBC' 		=> $BaseCalculoCofins,
                    'pCOFINS'	=> number_format( $pCOFINS,2, '.', ''),
                    'vCOFINS' 	=> $ValorCofins,
                    'qBCProd' 	=> '',
                    'vAliqProd' => ''
                );
                ( $rowResult['ST_SERVICO'] == 'S' ? $totalCOFINSISSQN = $totalCOFINSISSQN + $ValorCofins : $totalCOFINSICMS = $totalCOFINSICMS + $ValorCofins );
            }else if( 	$cofinsCST == "04" ||
                $cofinsCST == "05" ||
                $cofinsCST == "06" ||
                $cofinsCST == "07" ||
                $cofinsCST == "08" ||
                $cofinsCST == "09" ){
                $cofins[] = array(
                    'nItem' 	=> $i,
                    'cst' 		=> $cofinsCST
                );
            }

            //Incrementa nItem
            $i = $i + 1;
        }

        //TAG Prod
        foreach ( @$aP as $prod) {
            $nItem    	= $prod['nItem'];
            $cProd    	= $prod['cProd'];
            $cEAN    	= $prod['cEAN'];
            $xProd    	= $prod['xProd'];
            $NCM      	= $prod['NCM'];
            $NVE      	= $prod['NVE'];
            $CEST     	= $prod['CEST'];
            $EXTIPI   	= $prod['EXTIPI'];
            $CFOP     	= $prod['CFOP'];
            $uCom     	= $prod['uCom'];
            $qCom     	= (int)$prod['qCom'];
            $vUnCom   	= $prod['vUnCom'];
            $vProd    	= $prod['vProd'];
            $cEANTrib 	= $prod['cEANTrib'];
            $uTrib    	= $prod['uTrib'];
            $qTrib	   	= $prod['qTrib'];
            $vUnTrib 	= $prod['vUnTrib'];
            $vFrete 	= $prod['vFrete'];
            $vSeg 		= $prod['vSeg'];
            $vDesc 		= ((float)$prod['vDesc'] > 0.0) ? $prod['vDesc'] : '';
            $vOutro 	= $prod['vOutro'];
            $indTot		= $prod['indTot'];
            $xPed 		= $prod['xPed'];
            $nItemPed	= $prod['nItemPed'];
            $nFCI 		= $prod['nFCI'];

            $resp                 = $nfe->tagprod( $nItem, $cProd, $cEAN, $xProd, $NCM, $NVE, $CEST, $EXTIPI, $CFOP, $uCom, $qCom, $vUnCom, $vProd, $cEANTrib, $uTrib, $qTrib, $vUnTrib, $vFrete, $vSeg, $vDesc, $vOutro, $indTot, $xPed, $nItemPed, $nFCI );

            //calculos totais da nota
            //( $rowResult['ST_SERVICO'] == 'S' )
            $totalNota        = $totalNota + ($vProd - $vDesc);         // V. TOTAL DA NOTA
            $totalNotaSemDesc = $totalNotaSemDesc + ($vUnCom * $qCom);  // V. TOTAL PRODUTOS
            $totalDesconto    = $totalDesconto + $vDesc;                // DESCONTO

            //imposto
            $nItem      = $prod['nItem'];
            $vTotTrib   = ''; //Verificar
            $resp       = $nfe->tagimposto($nItem, $vTotTrib);
        }

        //ICMS
        if( @$icms ){
            if( $regime != '1' ){
                foreach( $icms as $ic) {
                    $nItem 		= $ic['nItem'];
                    $orig		= $ic['orig'];
                    $cst 		= $ic['cst'];
                    $modBC		= $ic['modBC'];
                    $pRedBC		= $ic['pRedBC'];
                    $vBC 		= $ic['vBC'];
                    $pICMS 		= $ic['pICMS'];
                    $vICMS 		= $ic['vICMS'];
                    $vICMSDeson	= $ic['vICMSDeson'];
                    $motDesICMS = $ic['motDesICMS'];
                    $modBCST 	= $ic['modBCST'];
                    $pMVAST 	= $ic['pMVAST'];
                    $pRedBCST 	= $ic['pRedBCST'];
                    $vBCST 		= $ic['vBCST'];
                    $pICMSST 	= $ic['pICMSST'];
                    $vICMSST 	= $ic['vICMSST'];
                    $pDif 		= $ic['pDif'];
                    $vICMSDif 	= $ic['vICMSDif'];
                    $vICMSOp 	= $ic['vICMSOp'];
                    $vBCSTRet 	= $ic['vBCSTRet'];
                    $vICMSSTRet = $ic['vICMSSTRet'];

                    $resp = $nfe->tagICMS($nItem, $orig, $cst, $modBC, $pRedBC, $vBC, $pICMS, $vICMS, $vICMSDeson, $motDesICMS, $modBCST, $pMVAST, $pRedBCST, $vBCST, $pICMSST, $vICMSST, $pDif, $vICMSDif, $vICMSOp, $vBCSTRet, $vICMSSTRet);

                    //total ICMS
                    $totalBCICMS    = $totalBCICMS  + $vBC;
                    $totalICMS      = $totalICMS    + $vICMS;
                }
            }else{
                foreach( $icms as $ic) {
                    $nItem 		= $ic['nItem'];
                    $orig		= $ic['orig'];
                    $csosn		= $ic['CSOSN'];
                    $resp = $nfe->tagICMSSN($nItem, $orig, $csosn);
                }
            }
        }

        //Tag ISSQN
        if( @$issqn ){
            foreach( @$issqn as $iss){
                $nItem 			= $iss['nItem'];
                $vBC 			= $iss['vBC'];
                $vAliq 			= $iss['vAliq'];
                $vISSQN 		= $iss['vISSQN'];
                $cMunFG 		= $iss['cMunFG'];
                $cListServ 		= $iss['CLISTSERV'];
                $vDeducao 		= $iss['vDeducao'];
                $vOutro 		= $iss['vOutro'];
                $vDescIncond 	= $iss['vDescIncond'];
                $vDescCond 		= $iss['vDescCond'];
                $vISSRet 		= $iss['vISSRet'];
                $indISS 		= $iss['indISS'];
                $cServico 		= $iss['cServico'];
                $cMun 			= $iss['cMun'];
                $cPais 			= $iss['cPais'];
                $nProcesso 		= $iss['nProcesso'];
                $indIncentivo 	= $iss['indIncentivo'];
                $resp = $nfe->tagISSQN($nItem, $vBC, $vAliq, $vISSQN, $cMunFG, $cListServ, $vDeducao, $vOutro, $vDescIncond, $vDescCond, $vISSRet, $indISS, $cServico, $cMun, $cPais, $nProcesso, $indIncentivo );
                $totalBcISSQN = $totalBcISSQN + $vBC;
                $totalISSQN = $totalISSQN + $vISSQN;
                $totalRetISS = $totalRetISS + $vISSRet;
            }
        }
        //TAG PIS
        if( @$pis ){

            foreach( @$pis as $pi){
                $nItem 		= $pi['nItem'];
                $cst 		= $pi['cst'];
                $vBC 		= $pi['vBC'];
                $pPIS 		= $pi['pPIS'];
                $vPIS 		= $pi['vPIS'];
                $qBCProd 	= $pi['qBCProd'];
                $vAliqProd 	= $pi['vAliqProd'];
                $resp = $nfe->tagPIS($nItem, $cst, $vBC, $pPIS, $vPIS, $qBCProd, $vAliqProd);
            }
        }

        //tag COFINS
        if( @$cofins ){
            foreach( @$cofins as $co ){
                $nItem 	 	= $co['nItem'];
                $cst 	 	= $co['cst'];
                $vBC 	 	= $co['vBC'];
                $pCOFINS 	= $co['pCOFINS'];
                $vCOFINS 	= $co['vCOFINS'];
                $qBCProd 	= $co['qBCProd'];
                $vAliqProd 	= $co['vAliqProd'];
                $resp = $nfe->tagCOFINS($nItem, $cst, $vBC, $pCOFINS, $vCOFINS, $qBCProd, $vAliqProd);
            }
        }

        //TAG IPI
        if( @$ipi ){
            $totalIPI= 0;
            foreach( @$ipi as $ip ){
                $nItem 		= $ip['nItem'];
                $cst 		= $ip['cst'];
                $clEnq 		= $ip['clEnq'];
                $cnpjProd 	= $ip['cnpjProd'];
                $cSelo 		= $ip['cSelo'];
                $qSelo 		= $ip['qSelo'];
                $cEnq 		= $ip['cEnq'];
                $vBC 		= $ip['vBC'];
                $pIPI 		= $ip['pIPI'];
                $qUnid 		= $ip['qUnid'];
                $vUnid 		= $ip['vUnid'];
                $vIPI 		= $ip['vIPI'];
                $resp = $nfe->tagIPI($nItem, $cst, $clEnq, $cnpjProd, $cSelo, $qSelo, $cEnq, $vBC, $pIPI, $qUnid, $vUnid, $vIPI);
                $totalIPI = $totalIPI + $vIPI;
            }
        }

        //total icms
        $vBC            = number_format($totalBCICMS,2, '.', '');
        $vICMS          = number_format($totalICMS,2, '.', '');
        $vICMSDeson     = '0';
        $vBCST          = '0';
        $vST            = '0';
        $vProd          = number_format($totalProd,2, '.', '');
        $vFrete         = '0';
        $vSeg           = '0';
        $vDesTotalIcms  = !empty($totalDesconto) ? number_format( $totalDesconto, 2, '.', '') : '0';
        $vII            = '0';
        $vIPI           = ( $totalIPI > 0 ? number_format( $totalIPI, 2, '.', '') : '0.00');
        $vPIS           = ( $totalPISICMS > 0 ? number_format($totalPISICMS,2, '.', '') : '0.00' );
        $vCOFINS        = ( $totalCOFINSICMS > 0 ? number_format($totalCOFINSICMS,2, '.', ''): '0.00');
        $vOutro         = '0';
        $vNF            = number_format($totalNota,2, '.', '');
        $vTotTrib       = '';

        $resp = $nfe->tagICMSTot($vBC, $vICMS, $vICMSDeson, $vBCST, $vST, $vProd, $vFrete, $vSeg, $vDesTotalIcms, $vII, $vIPI, $vPIS, $vCOFINS, $vOutro, $vNF, $vTotTrib);


        //*************************************************************
        //Grupo obrigatório para a NFC-e. Não informar para a NF-e.
        //$tPag = '03'; //01=Dinheiro 02=Cheque 03=Cartão de Crédito 04=Cartão de Débito 05=Crédito Loja 10=Vale Alimentação 11=Vale Refeição 12=Vale Presente 13=Vale Combustível 99=Outros
        //$vPag = '1452,33';
        //$resp = $nfe->tagpag($tPag, $vPag);

        //se a operação for com cartão de crédito essa informação é obrigatória
        //$CNPJ = '31551765000143'; //CNPJ da operadora de cartão
        //$tBand = '01'; //01=Visa 02=Mastercard 03=American Express 04=Sorocred 99=Outros
        //$cAut = 'AB254FC79001'; //número da autorização da tranzação
        //$resp = $nfe->tagcard($CNPJ, $tBand, $cAut);
        //**************************************************************

        if($finNFe == 4) { //Devolução/Retorno

            if (!empty($refNFe)) {
                $resp = $nfe->tagrefNFe($refNFe);
            }

            $tPag = '90'; //Sem Pagamento
            $vPag = '0.00';
            $resp = $nfe->tagpag($tPag,$vPag);

        } else if($finNFe == 3) { //Ajuste

            $tPag = '99'; //Outros
            $vPag = '0.00';
            $resp = $nfe->tagpag($tPag,$vPag);

        } else {
            $resp = $nfe->tagpag('01', $vNF);
        }


        //Passar para o array DB
        $nfeGe['ICMSTot_vBC']       = $vBC;
        $nfeGe['ICMSTot_vICMS']     = $vICMS;
        $nfeGe['ICMSTot_vBCST']     = $vBCST;
        $nfeGe['ICMSTot_vST']       = $vST;
        $nfeGe['ICMSTot_vProd']     = $vProd;
        $nfeGe['ICMSTot_vFrete']    = $vFrete;
        $nfeGe['ICMSTot_vSeg']      = $vSeg;
        $nfeGe['ICMSTot_vDesc']     = $vDesTotalIcms;
        $nfeGe['ICMSTot_vII']       = $vII;
        $nfeGe['ICMSTot_vIPI']      = $vIPI;
        $nfeGe['ICMSTot_vPIS']      = $vPIS;
        $nfeGe['ICMSTot_vCOFINS']   = $vCOFINS;
        $nfeGe['ICMSTot_vOutro']    = $vOutro;
        $nfeGe['ICMSTot_vNF']       = $vNF;


        //total ISSQNTot
        $vServ          = ( $totalServ != '0.00' ? number_format($totalServ,2, '.', '') : '' );
        $vBC            = ( $totalBcISSQN != '0.00' ? number_format($totalBcISSQN,2, '.', '') : '' );
        $vISS           = ( $totalISSQN != '0.00' ? number_format($totalISSQN,2, '.', '') : '' );
        $vPIS           = ( $totalPISISSQN != '0.00' ? number_format($totalPISISSQN,2, '.', '') : '' );
        $vCOFINS        = ( $totalCOFINSISSQN != '0.00' ? number_format($totalCOFINSISSQN,2, '.', '') : '' );
        $dCompet        = date('Y-m-d');
        $vDeducao       = '';
        $vOutro         = '';
        $vDescIncond    = '';
        $vDescCond      = '';
        $vISSRet        = ( $totalRetISS != '0.00' ? number_format($totalRetISS,2,'.','') : '' );
        $cRegTrib       = ( $totalServ != '0.00' ? $regime : '');

        if( $totalServ != '0.00' ) {
            $resp = $nfe->tagISSQNTot($vServ, $vBC, $vISS, $vPIS, $vCOFINS, $dCompet, $vDeducao, $vOutro, $vDescIncond, $vDescCond, $vISSRet, $cRegTrib );
        }

        //Passar para o array DB
        $nfeGe['ISSQNTot_vServ']    = $vServ;
        $nfeGe['ISSQNTot_vBC']      = $vBC;
        $nfeGe['ISSQNTot_vISS']     = $vISS;
        $nfeGe['ISSQNTot_vPIS']     = $vPIS;
        $nfeGe['ISSQNTot_vCOFINS']  = $vCOFINS;

        //frete
        $modFrete = '9'; //0=Por conta do emitente; 1=Por conta do destinatário/remetente; 2=Por conta de terceiros; 9 Sem frete
        $resp = $nfe->tagtransp($modFrete);

        //Passar para o array DB
        $nfeGe['trans_modFrete'] = $modFrete;

        //informações Adicionais
        //$infAdFisco = 'SAIDA COM SUSPENSAO DO IPI CONFORME ART 29 DA LEI 10.637';
        //$infCpl = '';
        //$resp = $nfe->taginfAdic($infAdFisco, $infCpl);

        if( $post['infadc'] != '' ){

            $infAdFisco      = '';
            $infCpl          = $post['infadc'];
            $infCpl          = str_replace(chr(13), ' ', $infCpl);
            $resp            = $nfe->taginfAdic($infAdFisco, $infCpl);
            $nfeGe['infCpl'] = $infCpl;
        }
        $nfeGe['infAdFisco'] = '';

        //observações DMED
        if( $post->get('nome_paciente') != '' ){
            $aObsC[] = array( array('DMED Paciente',$post->get('nome_paciente')));
            $nfeGe['DMED_NOME'] = $post->get('nome_paciente');
        }
        if( $post->get('cpf_paciente') != '' ){
            $aObsC[] = array( array('DMED CPF',$post->get('cpf_paciente')));
            $nfeGe['DMED_CPF'] = $post->get('cpf_paciente');
        }
        if( $post->get('nascimento_paciente') != '' ){
            $aObsC[] = array( array('DMED Nascimento',date('d/m/Y', strtotime( $post->get('nascimento_paciente')))) );
            $nfeGe['DMED_NASCIMENTO'] = $post->get('nascimento_paciente');
        }

        if( $aObsC['0'] ){
            foreach ($aObsC['0'] as $obs) {
                $xCampo = $obs[0];
                $xTexto = $obs[1];
                $resp = $nfe->tagobsCont($xCampo, $xTexto);
            }
        }

        //observações Retenção PIS
        if( $post->get('retPis') == '1' ){
            $aObsCPIS[] = array( array('Retenção PIS', 'Retenções: PIS alíquota '. number_format($post->get('retPis_aliq'),2,'.','') . ' = R$' . number_format($post->get('retPis_total'),2,'.','') ) );
            foreach ($aObsCPIS['0'] as $obs) {
                $xCampo = $obs[0];
                $xTexto = $obs[1];
                $resp = $nfe->tagobsCont($xCampo, $xTexto);
                $nfeGe['RET_Base_PIS'] = str_ireplace(",",".",$post->get('retPis_bc'));
                $nfeGe['RET_Aliq_PIS'] = str_ireplace(",",".",$post->get('retPis_aliq'));
            }
        }

        //observações Retenção COFINS
        if( $post->get('retCofins') == '1' ){
            $aObsCCofins[] =array( array('Retenção COFINS', 'Retenções: COFINS alíquota '. number_format($post->get('retCofins_aliq'),2,'.','') . ' = R$' . number_format($post->get('retCofins_total'),2,'.','')) );
            foreach ($aObsCCofins['0'] as $obs) {
                $xCampo = $obs[0];
                $xTexto = $obs[1];
                $resp = $nfe->tagobsCont($xCampo, $xTexto);
                $nfeGe['RET_Base_COFINS'] = str_ireplace(",",".",$post->get('retCofins_bc'));
                $nfeGe['RET_Aliq_COFINS'] = str_ireplace(",",".",$post->get('retCofins_aliq'));
            }
        }

        //observações Retenção CSLL
        if( $post->get('retCsll') == '1' ){
            $aObsCCSLL[] = array( array('Retenção CSLL', 'Retenções: CSLL alíquota '. number_format( $post->get('retCsll_aliq'),2,'.',''). ' = R$' . number_format($post->get('retCsll_total'),2,'.','')) );
            foreach ($aObsCCSLL['0'] as $obs) {
                $xCampo = $obs[0];
                $xTexto = $obs[1];
                $resp = $nfe->tagobsCont($xCampo, $xTexto);
                $nfeGe['RET_Base_CSLL'] = str_ireplace(",",".",$post->get('retCsll_bc'));
                $nfeGe['RET_Aliq_CSLL'] = str_ireplace(",",".",$post->get('retCsll_aliq'));
            }
        }

        //observações Retenção IRRF
        if( $post->get('retIrrf') == '1' ){
            $aObsCIRRF[] = array( array('Retenção IRRF', 'Retenções: IRRF alíquota '. number_format($post->get('retIrrf_aliq'),2,'.','') . ' = R$' . number_format($post->get('retIrrf_total'),2,'.','')) );
            foreach ($aObsCIRRF['0'] as $obs) {
                $xCampo = $obs[0];
                $xTexto = $obs[1];
                $resp = $nfe->tagobsCont($xCampo, $xTexto);
                $nfeGe['RET_Base_IRRF'] = str_ireplace(",",".",$post->get('retIrrf_bc'));
                $nfeGe['RET_Aliq_IRRF'] = str_ireplace(",",".",$post->get('retIrrf_aliq'));
            }
        }

        //observações Retenção Previdencia
        if( $post->get('retPrev') == '1' ){
            $aObsCPrev[] = array( array('Retenção Previdencia', 'Retenções: Previdencia alíquota '. number_format($post->get('retPrev_aliq'),2,'.','') . ' = R$' . number_format($post->get('retPrev_total'),2,'.','')) );
            foreach ($aObsCPrev['0'] as $obs) {
                $xCampo = $obs[0];
                $xTexto = $obs[1];
                $resp = $nfe->tagobsCont($xCampo, $xTexto);
                $nfeGe['RET_Base_PREV'] = str_ireplace(",",".",$post->get('retPrev_bc'));
                $nfeGe['RET_Aliq_PREV'] = str_ireplace(",",".",$post->get('retPrev_aliq'));
            }
        }

        if( $pTributos > 0 ){
            $aObsCTrib[] = array( array('Imposto', 'Percentual de Imposto Federal Aproximado: R$' . number_format(( $totalNota * $pTributos ) / 100,2,'.','') )) ;
            foreach ($aObsCTrib['0'] as $obs) {
                $xCampo = $obs[0];
                $xTexto = $obs[1];
                $resp = $nfe->tagobsCont($xCampo, $xTexto);
            }
        }

        if( $pTributosEst > 0 ){
            $aObsCTribEst[] = array( array('Imposto', 'Percentual de Imposto Estadual Aproximado: R$' . number_format(( $totalNota * $pTributosEst ) / 100,2,'.','') ) );
            foreach ($aObsCTribEst['0'] as $obs) {
                $xCampo = $obs[0];
                $xTexto = $obs[1];
                $resp = $nfe->tagobsCont($xCampo, $xTexto);
            }
        }

        if( $pTributosMun > 0 ){
            $aObsCTribMun[] = array( array('Imposto', 'Percentual de Imposto Municipal Aproximado: R$' . number_format(( $totalNota * $pTributosMun ) / 100,2,'.','') ) ) ;
            foreach ($aObsCTribMun['0'] as $obs) {
                $xCampo = $obs[0];
                $xTexto = $obs[1];
                $resp = $nfe->tagobsCont($xCampo, $xTexto);
            }
        }

        //Retenções tributarias
        $vRetPIS = '';
        $vRetCOFINS = '';
        $vRetCSLL = '';
        $vBCIRRF = '';
        $vIRRF = '';
        $vBCRetPrev = '';
        $vRetPrev = '';
        if( $post->get('retPis') == '1' )
            $vRetPIS = number_format($post->get('retPis_total'),2,'.','');
        if( $post->get('retCofins') == '1' )
            $vRetCOFINS = number_format($post->get('retCofins_total'),2,'.','');
        if( $post->get('retCsll') == '1' )
            $vRetCSLL = number_format($post->get('retCsll_total'),2,'.','');
        if( $post->get('retIrrf') == '1' ){
            $vBCIRRF = number_format($post->get('retIrrf_bc'),2,'.','');
            $vIRRF = number_format($post->get('retIrrf_total'),2,'.','');
        }
        if( $post->get('retPrev') == '1' ){
            $vBCRetPrev = number_format($post->get('retPrev_bc'),2,'.','');
            $vRetPrev   = number_format($post->get('retPrev_total'),2,'.','');
        }
        $resp = $nfe->tagretTrib($vRetPIS, $vRetCOFINS, $vRetCSLL, $vBCIRRF, $vIRRF, $vBCRetPrev, $vRetPrev );


        $nfeReferenciadaGe= array();
        $nfeReferenciadaGe['CD_LOJA']              = $nfeGe['CD_LOJA'];
        $nfeReferenciadaGe['NR_PEDIDO']            = $nfeGe['NR_PEDIDO'];
        $nfeReferenciadaGe['infNFE']               = $nfeGe['infNFE'];
        $nfeReferenciadaGe['refNFe']               = $refNFe;

        if ( $bReenvia ) {
            $table->atualiza_nota($nfeGe['infNFE'], $nfeGe);

            $table->limpa_mercadorias($nfeGe['infNFE']);

            foreach( $nfeMercadoria as $a){
                $table->insere_mercadorias($a);
            }

            //Salva NFE Referenciada no banco
            $table->limpa_nota_referenciada($nfeGe['infNFE']);
            if ($nfeGe['finNFe'] == 4) { //devolução
                $table->insere_nota_referenciada($nfeReferenciadaGe);
            }

        } else {
            //Salva NFE no banco
            $table->atualiza_nextId($nfeGe['infNFE']);

            $table->insere_nota($nfeGe);

            foreach( $nfeMercadoria as $a){
                $table->insere_mercadorias($a);
            }

            //Salva NFE Referenciada no banco
            if ($nfeGe['finNFe'] == 4) { //devolução
                $table->insere_nota_referenciada($nfeReferenciadaGe);
            }
        }

        //monta a NFe e retorna na tela
        $resp           = $nfe->montaNFe();

        //Array que retornara as informações para os metodos
        $arrayViewModel = array();

        if ( $resp ) {

            $xml        = $nfe->getXML();
            $filename   =  getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\saidas\\'.$chave.'-nfe.xml';

            file_put_contents($filename, $xml);
            chmod($filename, 0777);

            if (isset($_POST['save'])) {
                //return $this->redirect()->toUrl("/nota/lista");
                $arrayViewModel = array('redirect'=>'/nota/lista');
            } else {
                $erro       = $this->assinaNFe($chave,$mod);

                //$viewModel = new ViewModel();
                //$viewModel->setTerminal(false);

                if ( $erro != '' ) {

                    //$viewModel->setVariable('erro', $erro);
                    //$viewModel->setVariable('chave', $chave);
                    $arrayViewModel = array('erro'=> $erro,
                                            'chave'=> $chave);

                } else {

                    //Envia nota para SEFAZ, pelo menos tenta rsrs
                    $retorno = $this->enviaNFe($chave, $tpAmb, $mod);

                    //Se tiver protocolo é porque enviou
                    if ( $retorno['protcoloco'] != '' ) {
                        if ( $remEmail != '' && $email != '' ) {
                            $arrayMail = array( $bkpMail, $remEmail, $email );
                        } elseif( $remEmail != '' ) {
                            $arrayMail = array( $bkpMail, $remEmail );
                        } elseif( $email != '' ) {
                            $arrayMail = array( $bkpMail, $email );
                        }

                        $array['DS_PROTOCOLO']      = $retorno['prot']['0']['nProt'];
                        $array['DT_PROTOCOLO_DATA'] = date('Ymd');
                        //$this->addProt($chave, $retorno['idLote']);

                        //$viewModel->setVariable('retorno', $retorno );
                        //$viewModel->setVariable('data', date('Ym', strtotime($nfeGe['dEmi'])) );
                        $arrayViewModel = array('retorno'=>$retorno,
                                                 'data'=>date('Ym', strtotime($nfeGe['dEmi'])));

                        //Atualiza Protocolo da NFE
                        $table->atualiza_nota($nfeGe['infNFE'], $array);

                        //Envia por email
                        $retornoMail = $this->enviaMail($chave, $arrayMail, date('Ym', strtotime($nfeGe['dEmi'])),$mod);
                        $arrayViewModel['retornoMail']      = $retornoMail;
                        $arrayViewModel['emailsEnviados']   = $arrayMail;
                    } else if(isset($retorno['xMotivo'])) {
                        $arrayViewModel['erro']  = $retorno['cStat']." : ".$retorno['xMotivo'];
                        $arrayViewModel['chave'] = $chave;
                    }   else {
                        //$viewModel->setVariable('erro', $retorno['prot']['0']['xMotivo']);
                        $arrayViewModel['erro']  = $retorno['prot']['0']['xMotivo'];
                        $arrayViewModel['chave'] = $retorno['prot']['0']['chNFe'];
                    }
                    $arrayViewModel['infNFE'] = $nfeGe['infNFE'];
                    $arrayViewModel['chave']  = $chave;
                    $arrayViewModel['mod']  = $mod;
                }
            }
            //return $viewModel;

        } else {
            //Força a apresentção do erros quando a  montagem da NFE não funcionou
            //header('Content-type: text/html; charset=UTF-8');
            $erroStackTrace = "";
            foreach ($nfe->erros as $err) {
                $erroStackTrace .= 'tag: &lt;' . $err['tag'] . '&gt; ---- ' . $err['desc'] . '<br>';
            }
            $arrayViewModel['erro'] = $erroStackTrace;
        }

        return $arrayViewModel;
    }


    /*
    * Criar uma nota NFE a partir de um pedido recebido no caixa
     *
     * @throws \Exception
     */
    public function geraNfePedidoAction(){

        // session
        $session    = new Container("orangeSessionContainer");

        //variaveis iniciais
        $erro           = "";
        $motivo         = "";
        $chave          = "";
        $cfop           = "";
        $xNatOp         = "";
        $arrayProdutos  = array();
        $retorno        = array();

        //get request
        $nrPedido   = $this->params()->fromQuery('nrPedido');
        $post       = $this->getRequest()->getPost();

        // get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');

        try {
            if ((int)$nrPedido > 0) {

                if (!is_numeric($nrPedido)) {
                    throw  new \Exception("Pedido nº{$nrPedido} inválido!",400);
                }

                //verifica se existe realmente o pedido
                $pedidoTable     = new PedidoTable($dbAdapter);
                $pedidoExistente = $pedidoTable->recuperaPedidoPorNumero($nrPedido,false);
                if (!$pedidoExistente) {
                    throw  new \Exception("Pedido nº{$nrPedido} não foi encontrado!",400);
                }

                //verifica se ja existe nfe gerada para este pedido
                $notaTable     = new NotaTable($dbAdapter);
                $notaExistente = $notaTable->recuperaNotaPorNumeroPedido($nrPedido,false);
                //if($notaExistente) {
                    //throw  new \Exception("A nota nº{$notaExistente['infNFE']} já está associada a este pedido nº{$nrPedido}!",400);
                //}

                //Dados da NFe (configuração padrão)
                $dadosConfiguracaoNF =  $notaTable->getConfig($session->cdLoja);

                //Recuperar dados dos produtos incluidos dentro pedido
                $produtoExistente    =   $pedidoTable->recuperaMercadoriasNumeroPedido($nrPedido);

                //Recuperar dados do cliente do pedido
                $clienteExistente    =   $pedidoTable->recuperaClienteNumeroPedido($nrPedido);

                //Impostos
                // valor base igual (bruto - descontos = liquido)
                $baseCalculoImpostos = !empty($pedidoExistente['VL_TOTAL_LIQUIDO']) ? $pedidoExistente['VL_TOTAL_LIQUIDO'] : $pedidoExistente['VL_TOTAL_BRUTO'];
                $aliquotaPis        = (float)$dadosConfiguracaoNF['RETENCAO_pPIS'];
                $aliquotaCofins     = (float)$dadosConfiguracaoNF['RETENCAO_pCOFINS'];
                $aliquotaCsll       = (float)$dadosConfiguracaoNF['RETENCAO_pCSLL'];
                $aliquotaIrrf       = (float)$dadosConfiguracaoNF['RETENCAO_pIRRF'];
                $aliquotaPrev       = (float)$dadosConfiguracaoNF['RETENCAO_pPrevidencia'];
                $totalPis           = ((float)($baseCalculoImpostos * $aliquotaPis)     / 100);
                $totalCofins        = ((float)($baseCalculoImpostos * $aliquotaCofins)  / 100);
                $totalCsll          = ((float)($baseCalculoImpostos * $aliquotaCsll)    / 100);
                $totalIrrf          = ((float)($baseCalculoImpostos * $aliquotaIrrf)    / 100);
                $totalPrev          = ((float)($baseCalculoImpostos * $aliquotaPrev)    / 100);

                //Prepara um objeto Zend\Stdlib\Parameters
                $post->set('infNFE',($notaExistente ? $notaExistente['infNFE'] : ''));
                $post->set('nrPedido',$nrPedido);
                $post->set('DS_PROTOCOLO','');
                $post->set('copiarCFOP','N');
                $post->set('nome_paciente',null);
                $post->set('cpf_paciente',null);
                $post->set('nascimento_paciente',null);
                $post->set('codCliente',$pedidoExistente['CD_CLIENTE']);

                $post->set('destNome',$clienteExistente[0]['DS_NOME_RAZAO_SOCIAL']);
                $post->set('destCNPJ',$clienteExistente[0]['NR_CGC_CPF']);

                $post->set('mod',"55");  //55 nfe or 65 nfce
                $post->set('dhEmi',date("d-m-Y"));
                $post->set('indPag','0');
                $post->set('finNFe','1');
                $post->set('refNFe','');
                $post->set('infadc','Nota fiscal associada ao pedido de número '.$nrPedido);

                foreach ($produtoExistente as $prod) {

                    $codMercadoria      = $prod['CD_MERCADORIA'];
                    $dscMercadoria      = utf8_encode($prod['DS_MERCADORIA']);
                    $qtdVendida         = $prod['NR_QTDE_VENDIDA'];
                    $vlPrecoUnitario    = $prod['VL_TOTAL_BRUTO'];
                    $vlPrecoDesconto    = $prod['VL_TOTAL_LIQUIDO'];
                    $vlTotal            = $vlPrecoDesconto * (int) $qtdVendida ; // valor liquido * a quantidade
                    $cfop               = (empty($cfop) && !empty($prod['DS_CFOP_INTERNO'])) ? $prod['DS_CFOP_INTERNO'] : "";

                    if (empty($xNatOp)){
                        $xNatOp   = "";
                    }

                    $post->set('ds_mercadoria-'.$codMercadoria, $dscMercadoria);
                    $post->set('qtdVendida-'.$codMercadoria, $qtdVendida);
                    $post->set('vl_preco_unitario-'.$codMercadoria, $vlPrecoUnitario);
                    $post->set('vl_preco_desconto-'.$codMercadoria, $vlPrecoDesconto);
                    $post->set('vl_tot-'.$codMercadoria,$vlTotal);

                    array_push($arrayProdutos,$codMercadoria);
                }

                $post->set('cdMercadoria',$arrayProdutos);

                $cfopTable           =  new TabelaTable($dbAdapter);
                if (empty($cfop)) {
                    $cfop                =  $dadosConfiguracaoNF[0]['CD_NATUREZA_OPERACAO'];
                    $cfopResult          =  $cfopTable->getNaturezaOperacao($dadosConfiguracaoNF[0]['CD_NATUREZA_OPERACAO']);
                    $xNatOp              =  utf8_encode($cfopResult['DS_NATUREZA_OPERACAO']);
                } else {
                    $cfopResult          =  $cfopTable->getNaturezaOperacao($cfop);
                    $xNatOp              =  utf8_encode($cfopResult['DS_NATUREZA_OPERACAO']);
                }

                $post->set('cfop',$cfop);
                $post->set('natOp',$cfop);
                $post->set('xNatOp',$xNatOp);

                $post->set('retPis_bc',$baseCalculoImpostos);
                $post->set('retPis_aliq',$aliquotaPis);
                $post->set('retPis_total',$totalPis);

                $post->set('retCofins_bc',$baseCalculoImpostos);
                $post->set('retCofins_aliq',$aliquotaCofins);
                $post->set('retCofins_total',$totalCofins);

                $post->set('retCsll_bc',$baseCalculoImpostos);
                $post->set('retCsll_aliq',$aliquotaCsll);
                $post->set('retCsll_total',$totalCsll);

                $post->set('retIrrf_bc',$baseCalculoImpostos);
                $post->set('retIrrf_aliq',$aliquotaIrrf);
                $post->set('retIrrf_total',$totalIrrf);

                $post->set('retPrev_bc', $baseCalculoImpostos);
                $post->set('retPrev_aliq',$aliquotaPrev);
                $post->set('retPrev_total',$totalPrev);

                //Salva a nota e força o envio
                $post->set('emitir','');

                //realiza a tentativa de geração e emissão da NFE
                // Recebe um array com os devidos retornos da operação de envio
                $retornoViewModel = $this->_gerarEnviarNfe($nrPedido,$post);

            } else {
                throw  new \Exception("Número de pedido inválido ou fornecido incorretamente!",400);
            }
        } catch (\Exception $e) {
            $erro = $e->getMessage();
        }

        if (is_array($retornoViewModel)) {
            if(isset($retornoViewModel['erro'])){
                $erro = $retornoViewModel['erro'];
            }

            if (isset($retornoViewModel['retorno'])) {
                $retorno = $retornoViewModel['retorno'];
            }

            if (isset($retornoViewModel['retornoMail'])) {
                $retornoMail = $retornoViewModel['retornoMail'];
            }

            if (isset($retornoViewModel['chave'])) {
                $chave = $retornoViewModel['chave'];
            }

            if (isset($retornoViewModel['data'])) {
                $data = $retornoViewModel['data'];
            }

            if (isset($retornoViewModel['emailsEnviados'])) {
                $emailsEnviados = implode(",",$retornoViewModel['emailsEnviados']);
            }
        }

        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);
        $viewModel->setVariable('nrPedido', $nrPedido);
        $viewModel->setVariable('erro', $erro);
        $viewModel->setVariable('retorno', $retorno);
        $viewModel->setVariable('retornoMail', $retornoMail);
        $viewModel->setVariable('emailsEnviados', $emailsEnviados);
        $viewModel->setVariable('chave', $chave);
        $viewModel->setVariable('data', $data);

        return $viewModel;
    }

	/*
	* Criar uma nota NFE avulsa
	*/
	public function geraNfeAction(){

        // get the db adapter
        $sm         = $this->getServiceLocator();
        $dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');

        //get session
        $session    = new Container("orangeSessionContainer");

        //variaveis iniciais
        $erro           = "";
        $motivo         = "";
        $chave          = "";
        $nfeGe          = array();

        //get Post
        @$post = $this->getRequest()->getPost();

        try {
            //realiza a tentativa de geração e emissão da NFE
            // Recebe um array com os devidos retornos da operação de envio
            $retornoViewModel = $this->_gerarEnviarNfe("",$post);

        } catch (\Exception $e) {
            $erro = $e->getMessage();
        }

        if (is_array($retornoViewModel)) {
            if(isset($retornoViewModel['erro'])){
                $erro = $retornoViewModel['erro'];
            }

            if (isset($retornoViewModel['retorno'])) {
                $retorno = $retornoViewModel['retorno'];
            }

            if (isset($retornoViewModel['chave'])) {
                $chave = $retornoViewModel['chave'];
            }

            if (isset($retornoViewModel['data'])) {
                $data = $retornoViewModel['data'];
            }

            if (isset($retornoViewModel['infNFE'])) {
                $infNfe = $retornoViewModel['infNFE'];
            }

            if (isset($retornoViewModel['redirect'])){
                return $this->redirect()->toUrl($retornoViewModel['redirect']);
            }
        }

        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);
        $viewModel->setVariable('erro', $erro);
        $viewModel->setVariable('retorno', $retorno);
        $viewModel->setVariable('chave', $chave);
        $viewModel->setVariable('data', $data);
        $viewModel->setVariable('infNFE', $infNfe);

        return $viewModel;

	}

	//Testar Status do serviço
	public function statusNfeAction(){
		//get session
        $session = new Container("orangeSessionContainer");

		$nfe = new ToolsNFe( getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');
		$nfe->setModelo('55');

		$aResposta = array();
		$siglaUF = 'DF';
		$tpAmb = '2';
		$retorno = $nfe->sefazStatus($siglaUF, $tpAmb, $aResposta);
		echo '<br><br><PRE>';
		echo htmlspecialchars($nfe->soapDebug);
		echo '</PRE><BR>-------------------------------------------------------------------------------------------------';
		print_r($aResposta);
		echo "<br>";
	}

	public function consultaReceitaAction(){

		$viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        return $viewModel;
	}

	public function assinaNFe($chave, $modelo){

		//get session
        $session = new Container("orangeSessionContainer");
		$msgErro = '';

		$nfe = new ToolsNFe( getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');
        $nfe->setModelo((int)$modelo);

		//$filename = "/var/www/nfe/homologacao/entradas/{$chave}-nfe.xml"; // Ambiente Linux
		//$filename = "D:/xampp/htdocs/GIT-nfephp-org/nfephp/xmls/NF-e/homologacao/entradas/{$chave}-nfe.xml"; // Ambiente Windows
		$filename =  getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\saidas\\'.$chave.'-nfe.xml';
		$xml = file_get_contents($filename);

		$xml = $nfe->assina($xml);
		//$filename = "/var/www/nfe/homologacao/assinadas/{$chave}-nfe.xml"; // Ambiente Linux
		//$filename = "D:/xampp/htdocs/GIT-nfephp-org/nfephp/xmls/NF-e/homologacao/assinadas/{$chave}-nfe.xml"; // Ambiente Windows
		$filename =  getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\assinadas\\'.$chave.'-nfe.xml';

		file_put_contents($filename, $xml);
		chmod($filename, 0777);

		// Valida XML
		$validador = new ToolsNFe(getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');
		$validador->setModelo((int)$modelo);

		if (! $validador->validarXml($xml) || sizeof($nfe->errors)) {
			foreach ($validador->errors as $erro) {
				if (is_array($erro)) {
					foreach ($erro as $err) {
						$msgErro = $msgErro . $err ."<br>";
					}
				} else {
					$msgErro = $msgErro . $erro ."<br>";
				}
			}
		}
		//Fim Valida XML

		return $msgErro;
	}

	public function enviaNFe($chave, $tpAmb, $modelo){

		//get session
        $session = new Container("orangeSessionContainer");
		$nfe = new ToolsNFe( getcwd() . '/vendor/config/config_'.$session->cdBase.'.json' );
		$nfe->setModelo((int)$modelo);

		$aResposta = array();
		// $aXml = file_get_contents("/var/www/nfe/homologacao/assinadas/{$chave}-nfe.xml"); // Ambiente Linux
		$aXml = file_get_contents( getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\assinadas\\'.$chave.'-nfe.xml'); // Ambiente Windows
		$idLote = '';
		$indSinc = '1';
		$flagZip = false;

		$retorno = $nfe->sefazEnviaLote($aXml, $tpAmb, $idLote, $aResposta, $indSinc, $flagZip);

		//$nfe = new ToolsNFe( getcwd() . '/vendor/config/config.json' );

		//$indSinc = '1'; //0=asíncrono, 1=síncrono
		//$pathNFefile = "D:/xampp/htdocs/GIT-nfephp-org/nfephp/xmls/NF-e/homologacao/assinadas/{$chave}-nfe.xml";
		//if (! $indSinc) {
		//	$pathProtfile = "D:/xampp/htdocs/GIT-nfephp-org/nfephp/xmls/NF-e/homologacao/temporarias/201605/{$recibo}-retConsReciNFe.xml";
		//} else {
		//	$pathProtfile = "D:/xampp/htdocs/GIT-nfephp-org/nfephp/xmls/NF-e/homologacao/temporarias/201605/{$recibo}-retEnviNFe.xml";
		//}
		if( $aResposta['prot']['0']['nProt'] != ''){
			$recibo = $aResposta['idLote'];
			$retorno = $aResposta;
			$retorno['recibo']     = $recibo;
			$retorno['protcoloco'] = $aResposta['prot']['0']['nProt'];

			$pathNFefile = getcwd() ."/public/clientes/".$session->cdBase."/NFe/assinadas/{$chave}-nfe.xml";
			$pathProtfile = getcwd() ."/public/clientes/".$session->cdBase."/NFe/temporarias/". date("Ym") ."/{$recibo}-retEnviNFe.xml";

			$saveFile = true;
			$nfe->addProtocolo($pathNFefile, $pathProtfile, $saveFile);
			//$retorno = $nfe->addProtocolo($pathNFefile, $pathProtfile, $saveFile);

			return $retorno;
		}else{
			return $aResposta;
		}
	}

	public function imprimeDanfeAction(){

		//get session
        $session = new Container("orangeSessionContainer");
		$chave = (string) $this->params()->fromQuery('nCh');
		$data  = (string) $this->params()->fromQuery('data');

		try {
            $nfe = new ToolsNFe( getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');

            $xmlProt = getcwd() ."/public/clientes/".$session->cdBase."/NFe/enviadas/aprovadas/". $data ."/".$chave."-protNFe.xml";
            // Uso da nomeclatura '-danfe.pdf' para facilitar a diferenciação entre PDFs DANFE e DANFCE salvos na mesma pasta...
            $pdfDanfe = getcwd() ."/public/clientes/".$session->cdBase."/NFe/PDF/". $data ."/".$chave."-danfe.pdf";
            if( !is_dir( getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\\'. $data .'\\' ))
                @mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\\'. $data .'\\');

            $docxml = FilesFolders::readFile($xmlProt);
            $danfe = new Danfe($docxml, 'P', 'A4', $nfe->aConfig['aDocFormat']->pathLogoFile, 'I', '');
            $id = $danfe->montaDANFE();
            $salva = $danfe->printDANFE($pdfDanfe, 'F'); //Salva o PDF na pasta
            $abre = $danfe->printDANFE("{$id}-danfe.pdf", 'D'); //Abre o PDF no Navegador

        } catch(\Exception $e){
            $msg = 'error='.$e->getMessage();
            return $this->redirect()->toUrl("/nota/lista?".$msg);
        }

        return true;
	}

    /**
     *  Impressão de DANFCE
     * @return bool|\Zend\Http\Response
     */
    public function imprimeDanfceAction(){

        //get session
        $session = new Container("orangeSessionContainer");
        $chave = (string) $this->params()->fromQuery('nCh');
        $data  = (string) $this->params()->fromQuery('data');

        try {
            $nfe        = new ToolsNFe( getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');
            $xmlProt    = getcwd() ."/public/clientes/".$session->cdBase."/NFe/enviadas/aprovadas/". $data ."/".$chave."-protNFe.xml";
            //$xmlProt    = getcwd() ."/public/clientes/".$session->cdBase."/NFe/saidas/xml-erro-montaDanfce.xml";

            // Uso da nomeclatura '-danfce.pdf' para facilitar a diferenciação entre PDFs DANFE e DANFCE salvos na mesma pasta...
            $pdfDanfce = getcwd() ."/public/clientes/".$session->cdBase."/NFe/PDF/". $data ."/".$chave."-danfce.pdf";

            if( !is_dir( getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\\'. $data .'\\' )) {
                @mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\\'. $data .'\\');
            }

            $docxml     = FilesFolders::readFile($xmlProt);
            $ecoNFCe    = false;    //false = Não (NFC-e Completa);
                                    //true = Sim (NFC-e Simplificada)

            $danfce     = new Danfce($docxml,  $nfe->aConfig['aDocFormat']->pathLogoFile, 2);
            $id         = $danfce->montaDANFCE($ecoNFCe);
            $salva      = $danfce->printDANFCE('pdf', $pdfDanfce, 'F'); //Salva o PDF na pasta
            $abre       = $danfce->printDANFCE('pdf', $pdfDanfce, 'I'); //Abre o PDF no Navegador

        } catch(\Exception $e){
            $msg = 'error='.$e->getMessage();
            return $this->redirect()->toUrl("/nota/lista?".$msg);
        }

        return true;
    }

    /**
     * Metodo apenas para DANFE não enviadas
     *
     * @return bool
     * @throws \NFePHP\Extras\NfephpException
     */
	public function visualizaDanfeAction(){

		//get session
        $session = new Container("orangeSessionContainer");
		$chave = (string) $this->params()->fromQuery('nCh');

		$nfe = new ToolsNFe( getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');
		$xmlProt = getcwd() ."/public/clientes/".$session->cdBase."/NFe/saidas/".$chave."-nfe.xml";
		// Uso da nomeclatura '-danfe.pdf' para facilitar a diferenciação entre PDFs DANFE e DANFCE salvos na mesma pasta...
		//$pdfDanfe = getcwd() ."/public/clientes/".$session->cdBase."/NFe/PDF/temporarias/".$chave."-danfe.pdf";

		if( !is_dir( getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\temporarias\\' )) {
            @mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\temporarias\\');
        }

		$docxml = FilesFolders::readFile($xmlProt);
		$danfe = new Danfe($docxml, 'P', 'A4', $nfe->aConfig['aDocFormat']->pathLogoFile, 'I', '');
		$id = $danfe->montaDANFE();
		//$salva = $danfe->printDANFE($pdfDanfe, 'F'); //Salva o PDF na pasta
		$abre = $danfe->printDANFE("{$id}-danfe.pdf", 'I'); //Abre o PDF no Navegador

		return true;
	}

    /**
     * Metodo apenas para DANFCE não enviadas
     *
     * @return bool
     * @throws \NFePHP\Extras\NfephpException
     */
    public function visualizaDanfceAction(){

        //get session
        $session    = new Container("orangeSessionContainer");
        $chave      = (string) $this->params()->fromQuery('nCh');
        $nfe        = new ToolsNFe( getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');
        $xmlProt    = getcwd() ."/public/clientes/".$session->cdBase."/NFe/saidas/".$chave."-nfe.xml";

        if( !is_dir( getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\temporarias\\' )){
            @mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\temporarias\\');
        }

        $docxml = FilesFolders::readFile($xmlProt);
        $danfce = new Danfce($docxml, $nfe->aConfig['aDocFormat']->pathLogoFile, 2);

        $ecoNFCe = false; //false = Não (NFC-e Completa); true = Sim (NFC-e Simplificada)
        $id = $danfce->montaDANFCE($ecoNFCe);

        $abre = $danfce->printDANFCE('pdf', "{$id}-danfce.pdf", 'I'); //Abre o PDF no Navegador

        return true;
    }

	public function addProt($chave, $recibo){}

	public function consultaChave($chave){

		$nfe = new ToolsNFe( getcwd() . '/vendor/config/config_'.$session->cdBase.'.json' );
		$nfe->setModelo('55');

		$tpAmb = '2';
		$aResposta = array();
		$xml = $nfe->sefazConsultaChave($chave, $tpAmb, $aResposta);

		return $aResposta;
	}

	public function cancelaAction(){

		$session = new Container("orangeSessionContainer");
		// get the db adapter
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
		$post = $this->getRequest()->getPost();
		$table = new notaTable($dbAdapter);

		$pageNumber = (int) $this->params()->fromQuery('pg');
        $param = array();

		$viewModel = new ViewModel();

		$nfe    = new ToolsNFe(getcwd() . '/vendor/config/config_'.$session->cdBase.'.json');
		$modelo = $post['mod'];
		$tpAmb  = $post['tpAmb'];
		$chave  = $post['chave'];
		$nProt  = $post['nProt'];
		$xJust  = $post['xJust'];

		$aResposta = array();

		$nfe->setModelo($modelo);

		try {
            $retorno = $nfe->sefazCancela($chave, $tpAmb, $xJust, $nProt, $aResposta);

            if( $aResposta['evento']['0']['nProt'] != ''){
                $array['DT_CANCELA_DATA'] = date('Ymd');
                $array['DS_CANCELA_PROTOCOLO'] = $aResposta['evento']['0']['nProt'];

                $table->atualiza_nota($chave, $array);

                $msg = 'sucess=Nota cancelada com sucesso!';
            }else{
                $msg = 'error=Erro no cancelamento de nota! '.$aResposta['evento']['0']['xMotivo'];
            }
        } catch(\Exception $e) {
            $msg = 'error='.$e->getMessage();
        }

		return $this->redirect()->toUrl("/nota/lista?".$msg);
	}

    /**
     * @param $chave
     * @param array $email
     * @param $dataemis
     * @return bool
     */
	public function enviaMail($chave, $email = array (), $dataemis, $modelo = '55'){

		//get session
        $session = new Container("orangeSessionContainer");
		$nfe = new ToolsNFe( getcwd() . '/vendor/config/config_'.$session->cdBase.'.json' );
		$nfe->setModelo($modelo);

		$pathXml = getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\enviadas\aprovadas\\'. $dataemis .'\\'.$chave.'-protNFe.xml';

		if( !is_dir( getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\\'. $dataemis .'\\' ))
			@mkdir(getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\\'. $dataemis .'\\');

        $aMails         = $email; //se for um array vazio a classe Mail irá pegar os emails do xml
        $templateFile   = ''; //se vazio usará o template padrão da mensagem
        $comPdf         = true; //se true, anexa a DANFE no e-mail

		try {

            $docxml = FilesFolders::readFile($pathXml);

            if ($modelo == '65') { //nfc-e

                $pathPdf    = getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\\'. $dataemis .'\\'.$chave.'-danfce.pdf';
                $ecoNFCe    = false;    //false = Não (NFC-e Completa);
                                        //true = Sim (NFC-e Simplificada)
                $danfce     = new Danfce($docxml,  $nfe->aConfig['aDocFormat']->pathLogoFile, 2);
                $id         = $danfce->montaDANFCE($ecoNFCe);
                $salva      = $danfce->printDANFCE('pdf', $pathPdf, 'F'); //Salva o PDF na pasta

            } else {
                $pathPdf = getcwd() . '\public\clientes\\'.$session->cdBase.'\NFe\PDF\\'. $dataemis .'\\'.$chave.'-danfe.pdf';

                $danfe = new Danfe($docxml, 'P', 'A4', $nfe->aConfig['aDocFormat']->pathLogoFile, 'I', '');
                $id = $danfe->montaDANFE();
                $salva = $danfe->printDANFE($pathPdf, 'F'); //Salva o PDF na pasta
            }

            $nfe->enviaMail($pathXml, $aMails, $templateFile, $comPdf, $pathPdf);

			return true;
		} catch ( \Exception $e/*NFePHP\Common\Exception\RuntimeException $e*/) { //Essa exception está atrapalhando o fluxo caso email errado
			//echo $e->getMessage();
			return false;
		}
	}

    /**
     * @return \Zend\Http\Response
     */
	public function sendAction(){

		@$post = @$this->getRequest()->getPost();

		$chave      = $post->get('chaveMail');
		$email      = $post->get('email');
		$dataemis   = $post->get('dataemis');
        $modelo     = $post->get('mod');

		try {
            $this->enviaMail( $chave, array($email), $dataemis , $modelo);
            $msg = "success=Email enviado!";
        } catch ( \Exception $e) {
            $msg = "error=". $e->getMessage();
        }

		return $this->redirect()->toUrl("/nota/lista?".$msg);
	}

    /**
     * @return ViewModel
     */
	public function abrirAction() {
		// get the db adapter
        $sm 		        = $this->getServiceLocator();
        $dbAdapter 	        = $sm->get('Zend\Db\Adapter\Adapter');
		$cfopTable          = new TabelaTable($dbAdapter);
		$table              = new NotaTable($dbAdapter);
		$session            = new Container("orangeSessionContainer");

		$infNFe             = (string) $this->params()->fromQuery('infNFe');
		$replicar           = (string) $this->params()->fromQuery('replicar');
		$subTotal           = 0.0;
        $total              = 0.0;
        $nrDescontoNota     = 0.0;
		$mercadoriasNota    = $table->getMercadoria($infNFe);

		foreach( $mercadoriasNota as $merc ){
            $subTotal       = (float)$subTotal + ($merc['vUnCom'] * $merc['qCom']);
			$total          = (float)$total    + $merc['vProd'];
		}

		//desconto geral aplicado na nota
        if ($subTotal != $total) {
            $nrDescontoNota = (float)((($subTotal - $total) * 100) / $subTotal);
        }

		$nfe 		        = $table->getNota($infNFe);
        $nfeReferenciada    = $table->getNotaReferenciada($infNFe);
        $cfop               = $cfopTable->selectAll_cfop();
		$config             = $table->getConfig('1');
		$viewModel          = new ViewModel();

		$viewModel->setTemplate('application/nota/avulsa');
		$viewModel->setTerminal(false);
		$viewModel->setVariable('replicar', $replicar);
		$viewModel->setVariable('nfe', $nfe);
		$viewModel->setVariable('nfeMerc', $mercadoriasNota);
        $viewModel->setVariable('nfeReferenciada', $nfeReferenciada);
        $viewModel->setVariable('totalSubNota', $subTotal);
		$viewModel->setVariable('totalNota', $total);
        $viewModel->setVariable('nrDesconto', $nrDescontoNota);
        $viewModel->setVariable('valorDesconto', ($subTotal - $total));
		$viewModel->setVariable('cfop', $cfop);
		$viewModel->setVariable('config', $config);

        return $viewModel;
	}

    /**
     * Usado para baixar notas no arquivo XML, na tela de listagem na nota
     *
     * @return \Zend\Http\Response|\Zend\Http\Response\Stream
     */
	public function saveNotaAction(){

		$msg = '';

		$session = new Container("orangeSessionContainer");

		$mes = $_POST['mes'];
		$ano = $_POST['ano'];
		$data = $ano.$mes;

		$pastaXML           = getcwd() . '/public\clientes/'.$session->cdBase.'/NFe/enviadas/aprovadas/'. $data .'/';
        $pastaCanceladaXML  = getcwd() . '/public\clientes/'.$session->cdBase.'/NFe/canceladas/'. $data .'/';
		$pastaPDF           = getcwd() . '/public\clientes/'.$session->cdBase.'/NFe/PDF/'. $data .'/';

		try {
            if( !is_dir( $pastaXML )) {
                mkdir($pastaXML,0777);
                //throw  new \Exception("Nenhuma pasta XML foi encontrada para enviadas!",400);
            }

            if( !is_dir( $pastaCanceladaXML )) {
                mkdir($pastaCanceladaXML,0777);
               // throw  new \Exception("Nenhuma pasta XML foi encontrada para canceladas!",400);
            }

            if( !is_dir( $pastaPDF )) {
                mkdir($pastaPDF,0777);
                //throw  new \Exception("Nenhuma pasta PDF foi encontrada!",400);
            }

            $zip    = new Ziparchive();
            $dirZip = getcwd() . '/public/clientes/'.$session->cdBase.'/NFe/';
            chmod($dirZip,0777);
	
            $file = $dirZip.'Notas-'.$data.'.zip';

            if (!is_file($file)) {
                if ($zip->open($file, ZipArchive::CREATE|ZipArchive::OVERWRITE) !== TRUE) {
                    throw  new \Exception("Erro ao retornar arquivo \.ZIP, sem permissão de escrita na pasta pública!",400);
                }
            } else {
				
                if ($zip->open($file, ZipArchive::OVERWRITE) !== TRUE) {
                    throw  new \Exception("Erro ao retornar arquivo \.ZIP, arquivo não pode ser encontrado!",400);
                }
            }

            $iteratorXML = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pastaXML));
            foreach ($iteratorXML as $key=>$value) {
				if ( basename($key)== "." || basename($key)== ".."){
					continue;
				}
				
				if (TRUE !== $zip->addFile(realpath($key), iconv('ISO-8859-1', 'IBM850', 'XML/aprovadas/'.basename($key)))){
					 throw  new \Exception("Erro ao  adicionar o arquivo XML: $key",400);
				}
            }

            $iteratorXML = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pastaCanceladaXML));
            foreach ($iteratorXML as $key=>$value) {
                if ( basename($key)== "." || basename($key)== ".."){
                    continue;
                }

                if (TRUE !== $zip->addFile(realpath($key), iconv('ISO-8859-1', 'IBM850', 'XML/canceladas/'.basename($key)))){
                    throw  new \Exception("Erro ao  adicionar o arquivo XML: $key",400);
                }
            }

            $iteratorPDF = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pastaPDF));
            foreach ($iteratorPDF as $key=>$value) {
				if ( basename($key)== "." || basename($key)== ".."){
					continue;
				}
				
                if (TRUE !== $zip->addFile(realpath($key), iconv('ISO-8859-1', 'IBM850', 'PDF/'.basename($key)))){
					 throw  new \Exception("Erro ao  adicionar o arquivo PDF: $key",400);
				}
            }

            $zip->close();

        } catch(\Exception $e) {
            return $this->redirect()->toUrl('/nota/lista?error='.$e->getMessage());
        }

		$response = new \Zend\Http\Response\Stream();
		$response->setStream(fopen($file, 'r'));
		$response->setStatusCode(200);
		$response->setStreamName(basename($file));
		$headers = new \Zend\Http\Headers();
		$headers->addHeaders(array(
			'Content-Disposition' => 'attachment; filename="' . basename($file) .'"',
			'Content-Type' => 'application/octet-stream',
			'Content-Length' => filesize($file),
			'Expires' => '@0', // @0, because zf2 parses date as string to \DateTime() object
			'Cache-Control' => 'must-revalidate',
			'Pragma' => 'public'
		));
		$response->setHeaders($headers);
		return $response;
	}

    private function zipStatusString( $status )
    {
        switch( (int) $status )
        {
            case ZipArchive::ER_OK           : return 'N No error';
            case ZipArchive::ER_MULTIDISK    : return 'N Multi-disk zip archives not supported';
            case ZipArchive::ER_RENAME       : return 'S Renaming temporary file failed';
            case ZipArchive::ER_CLOSE        : return 'S Closing zip archive failed';
            case ZipArchive::ER_SEEK         : return 'S Seek error';
            case ZipArchive::ER_READ         : return 'S Read error';
            case ZipArchive::ER_WRITE        : return 'S Write error';
            case ZipArchive::ER_CRC          : return 'N CRC error';
            case ZipArchive::ER_ZIPCLOSED    : return 'N Containing zip archive was closed';
            case ZipArchive::ER_NOENT        : return 'N No such file';
            case ZipArchive::ER_EXISTS       : return 'N File already exists';
            case ZipArchive::ER_OPEN         : return 'S Can\'t open file';
            case ZipArchive::ER_TMPOPEN      : return 'S Failure to create temporary file';
            case ZipArchive::ER_ZLIB         : return 'Z Zlib error';
            case ZipArchive::ER_MEMORY       : return 'N Malloc failure';
            case ZipArchive::ER_CHANGED      : return 'N Entry has been changed';
            case ZipArchive::ER_COMPNOTSUPP  : return 'N Compression method not supported';
            case ZipArchive::ER_EOF          : return 'N Premature EOF';
            case ZipArchive::ER_INVAL        : return 'N Invalid argument';
            case ZipArchive::ER_NOZIP        : return 'N Not a zip archive';
            case ZipArchive::ER_INTERNAL     : return 'N Internal error';
            case ZipArchive::ER_INCONS       : return 'N Zip archive inconsistent';
            case ZipArchive::ER_REMOVE       : return 'S Can\'t remove file';
            case ZipArchive::ER_DELETED      : return 'N Entry has been deleted';

            default: return sprintf('Unknown status %s', $status );
        }
    }

}
