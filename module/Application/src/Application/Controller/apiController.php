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
class ApiController extends AbstractActionController {

    protected $lojaTable;
    protected $cargoTable;

    public function getLojaTable() {
        if (!$this->lojaTable) {
            // get the db adapter
            $sm = $this->getServiceLocator();
            $this->lojaTable = $sm->get("loja_table");
        }

        return $this->lojaTable;
    }

    public function getCargoTable() {
        if (!$this->cargoTable) {
            // get the db adapter
            $sm = $this->getServiceLocator();
            $this->cargoTable = $sm->get("cargo_table");
        }

        return $this->cargoTable;
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

    	// $session 	= new Container("orangeSessionContainer");

    	// get the db adapter
    	$sm 		= $this->getServiceLocator();
    	$dbAdapter  = $sm->get('Zend\Db\Adapter\Adapter');


        if( $this->params()->fromQuery('cdBase') ){
			$statement = $dbAdapter->query("USE BDGE_".$this->params()->fromQuery('cdBase') );
			$statement->execute();
		}
        $date = Date('Ymd');

    	//query
    	$today 		= " select today = sum(VL_TOTAL_LIQUIDO) from TB_PEDIDO
                        where DT_PEDIDO between '".$date." 00:00:00' and '".$date." 23:59:59'
                         and ST_PEDIDO = 'F' ";

        $statementList   = $dbAdapter->query($today);
        // $results = $statement->execute(array($date . ' 00:00:00', $date . ' 23:59:59'));
        $results = $statementList->execute();
        foreach ($results as $result) {
            $returnToday = $result;
        }
        //List today
        $today 		= " select  totalByType = sum(p.VL_TOTAL_LIQUIDO),
                                paymentType = tp.DS_TIPO_PAGAMENTO
                        from TB_PEDIDO p
                        inner join TB_PEDIDO_PAGAMENTO pp on pp.NR_PEDIDO = p.NR_PEDIDO
                        inner join TB_TIPO_PAGAMENTO tp on tp.CD_TIPO_PAGAMENTO = pp.CD_TIPO_PAGAMENTO
                        WHERE DT_PEDIDO BETWEEN '".$date." 00:00:00' AND '".$date." 23:59:59'
                            AND ST_PEDIDO = 'F'
                        GROUP BY tp.DS_TIPO_PAGAMENTO
                        ORDER BY sum(p.VL_TOTAL_LIQUIDO) desc";

        $statementList   = $dbAdapter->query($today);
        // $results = $statement->execute(array($date . ' 00:00:00', $date . ' 23:59:59'));
        $results = $statementList->execute();
        $returnListToday = array();
        foreach ($results as $result) {
            $returnListToday[] = array(
                'paymentType' => utf8_encode($result['paymentType']),
                'totalByType' => $result['totalByType']
            );
        }

        $day = date('w');
        $week_start = date('Ymd', strtotime('-'.$day.' days'));
        $week_end = date('Ymd', strtotime('+'.(6-$day).' days'));
        $week 		= " select week = sum(VL_TOTAL_LIQUIDO) from TB_PEDIDO
                        where DT_PEDIDO between '".$week_start." 00:00:00' and '".$week_end." 23:59:59'
                        AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' ) ";

        $statementList   = $dbAdapter->query($week);
        // $results = $statement->execute(array($date . ' 00:00:00', $date . ' 23:59:59'));
        $results = $statementList->execute();
        foreach ($results as $result) {
            $returnWeek = $result;
        }
        $week 		= " select top 10 totalByType = sum(p.VL_TOTAL_LIQUIDO),
                                paymentType = tp.DS_TIPO_PAGAMENTO
                        from TB_PEDIDO p
                        left join TB_PEDIDO_PAGAMENTO pp on pp.NR_PEDIDO = p.NR_PEDIDO
                        left join TB_TIPO_PAGAMENTO tp on tp.CD_TIPO_PAGAMENTO = pp.CD_TIPO_PAGAMENTO
                        WHERE p.DT_PEDIDO BETWEEN '".$week_start." 00:00:00' AND '".$week_end." 23:59:59'
                            AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )
                        GROUP BY tp.DS_TIPO_PAGAMENTO
                        ORDER BY 1 desc";

        $statementList   = $dbAdapter->query($week);
        // $results = $statement->execute(array($date . ' 00:00:00', $date . ' 23:59:59'));
        $results = $statementList->execute();
        $returnListWeek = array();
        foreach ($results as $result) {
            $returnListWeek[] = array(
                'paymentType' => $result['paymentType'] != '' ? utf8_encode($result['paymentType']) : 'Não especificado',
                'totalByType' => $result['totalByType']
            );
        }

