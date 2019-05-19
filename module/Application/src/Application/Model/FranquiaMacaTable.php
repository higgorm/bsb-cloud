<?php

namespace Application\Model;

use Zend\Db\Sql\Where;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Sql;
use Zend\Session\Container;

class FranquiaMacaTable extends AbstractTableGateway {

    protected $table = "TB_FRANQUIA_MACA";

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new FranquiaMaca());
        $this->initialize();
		
		$session = new Container("orangeSessionContainer");
		if( @$session->cdBase ){
			$statement = $this->adapter->query("USE BDGE_".$session->cdBase);
			$statement->execute();
		}
    }

    public function getId($cdLoja, $nrMaca) {

        $select = $this->getSql()->select();
        $select->columns(array('cd_loja',
                    'nr_maca',
                    'ds_identificacao',
                    'cd_funcionario',
                ))
                ->where(array('cd_loja' => $cdLoja))
                ->where(array('nr_maca' => $nrMaca));

        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            throw new \Exception("Maca $nrMaca  não existe no banco de dados!");
        }

        return $row;
    }

    public function save(FranquiaMaca $tableData) {
        $data = array(
            "cd_loja" => $tableData->cd_loja,
            "nr_maca" => $tableData->nr_maca,
            "ds_identificacao" => $tableData->ds_identificacao,
            "cd_funcionario" => $tableData->cd_funcionario,
        );

        $nrMaca = (int) $tableData->nr_maca;
        $cdLoja = (int) $tableData->cd_loja;

        if ($this->getId($cdLoja, $nrMaca)) {
            $this->update($data, array("cd_loja" => $cdLoja, "nr_maca" => $nrMaca));
        } else {

            $nextVal = "SELECT ISNULL(MAX(nr_maca),0)+1 AS nr_maca FROM dbo.TB_FRANQUIA_MACA where CD_LOJA = ? ";
            $statement = $this->adapter->createStatement($nextVal);
            $result = $statement->execute(array("cd_loja"=>$cdLoja));

            foreach ($result as $res) {
                $data['nr_maca'] = $res['nr_maca'];
            }
            $this->insert($data);
        }
    }

    public function remove($cdLoja, $nrMaca) {

        if ($this->getId($cdLoja, $nrMaca)) {
            $this->delete(array("cd_loja" => $cdLoja, "nr_maca" => $nrMaca));
        } else {
            throw new \Exception("Maca $nrMaca  n�o existe no banco de dados!");
        }
    }

    public function fetchAll(Array $param = array(), $currentPage = "1", $countPerPage = "10") {
        $select = new Select();
        $where = new Where();

        foreach ($param as $field => $search) {
            $where->like($field, '%' . $search . '%');
        }

        $select->from($this->table)->where($where)->order("DS_NOME_RAZAO_SOCIAL ");

        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($currentPage);
        $paginator->setItemCountPerPage($countPerPage);

        return $paginator;
    }

    public function getMacas($cdLoja, $cdFuncionario = null) {

        $select = $this->getSql()->select();
        $select->columns(array('cd_loja',
                    'nr_maca',
                    'ds_identificacao',
                    'cd_funcionario',
                ))
                ->join(array('F' => 'TB_FUNCIONARIO'), 'TB_FRANQUIA_MACA.cd_funcionario = F.cd_funcionario', array(), 'left')
                ->where(array('TB_FRANQUIA_MACA.cd_loja' => $cdLoja));

        if ($cdFuncionario != null) {
            $select->where(array('F.cd_funcionario' => $cdFuncionario));
        }

        return $this->selectWith($select);
    }

    public function getMacaFunctionario($cdLoja,$cdFuncionario)
    {
        $select = $this->getSql()->select();
        $select->columns(array('cd_loja',
                    'nr_maca',
                    'ds_identificacao',
                    'cd_funcionario',
                ))
                ->where(array('cd_loja' => $cdLoja))
                ->where(array('cd_funcionario' => $cdFuncionario));
        //echo $select->getSqlString();exit;
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        return $row;
    }

    public function getAgendamento($cdLoja, $nrMaca, $dtInicio, $dtFim) {

        $statement = $this->adapter->query("select
                                                        tfm.NR_MACA,
                                                        tfm.DS_IDENTIFICACAO,
                                                        taf.DT_HORARIO,
                                                        DS_NOME_RAZAO_SOCIAL = CASE WHEN ( tc.CD_CLIENTE IS NULL or taf.CD_CLIENTE = 1 ) THEN FCR.DS_NOME ELSE tc.DS_NOME_RAZAO_SOCIAL END,
                                                        tc.CD_CLIENTE
                                                    from TB_FRANQUIA_MACA tfm
                                                    left join TB_AGENDAMENTO_FRANQUIA taf on tfm.CD_LOJA = taf.CD_LOJA and tfm.NR_MACA = taf.NR_MACA
                                                    left join TB_CLIENTE tc on taf.CD_CLIENTE = tc.CD_CLIENTE
                                                    left join TB_FRANQUIA_CLIENTE_RAPIDO fcr on taf.CD_CLIENTE = fcr.CD_CLIENTE
                                                    where tfm.CD_LOJA = ?
                                                            and tfm.NR_MACA = ?
                                                            and CONVERT(VARCHAR(10),taf.DT_HORARIO,103) between CONVERT(VARCHAR(10),?,103) and CONVERT(VARCHAR(10),?,103)
                                                    order by tfm.NR_MACA, taf.DT_HORARIO");

        $results = $statement->execute(array($cdLoja, $nrMaca, $dtInicio, $dtFim));
        $returnArray = array();

        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function getAgendamentoMacas($cdLoja, $dtInicio, $dtFim, $cdFuncionario='') {

        $dtInicio =  $dtInicio . ' 00:00:00';
        $dtFim = $dtFim . ' 23:59:59';

        $sql = "SELECT DISTINCT a.CD_CLIENTE, a.DT_HORARIO, a.NR_MACA,
                    DS_CLIENTE = CASE WHEN ( C.CD_CLIENTE IS NULL or a.cd_cliente = 1 ) THEN SUBSTRING(CR.DS_NOME,0,10) ELSE SUBSTRING(C.DS_NOME_RAZAO_SOCIAL,0,10) END,
                    --DS_CLIENTE = CASE WHEN ( C.CD_CLIENTE IS NULL or a.cd_cliente = 1 ) THEN left(CR.DS_NOME,CHARINDEX(' ',CR.DS_NOME)) ELSE left(C.DS_NOME_RAZAO_SOCIAL,CHARINDEX(' ',C.DS_NOME_RAZAO_SOCIAL)) END,
                    DS_FONE1 = CASE WHEN ( C.CD_CLIENTE IS NULL or a.cd_cliente = 1 ) THEN CR.DS_FONE1 ELSE C.DS_FONE1 END,
                    ST_PEDIDO = ISNULL( p.ST_PEDIDO, 'A' ), p.NR_PEDIDO,
                    ST_CLIENTE_RAPIDO = CASE WHEN (C.CD_CLIENTE IS NULL OR C.CD_CLIENTE = '1') THEN 'S' ELSE 'N' END,
                    CASE
                        WHEN (GETDATE() > a.DT_HORARIO AND A.ST_CLIENTE_CHEGOU = 'N') THEN '#299900' --atrasado
                        WHEN (P.ST_PEDIDO = 'A' AND A.ST_CLIENTE_CHEGOU = 'S') THEN '#FFC0CB' --em atendimento
                        WHEN (P.ST_PEDIDO = 'C' AND A.ST_CLIENTE_CHEGOU = 'N') THEN 'red' --cancelado
                        WHEN (P.ST_PEDIDO = 'F' AND A.ST_CLIENTE_CHEGOU = 'S') THEN '#0099FF' --fechado
                        WHEN (Convert(char(8),GETDATE(),108) between Convert(char(8),dateadd(mi,-5,A.DT_HORARIO),108) and Convert(char(8),A.DT_HORARIO,108)) THEN 'yellow' -- 5 minutos para atendimento
                        ELSE '#FFA500'
                    END as COR,
                    M.DS_IDENTIFICACAO
                FROM TB_AGENDAMENTO_FRANQUIA A
                    join TB_FRANQUIA_MACA M on (A.NR_MACA = M.NR_MACA AND A.CD_LOJA = M.CD_LOJA)
                    LEFT JOIN TB_CLIENTE C ON A.CD_CLIENTE = C.CD_CLIENTE
                    LEFT JOIN TB_FRANQUIA_CLIENTE_RAPIDO CR ON A.CD_CLIENTE_RAPIDO = CR.CD_CLIENTE
                    LEFT JOIN TB_PEDIDO P ON P.CD_LOJA = A.CD_LOJA and P.NR_PEDIDO = A.NR_PEDIDO
                WHERE  1=1
                    AND A.CD_LOJA = ?
                    AND A.DT_HORARIO between ? and ? 
                    AND  (P.ST_PEDIDO  <> 'C' OR P.ST_PEDIDO IS NULL) ";

        if(!empty($cdFuncionario))
        {
            $sql .= " and M.CD_FUNCIONARIO = ? ";
            $parametros = array($cdLoja, $dtInicio, $dtFim, $cdFuncionario);
        }
        else
        {
            $parametros = array($cdLoja, $dtInicio, $dtFim);
        }

        $sql .= " ORDER BY A.DT_HORARIO";

        $statement = $this->adapter->query($sql);

        return $statement->execute($parametros);
    }

    public function verificaAgenda($cdLoja, $dtInicio, $dtFim) {
        $statement = $this->adapter->query("select case when (tc.CD_CLIENTE IS null) then tfcr.DS_NOME else tc.DS_NOME_RAZAO_SOCIAL end as DS_NOME_RAZAO_SOCIAL, tfm.DS_IDENTIFICACAO
                                            from TB_AGENDAMENTO_FRANQUIA taf
                                            join TB_FRANQUIA_MACA tfm on taf.CD_LOJA = tfm.CD_LOJA and taf.NR_MACA = tfm.NR_MACA
                                            left join TB_CLIENTE tc on taf.CD_CLIENTE = tc.CD_CLIENTE
                                            left join TB_FRANQUIA_CLIENTE_RAPIDO tfcr on taf.CD_CLIENTE = tfcr.CD_CLIENTE
                                            where taf.CD_LOJA = ?
                                                    and taf.DT_HORARIO between ? and ? ");

        return $statement->execute(array($cdLoja, $dtInicio, $dtFim));
    }

}
