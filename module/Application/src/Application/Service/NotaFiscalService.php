<?php
/**
 * Created by PhpStorm.
 * User: HIGOR
 * Date: 16/02/2019
 * Time: 19:16
 *
 * Classe de servi�o, a ser utilizada pelos Modulos de Api e Application
 *
 */

namespace Application\Service;

use Application\Model\NotaTable;
use Zend\Paginator;
use Zend\ServiceManager\ServiceManager;
use Zend\Session\Container;


class NotaFiscalService
{
    /**
     * @var ServiceManager
     */
    private $sm;


    /**
     * NotaFiscalService constructor.
     * @param ServiceManager $sm
     */
    public function __construct(ServiceManager $sm, $sessionCdBase = null)
    {
        $this->sm = $sm;

        if (null != $sessionCdBase) { //temporario
            $session = new Container("orangeSessionContainer");
            $session->cdBase = (int)$sessionCdBase;
        }
    }

    /**
     * @return array|object
     */
     private function getDbAdapter(){
        $dbAdapter = $this->sm->get('Zend\Db\Adapter\Adapter');
        return $dbAdapter;
    }

    /**
     * @param $param
     * @param $pageNumber
     * @return Paginator\Paginator
     */
    public function getList($param, $pageNumber) {

        $table = new notaTable($this->getDbAdapter());

        if ((int)$pageNumber == 0) {
            $pageNumber = 1;
        }

        $listaNfe = $table->fetchAll($param, $pageNumber);

        return $listaNfe;
    }

    /**
     * @param $param
     * @param $pageNumber
     * @return Paginator\Paginator
     */
    public function getArrayList($param) {

        $table      = new notaTable($this->getDbAdapter());
        $listaNfe   = $table->fetchArray($param);

        return $listaNfe;
    }
}