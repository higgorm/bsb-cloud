<?php
//e.Guilherme
namespace Application\Model;

use Zend\Db\Sql\Where;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;

class ContasPagarTable extends AbstractTableGateway
{

    protected $table = "TB_CONTAS_PAGAR";

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }

    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10")
    {
        $select = new Select();
        $where = new Where();

        foreach ($param as $field => $search) {
            if($field == 'CD_TIPO_PAGAMENTO'){
                $where->like('P.CD_TIPO_PAGAMENTO',$search);
            }else if($field == 'minValor'){
                $where->expression('R.VL_DOCUMENTO >= ?',$search);
            }else if($field == 'maxValor'){
                $where->expression('R.VL_DOCUMENTO <= ?',$search);
            }else if($field == 'dtIni'){
                $where->expression('DT_MOVIMENTO >= ?',$search);
            }else if($field == 'dtFim'){
                $where->expression('DT_MOVIMENTO <= ?',$search);
            }else if($field == 'CD_LOJA'){
                $where->like('R.CD_LOJA', $search);
            }else if($field == 'VL_DOCUMENTO'){
                $where->like('R.VL_DOCUMENTO', $search.'%');
            }else{
                $where->like($field, '%' . $search . '%');
            }
        }

        $select->from(array('R' => $this->table))
            ->join(array('P' => 'TB_CONTASPAGAR_PAGAMENTO'), 'P.NR_DOCUMENTO_CP = R.NR_DOCUMENTO_CP','CD_TIPO_PAGAMENTO','LEFT')
            ->join(array('T' => 'TB_TIPO_PAGAMENTO'), 'T.CD_TIPO_PAGAMENTO = P.CD_TIPO_PAGAMENTO','DS_TIPO_PAGAMENTO','LEFT')
            ->join(array('F' => 'TB_FORNECEDOR'), 'R.CD_FORNECEDOR = F.CD_FORNECEDOR', 'DS_RAZAO_SOCIAL','LEFT')
            ->where($where)
            ->where(' P.NR_DOCUMENTO_CP_SEQ IN (SELECT MIN(NR_DOCUMENTO_CP_SEQ) FROM TB_CONTASPAGAR_PAGAMENTO) ')
            ->order("DT_MOVIMENTO DESC");
        //echo $select->getSqlString();
        //exit;
        $adapter = new DbSelect($select, $this->adapter);

        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function nextId($cdLoja)
    {

        $select = new Select();
        $select ->from($this->table)
            ->columns(array(new Expression('ISNULL(MAX(NR_DOCUMENTO_CP),0)+1 as NR_DOCUMENTO_CP')))
            ->where(new Expression('CD_LOJA = '.$cdLoja));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->NR_DOCUMENTO_CP;
    }

    public function save ($alt){

        try {
            $result = $this->insert($alt);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $result;
    }

    public function selectById($cdLoja,$id){

        $statement = $this->adapter->query('SELECT * FROM '.$this->table.'
        WHERE CD_LOJA = '.$cdLoja.' AND NR_DOCUMENTO_CP = '.$id);

        $result = $statement->execute();
        foreach ($result as $field => $search) {
            $selectData[$field] = $search;
        }

        return $selectData;
    }

    public function selectQtdParcelas($cdLoja,$id){

        $statement = $this->adapter->query('SELECT
                count(B.NR_DOCUMENTO_CP_SEQ) as qtd_parcelas,
                min(B.DT_VENCIMENTO) as vencimento

                FROM TB_CONTAS_PAGAR as A
                LEFT JOIN TB_CONTASPAGAR_PAGAMENTO as B
                ON A.NR_DOCUMENTO_CP = B.NR_DOCUMENTO_CP

                WHERE A.CD_LOJA = '.$cdLoja.' AND A.NR_DOCUMENTO_CP = '.$id);

        $result = $statement->execute();

        foreach ($result as $field => $search) {
            $selectData[$field] = $search;
        }

        return $selectData['2'];
    }

    public function change ($alt,$cdLoja,$cdSeq){
        try {
            $result = $this->update($alt,'CD_LOJA = '.$cdLoja.' AND NR_DOCUMENTO_CP = '.$cdSeq);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $result;
    }

}