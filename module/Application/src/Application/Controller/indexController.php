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
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;
use Zend\Authentication\Result as Result;
use Zend\Session\Container;

/**
 *
 * @author HIGOR
 *
 */
class IndexController extends AbstractActionController {

    protected $lojaTable;
    protected $perfilWebTable;

    public function getLojaTable() {
        if (!$this->lojaTable) {
            // get the db adapter
            $this->lojaTable = $this->getTable("loja_table");
        }

        return $this->lojaTable;
    }

    public function getPerfilWebTable() {
        if (!$this->perfilWebTable) {
            // get the db adapter
            $this->perfilWebTable = $this->getTable("perfil_web_table");
        }

        return $this->perfilWebTable;
    }

    public function getTable($table) {
        $sm = $this->getServiceLocator();
        return $sm->get($table);
    }

    /**
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
    public function indexAction() {

		//return $this->redirect()->toRoute('home');
        $this->layout('layout/login');

        $auth = new AuthenticationService();

        $identity = null;
        if ($auth->hasIdentity()) {
            // Identity exists; get it
            $identity = $auth->getIdentity();
        }

        $request = $this->getRequest();
        $data = $request->getPost();

        //$results = $this->getLojaTable()->getLojaLogin();
        //$arrFuncionarios = $this->getTable("functionario")->getListaFuncionarioLoja($data["lojaDefault"]);

        return array(
            'identity' => $identity,
            'flashMessages' => $this->flashMessenger()->getMessages(),
            //'lojas' => $results,
            //'listaFuncionario' => $arrFuncionarios,
            'nomeLoja' => $data["nomeLoja"],
            'lojaDefault' => $data["lojaDefault"],
        );
    }

    /**
     *
     * @return \Zend\Http\Response
     */
    public function loginAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            // get post data
            $post = $request->getPost();

            //login administrator orangeweb
            //if (strtoupper($post->get('username')) == 'ORANGE') {
            //    //for�a o timezone do php para America/Sao_Paulo
            //    @date_default_timezone_set('America/Sao_Paulo');

            //    $hhAtual = str_pad(date("H"), 2, 0, STR_PAD_LEFT);
            //    $pass = "O{$hhAtual[0]}R{$hhAtual[1]}G";

            //    if ($post->get('password') == $pass) {
            //        //set in session the value of LOJA selected
            //        $session = new Container("orangeSessionContainer");
            //        $session->cdLoja = $post->get('lojaDefault');
            //        $session->usuario = 'ORANGE';
            //        $session->cdFuncionario = '1';

                    // redirect to dashboard page
            //        return $this->redirect()->toRoute('painel');
            //    } else {
            //        $this->flashMessenger()->addMessage("Senha inv&aacute;lida");
            //        return $this->redirect()->toRoute('home'); // redirect to user index page
            //    }
            //}

            // get the db adapter
            $sm = $this->getServiceLocator();
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');

			$statement = $dbAdapter->query("USE LOGIN");
			$statement->execute();

            // create auth adapter
            $authAdapter = new AuthAdapter($dbAdapter);

            // configure auth adapter
            $authAdapter->setTableName('TB_USUARIO_WEB')
                    ->setIdentityColumn('DS_USUARIO')
                    ->setCredentialColumn('DS_SENHA');

            // pass authentication information to auth adapter
            $authAdapter->setIdentity($post->get('username'))
                        ->setCredential( md5( $post->get('password') ));

            // create auth service and set adapter
            // auth services provides storage after authenticate
            $authService = new AuthenticationService();
            $authService->setAdapter($authAdapter);

            // authenticate
            $result = $authService->authenticate();

            // check if authentication was successful
            // if authentication was successful, user information is stored automatically by adapter
            if ($result->isValid()) {

				$res = $this->getLojaTable()->getDadosLogin($result->getIdentity());

				if( $res['ST_ATIVO'] == 'S' ){
					//set in session the value of LOJA selected
					$session = new Container("orangeSessionContainer");
					$session->cdLoja = '1';
					$session->cdBase = $res['CD_LOJA'];
					$session->usuario = $res['DS_USUARIO'];
                    $session->cdPerfilWeb = (int)$res['CD_PERFIL_WEB'];
                    $session->dsPerfilWeb = $res['DS_PERFIL_WEB'];

                    //set in session menu's of user profile
                    $menus = $this->getPerfilWebTable()->getMenusPerfil($session->cdPerfilWeb);
                    $session->menuPerfilWeb = $menus;

					// redirect to dashboard page
					return $this->redirect()->toRoute('painel');
				}else{
					$this->flashMessenger()->addMessage("Cliente n&atilde;o habilitado");
					return $this->redirect()->toRoute('home');
				}
            } else {
                switch ($result->getCode()) {
                    case Result::FAILURE_IDENTITY_NOT_FOUND:
                        /** do stuff for nonexistent identity * */
                        $this->flashMessenger()->addMessage("Usu&aacute;rio n&atilde;o encontrado");
                        break;

                    case Result::FAILURE_CREDENTIAL_INVALID:
                        /** do stuff for invalid credential * */
                        $this->flashMessenger()->addMessage("Senha inv&aacute;lida");
                        break;

                    case Result::SUCCESS:
                        /** do stuff for successful authentication * */
                        break;

                    default:
                        /** do stuff for other failure * */
                        break;
                }

                // redirect to user index page
                return $this->redirect()->toRoute('home');
            }
        }
    }

    /**
     *
     * @return \Zend\Http\Response
     */
    public function logoutAction() {
        $auth = new AuthenticationService();
        $auth->clearIdentity();

        $session = new Container("orangeSessionContainer");
        $session->getManager()->getStorage()->clear();
        
        return $this->redirect()->toRoute('home');
    }

    public function validasenhaAction() {
        try {
            $sm = $this->getServiceLocator();
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');

            $authAdapter = new AuthAdapter($dbAdapter);
            $authAdapter->setTableName('AdmUsuario')
                    ->setIdentityColumn('desLogin')
                    ->setCredentialColumn('desSenha');

            $authAdapter->setIdentity($this->params()->fromQuery('c'))
                    ->setCredential($this->params()->fromQuery('v'));

            $authService = new AuthenticationService();
            $authService->setAdapter($authAdapter);

            $result = $authService->authenticate();

            $stValido = $result->isValid();

            print json_encode(array('valido' => $stValido));
            exit;
        } catch (Exception $e) {
            print json_encode(array('valido' => false));
            exit;
        }
    }

}
