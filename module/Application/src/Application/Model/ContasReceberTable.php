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

class ContasReceberTable extends AbstractTableGateway
{

    protected $table = "TB_CONTAS_RECEBER";

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
        $periodo = 0;
        $dtIni = '10/10/1993';

        foreach ($param as $field => $search) {

            if($field == 'CD_TIPO_PAGAMENTO'){
                $where->like('R.CD_TIPO_PAGAMENTO', '%' . $search . '%');
            }else if($field == 'minValor'){
                $where->expression('VL_DOCUMENTO >= ?',$search);
            }else if($field == 'maxValor'){
                $where->expression('VL_DOCUMENTO <= ?',$search);
            }else if($field == 'rdVencimento'){
                if($search == '0'){
                    $periodo = '0';
                }else if($search == '1'){
                    $periodo = '1';
                }
            }else if($field == 'dtIni'){
                $dtIni = $search;
            }else if($field == 'dtFim'){
                if($periodo == '0'){
                    $where  ->expression('R.DT_VENCIMENTO >= ?', $dtIni);
                    $where  ->expression('R.DT_VENCIMENTO <= ?', $search);
                }else if($periodo == '1'){
                    $where  ->expression('R.DT_EMISSAO >= ?', $dtIni);
                    $where  ->expression('R.DT_EMISSAO <= ?', $search);
                }
            }else if($field == 'rdApenas'){
                if($search == '1'){
                    $where->isNotNull('DT_BAIXA');
                }else if($search == '2'){
                    $where->isNull('DT_BAIXA');
                }
            }else if($field == 'CD_LOJA'){
                $where->like('R.CD_LOJA', $search);
            }else{
                $where->like($field, '%' . $search . '%');
            }
        }

        $select->from(array('R' => $this->table))
               ->join(array('C' =>'TB_CLIENTE'),'R.CD_CLIENTE = C.CD_CLIENTE', array('DS_NOME_RAZAO_SOCIAL'), 'left')
               ->join(array('T' => 'TB_TIPO_PAGAMENTO'), 'T.CD_TIPO_PAGAMENTO = R.CD_TIPO_PAGAMENTO')
               ->columns(array('R' => 'CD_CLIENTE',
                                'NR_LANCAMENTO_CR',
                                'NR_DOCUMENTO_CR',
                                'VL_DOCUMENTO',
                                'NR_NOTA',
                                'EMISSAO' => 'DT_EMISSAO',
                                'DT_VENCIMENTO',
                                'CD_CLIENTE'

                            ))

            ->where($where)
            ->order("NR_LANCAMENTO_CR DESC");

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
                ->columns(array(new Expression('COALESCE(max(NR_LANCAMENTO_CR),0)+1 as NR_LANCAMENTO_CR')))
                ->where(new Expression('CD_LOJA = '.$cdLoja));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->NR_LANCAMENTO_CR;
    }

    public function save ($alt){

        try {
            $result = $this->insert($alt);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $result;
    }

    public function selectById($id,$cdLoja){

        $statement = $this->adapter->query('SELECT * FROM TB_CONTAS_RECEBER WHERE CD_LOJA = '.$cdLoja.' AND NR_LANCAMENTO_CR = '.$id);

        $result = $statement->execute();
        foreach ($result as $field => $search) {
            $selectData[$field] = $search;
        }

        return $selectData;
    }

    public function change ($alt,$cdLoja,$cdSeq){
        try {
            $result = $this->update($alt,'CD_LOJA = '.$cdLoja.' AND NR_LANCAMENTO_CR = '.$cdSeq);
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }
        return $result;
    }

}