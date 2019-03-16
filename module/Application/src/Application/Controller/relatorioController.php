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
use DOMPDFModule\View\Model\PdfModel;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;


/**
 * 
 * @author HIGOR
 *
 */
class RelatorioController extends AbstractActionController
{
	/**
	 * 
	 */
	 
	public function onDispatch(\Zend\Mvc\MvcEvent $e){
		//get session
    	$session 	= new Container("orangeSessionContainer");
    	// get the db adapter
    	$sm 		= $this->getServiceLocator();
    	$dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');
		
		if( @$session->cdBase ){
			$statement = $dbAdapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
        return parent::onDispatch($e);
	}
	
	public function validaAcessoGerente(){
		//get session
		$session 	= new Container("orangeSessionContainer");
	
		if(($session->usuario != 'ORANGE') &&(!$session->stGerente))
		{
			// redirect to dashboard page
			//return $this->redirect()->toRoute('painel');
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	 */
	public function indexAction()
	{
		$this->validaAcessoGerente();	
	}

	/**
     * 
     * @return \Zend\Http\Response
     */
    public function relatorioBIAction()
    {
    	$auth = new AuthenticationService();
    	
    	$identity = null;
    	if ((!$auth->hasIdentity())&&($session->usuario != 'ORANGE')) 
    	{
    		// redirect to user index page
    		//return $this->redirect()->toRoute('home');
    	}
    	 
    }
    
}