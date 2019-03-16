<?php
/**
 * Classe responsável pelo acesso REST das entidades
 *
 * @category Api
 * @package  Controller
 * @author   Higor <higgor.m@gmail.com>
 */

 namespace Api\Controller;

 use Zend\Mvc\Controller\AbstractRestfulController;
 use Zend\View\Model\JsonModel;
 use Zend\Http\Response;
 use Zend\Http\Request;
 use Zend\Http\Headers;
 use Application\Service\NotaFiscalService;

 class NotaFiscalRestController extends AbstractRestfulController {

     /**
      * Retorna uma lista de entidades
      *
      *
      * @return array
      */
     public function getList()
     {
         return new JsonModel(array('mensagem'=> 'Nada a listar'));

         //$notaFiscalService = new NotaFiscalService( $this->getServiceLocator(),541);
         //$listaNfe   = $notaFiscalService->getArrayList(array());
         //return new JsonModel($listaNfe);
     }

     /**
      * Retorna uma única entidade
      *
      *
      * @param int $id  Id da entidade
      *
      * @return array
      */
     public function get($id)
     {
         // TODO: Implement get() method.
         return new JsonModel(array('mensagem'=> 'Nada a listar por id'));
     }

     /**
      * Cria uma nova entidade
      *
      * @param array $data  Dados da entidade sendo salva
      *
      * @return array
      */
     public function create ($data)
     {
         //$this­>response­>getHeaders()­>addHeaderLine('Content­ type','applicationjson');
         if (empty($data)) {
             //$this­>response­>setStatusCode(Response::STATUS_CODE_400);
             return new JsonModel(array('mensagem'=>'Nenhum dado foi  enviado'));
         }
     }

     /**
      * Atualiza uma entidade
      * @param  int $id O código da entidade a ser atualizada
      * @param  array $data Os dados sendo alterados
      *
      * @return array       Retorna a entidade atualizada
      */
     public function update($id, $data)
     {
         //$this­>response­>setStatusCode(Response::STATUS_CODE_401);
         return new JsonModel(array('mensagem'=>'Você precisa estar  logado'));
     }

     /**
      * Exclui uma entidade
      *
      * @param  int $id Id da entidade sendo excluída
      *
      * @return int
      */
      public function delete($id)
      {
        //$this­>response­>setStatusCode(Response::STATUS_CODE_401);
        return new JsonModel(array('mensagem'=>'Você precisa estar  logado'));
      }
 }