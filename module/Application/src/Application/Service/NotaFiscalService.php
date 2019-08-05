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
use Application\Model\NotaInutilizadaTable;
use Zend\Paginator;
use Zend\ServiceManager\ServiceManager;
use Zend\Session\Container;

/**
 * Class NotaFiscalService
 * @package Application\Service
 */
class NotaFiscalService
{
    /**
     * @var ServiceManager
     */
    private $sm;

    /**
     * Solução alternativa para a function nativa <array_column>, previsto apenas para php5.5 ou superior
     *
     * @param array $input
     * @param $columnKey
     * @param null $indexKey
     * @return array|bool
     */
    protected function array_column_bdge(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if ( !array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if ( ! is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }



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
     * @return array
     */
    public function getListNumeroInutilizadas() {

        $table = new notaInutilizadaTable($this->getDbAdapter());

        $listaNfeNumeroInutilizada = $table->listNumeroNfeInutilizada();

        if(is_array($listaNfeNumeroInutilizada)) {
            return $this->array_column_bdge($listaNfeNumeroInutilizada,'NR_FAIXA');
        } else {
            return array();
        }
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