        $weekDay = " select     sunday = ( select sum(VL_TOTAL_LIQUIDO) from TB_PEDIDO where DT_PEDIDO between '".date('Ymd',strtotime('last sunday'))." 00:00:00' and '".date('Ymd',strtotime('last sunday'))." 23:59:59' AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )) ,
                                monday = ( select sum(VL_TOTAL_LIQUIDO) from TB_PEDIDO where DT_PEDIDO between '".date('Ymd',strtotime('last monday'))." 00:00:00' and '".date('Ymd',strtotime('last monday'))." 23:59:59' AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )),
                            tuesday = ( select sum(VL_TOTAL_LIQUIDO) from TB_PEDIDO where DT_PEDIDO between '".date('Ymd',strtotime('last tuesday'))." 00:00:00' and '".date('Ymd',strtotime('last tuesday'))." 23:59:59' AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )),
                            wednesday = ( select sum(VL_TOTAL_LIQUIDO) from TB_PEDIDO where DT_PEDIDO between '".date('Ymd',strtotime('last wednesday'))." 00:00:00' and '".date('Ymd',strtotime('last wednesday'))." 23:59:59' AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )),
                            thursday = ( select sum(VL_TOTAL_LIQUIDO) from TB_PEDIDO where DT_PEDIDO between '".date('Ymd',strtotime('last thursday'))." 00:00:00' and '".date('Ymd',strtotime('last thursday'))." 23:59:59' AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )),
                            friday = ( select sum(VL_TOTAL_LIQUIDO) from TB_PEDIDO where DT_PEDIDO between '".date('Ymd',strtotime('last friday'))." 00:00:00' and '".date('Ymd',strtotime('last friday'))." 23:59:59' AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )),
                            saturday = ( select sum(VL_TOTAL_LIQUIDO) from TB_PEDIDO where DT_PEDIDO between '".date('Ymd',strtotime('last saturday'))." 00:00:00' and '".date('Ymd',strtotime('last saturday'))." 23:59:59' AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' ))
                ";
        $statementList   = $dbAdapter->query($weekDay);
        $results = $statementList->execute();
        foreach ($results as $result) {
            $returnDaysWeek = array(
                array( 'day' => 'Domingo',  'total' => $result['sunday'] ? $result['sunday'] : 0.00),
                array( 'day' => 'Segunda',  'total' => $result['monday'] ? $result['monday'] : 0.00),
                array( 'day' => 'Terça',    'total' => $result['tuesday'] ? $result['tuesday'] : 0.00),
                array( 'day' => 'Quarta',   'total' => $result['wednesday'] ? $result['wednesday'] : 0.00),
                array( 'day' => 'Quinta',   'total' => $result['thursday'] ? $result['thursday'] : 0.00),
                array( 'day' => 'Sexta',    'total' => $result['friday'] ? $result['friday'] : 0.00),
                array( 'day' => 'Sábado',   'total' => $result['saturday'] ? $result['saturday'] : 0.00)
            );
        }

        $date = date('Ymd',strtotime('first day of this month'));
        $secao 		= " select top 10 typeName = tms.DS_TIPO_MERCADORIA,
                                totalByType = sum(pm.VL_TOTAL_LIQUIDO)
                        from TB_PEDIDO p
                        inner join TB_PEDIDO_MERCADORIA pm on pm.NR_PEDIDO = p.NR_PEDIDO
                        inner join TB_MERCADORIA m on m.CD_MERCADORIA = pm.CD_MERCADORIA
                        inner join TB_TIPO_MERCADORIA_SECAO tms on tms.CD_TIPO_MERCADORIA = m.CD_TIPO_MERCADORIA
                        WHERE DT_PEDIDO BETWEEN '".$date." 00:00:00' AND '".date('Ymd')." 23:59:59'
                            AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )
                        GROUP BY tms.DS_TIPO_MERCADORIA
                        ORDER BY sum(pm.VL_TOTAL_LIQUIDO) desc";

        $statementList   = $dbAdapter->query($secao);
        $results = $statementList->execute();
        $returnSecao = array();
        foreach ($results as $result) {
            $returnSecao[] = array(
                'typeName' => utf8_encode($result['typeName']),
                'totalByType' => $result['totalByType']
            );
        }

        $products 		= " select top 50 productName = m.DS_MERCADORIA,
                                totalByProduct = sum(pm.VL_TOTAL_LIQUIDO),
                                estoque = e.NR_QTDE_ESTOQUE
                        from TB_PEDIDO p
                        left join TB_PEDIDO_MERCADORIA pm on pm.NR_PEDIDO = p.NR_PEDIDO
                        left join TB_MERCADORIA m on m.CD_MERCADORIA = pm.CD_MERCADORIA
                        left join TB_ESTOQUE e on e.CD_MERCADORIA = m.CD_MERCADORIA and p.CD_LOJA = e.CD_LOJA
                        WHERE DT_PEDIDO BETWEEN '".$date." 00:00:00' AND '".date('Ymd')." 23:59:59'
                            AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )
                        GROUP BY m.DS_MERCADORIA, e.NR_QTDE_ESTOQUE
                        ORDER BY sum(pm.VL_TOTAL_LIQUIDO) desc";

        $statementList   = $dbAdapter->query($products);
        $results = $statementList->execute();
        $returnTopProducts = array();
        foreach ($results as $result) {
            $returnTopProducts[] = array(
                'productName' => utf8_encode($result['productName']),
                'totalByProduct' => $result['totalByProduct'],
                'estoque'   => $result['estoque']
            );
        }

        $daysCurrentMonth = "
                        select sum(VL_TOTAL_LIQUIDO) as total,
                                DAY(DT_PEDIDO) as dayMonth,
                                CASE
                                    WHEN DATEPART(month, DT_PEDIDO) = 1 THEN 'Janeiro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 2 THEN 'Fevereiro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 3 THEN 'Março'
                                    WHEN DATEPART(month, DT_PEDIDO) = 4 THEN 'Abril'
                                    WHEN DATEPART(month, DT_PEDIDO) = 5 THEN 'Maio'
                                    WHEN DATEPART(month, DT_PEDIDO) = 6 THEN 'Junho'
                                    WHEN DATEPART(month, DT_PEDIDO) = 7 THEN 'Julho'
                                    WHEN DATEPART(month, DT_PEDIDO) = 8 THEN 'Agosto'
                                    WHEN DATEPART(month, DT_PEDIDO) = 9 THEN 'Setembro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 10 THEN 'Outubro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 11 THEN 'Novembro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 12 THEN 'Dezembro'
                                    ELSE ''
                                END as monthName
                        from TB_PEDIDO
                        WHERE DT_PEDIDO BETWEEN '".$date." 00:00:00' AND '".date('Ymd')." 23:59:59'
                            AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )
                        GROUP BY DAY(DT_PEDIDO), DATEPART(month, DT_PEDIDO)
                        ORDER BY DAY(DT_PEDIDO) asc";

        $statementList   = $dbAdapter->query($daysCurrentMonth);
        $results = $statementList->execute();
        $returnDaysCurrentMonth = array();
        foreach ($results as $result) {
            $returnDaysCurrentMonth[] = array(
                'dayMonth'   => $result['dayMonth'],
                'total'     => $result['total'],
                'monthName' => $result['monthName']
            );
        }

        $daysCurrentMonth_1 = " select sum(VL_TOTAL_LIQUIDO) as total,
                                DAY(DT_PEDIDO) as dayMonth,
                                CASE
                                    WHEN DATEPART(month, DT_PEDIDO) = 1 THEN 'Janeiro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 2 THEN 'Fevereiro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 3 THEN 'Março'
                                    WHEN DATEPART(month, DT_PEDIDO) = 4 THEN 'Abril'
                                    WHEN DATEPART(month, DT_PEDIDO) = 5 THEN 'Maio'
                                    WHEN DATEPART(month, DT_PEDIDO) = 6 THEN 'Junho'
                                    WHEN DATEPART(month, DT_PEDIDO) = 7 THEN 'Julho'
                                    WHEN DATEPART(month, DT_PEDIDO) = 8 THEN 'Agosto'
                                    WHEN DATEPART(month, DT_PEDIDO) = 9 THEN 'Setembro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 10 THEN 'Outubro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 11 THEN 'Novembro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 12 THEN 'Dezembro'
                                    ELSE ''
                                END as monthName
                        from TB_PEDIDO
                        WHERE DT_PEDIDO BETWEEN DATEADD(month, -1, '".$date." 00:00:00') AND DATEADD(month, -1, '".date('Ymd')." 23:59:59')
                            AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )
                        GROUP BY DAY(DT_PEDIDO),DATEPART(month, DT_PEDIDO)
                        ORDER BY DAY(DT_PEDIDO) asc";

        $statementList   = $dbAdapter->query($daysCurrentMonth_1);
        $results = $statementList->execute();
        $returnDaysCurrentMonth_1 = array();
        foreach ($results as $result) {
            $returnDaysCurrentMonth_1[] = array(
                'dayMonth'   => $result['dayMonth'],
                'total'     => $result['total'],
                'monthName' => $result['monthName']
            );
        }

        $daysCurrentMonth_2 = " select sum(VL_TOTAL_LIQUIDO) as total,
                                DAY(DT_PEDIDO) as dayMonth,
                                CASE
                                    WHEN DATEPART(month, DT_PEDIDO) = 1 THEN 'Janeiro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 2 THEN 'Fevereiro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 3 THEN 'Março'
                                    WHEN DATEPART(month, DT_PEDIDO) = 4 THEN 'Abril'
                                    WHEN DATEPART(month, DT_PEDIDO) = 5 THEN 'Maio'
                                    WHEN DATEPART(month, DT_PEDIDO) = 6 THEN 'Junho'
                                    WHEN DATEPART(month, DT_PEDIDO) = 7 THEN 'Julho'
                                    WHEN DATEPART(month, DT_PEDIDO) = 8 THEN 'Agosto'
                                    WHEN DATEPART(month, DT_PEDIDO) = 9 THEN 'Setembro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 10 THEN 'Outubro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 11 THEN 'Novembro'
                                    WHEN DATEPART(month, DT_PEDIDO) = 12 THEN 'Dezembro'
                                    ELSE ''
                                END as monthName
                        from TB_PEDIDO
                        WHERE DT_PEDIDO BETWEEN DATEADD(month, -2, '".$date." 00:00:00') AND DATEADD(month, -2, '".date('Ymd')." 23:59:59')
                            AND ( ST_PEDIDO = 'F' OR ST_PEDIDO = 'P1' )
                        GROUP BY DAY(DT_PEDIDO), DATEPART(month, DT_PEDIDO)
                        ORDER BY DAY(DT_PEDIDO) asc";

        $statementList   = $dbAdapter->query($daysCurrentMonth_2);
        $results = $statementList->execute();
        $returnDaysCurrentMonth_2 = array();
        foreach ($results as $result) {
            $returnDaysCurrentMonth_2[] = array(
                'dayMonth'   => $result['dayMonth'],
                'total'     => $result['total'],
                'monthName' => $result['monthName']
            );
        }

        $myArrayofData = array(
            'totalToday'            => $returnToday['today'] ? $returnToday['today'] : 0.00,
            'listToday'             => $returnListToday,
            'totalWeek'             => $returnWeek['week'] ? $returnWeek['week'] : 0.00,
            'listWeek'              => $returnListWeek,
            'daysWeek'              => $returnDaysWeek,
            'daysCurrentMonth'      => $returnDaysCurrentMonth,
            'daysCurrentMonth_1'    => $returnDaysCurrentMonth_1,
            'daysCurrentMonth_2'    => $returnDaysCurrentMonth_2,
            'sessaoList'            => $returnSecao,
            'listProducts'          => $returnTopProducts
        );

        header("Access-Control-Allow-Origin: *");
        $response = $this->getResponse();
        $response->getHeaders()->addHeaderLine( 'Content-Type', 'application/json' );
        $response->setContent(json_encode($myArrayofData));
        return $response;
    }

    public function loginAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            // get post data
            $post = $request->getPost();

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
					//->setCredentialTreatment('ST_ATIVO = "S"');

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
				// if( $res['ST_RECEBER_CHAVE'] == 'S' ){
					//set in session the value of LOJA selected
					$session = new Container("orangeSessionContainer");
					$session->cdLoja = '1';
					$session->cdBase = $res['CD_LOJA'];
					$session->usuario = $res['DS_USUARIO'];
					//$session->stGerente = ($rst['st_gerente'] == "S") ? true : false;
					//$session->cdFuncionario = $rst['CD_FUNCIONARIO'];

					// redirect to dashboard page

                    $return = array(
                        'cdBase'        => $res['CD_LOJA'],
                        'company'       => $res['DS_FANTASIA'],
                        'usuario'       => $res['DS_USUARIO']
                    );

                    $contacts = array( 'foo' => 'bar' );
                    header("Access-Control-Allow-Origin: *");
                    $response = $this->getResponse();
                    $response->getHeaders()->addHeaderLine( 'Content-Type', 'application/json' );
                    $response->setContent(json_encode($return));
                    return $response;
				// }else{
				// 	$this->flashMessenger()->addMessage("Cliente n&atilde;o habilitado");
				// 	return $this->redirect()->toRoute('home');
				// }
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

}
