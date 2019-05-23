<?php

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

class NotaInutilizadaTable extends AbstractTableGateway {

    protected $table = "TB_NFE_INUTILIZADAS";

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new NotaInutilizada());
        $this->initialize();

        $session = new Container("orangeSessionContainer");
        if( @$session->cdBase ){
            $statement = $this->adapter->query("USE BDGE_".$session->cdBase);
            $statement->execute();
        }
    }

    public function listHistorico()
    {
        $select     = 'SELECT 
                           TP_AMBIENTE
                          ,NR_SERIE
                          ,NR_ANO
                          ,NR_FAIXA_INICIAL
                          ,NR_FAIXA_FINAL
                          ,DS_JUSTIFICATIVA
                          ,DS_X_MOTIVO
                          ,CONVERT(VARCHAR(20), DH_RECEBIMENTO, 120) AS DH_RECEBIMENTO 
                          ,TP_MODELO
                          ,NR_VERSAO
                          ,NR_VERSAO_APLICACAO
                          ,CD_UF
                          ,NU_CNPJ
                          ,NU_PROTOCOLO
                          ,BSTAT
                          ,CSTAT
                FROM '. $this->table . ' 
               
                ORDER BY CD_NFE_INUTILILIZADA DESC';
        $statement  = $this->adapter->query($select);
        $result     = $statement->execute();
        return iterator_to_array($result,true);
    }

    public function nextId()
    {
        $select = $this->getSql()->select();
        $select->columns(array(new Expression('ISNULL(MAX(cd_nfe_inutililizada),0) + 1 as cd_nfe_inutililizada')));
        $rowset = $this->selectWith($select);
        $row = $rowset->current();

        if (!row) {
            return 1;
        }

        return $row->cd_nfe_inutililizada;
    }

    public function save($tableData, $respostaSefaz)
    {
        $isNew = false;

        if(is_array($respostaSefaz)) {
            $respostaSefaz = (object) $respostaSefaz;
        }

        try {
            if ($tableData->cd_nfe_inutililizada) {
                $base = $this->getId($tableData->cd_nfe_inutililizada);
            } else {
                $isNew= true;
            }

            $data = array(
                "ds_justificativa"  => (isset($tableData->ds_justificativa))  ? utf8_decode($tableData->ds_justificativa)    : $base->ds_justificativa,
                "cd_usuario"        => (isset($tableData->cd_usuario))     ? $tableData->cd_usuario : $base->cd_usuario,
                "cd_loja"           => (isset($tableData->cd_loja))        ? $tableData->cd_loja    : $base->cd_loja,

                "tp_ambiente"       => (isset($respostaSefaz->tpAmb))      ? $respostaSefaz->tpAmb    : $base->tp_ambiente,
                "tp_modelo"         => (isset($respostaSefaz->mod))        ? $respostaSefaz->mod      : $base->tp_modelo,
                "nr_serie"          => (isset($respostaSefaz->serie))      ? $respostaSefaz->serie    : $base->nr_serie,
                "nr_ano"            => (isset($respostaSefaz->ano))        ? $respostaSefaz->ano      : $base->nr_ano,
                "nr_faixa_inicial"  => (isset($respostaSefaz->nNFIni))     ? $respostaSefaz->nNFIni   : $base->nr_faixa_inicial,
                "nr_faixa_final"    => (isset($respostaSefaz->nNFFin))     ? $respostaSefaz->nNFFin   : $base->nr_faixa_final,
                "ds_x_motivo"       => (isset($respostaSefaz->xMotivo))    ? $respostaSefaz->xMotivo  : $base->ds_x_motivo,
                "dh_recebimento"    => (isset($respostaSefaz->dhRecbto))   ? $respostaSefaz->dhRecbto : $base->dh_recebimento,
                "nr_versao"         => (isset($respostaSefaz->versao))     ? $respostaSefaz->versao   : $base->nr_versao,
                "nr_versao_aplicacao" => (isset($respostaSefaz->verAplic)) ? $respostaSefaz->verAplic : $base->nr_versao_aplicacao,
                "cd_uf"             => (isset($respostaSefaz->cUF))        ? $respostaSefaz->cUF      : $base->cd_uf,
                "nu_cnpj"           => (isset($respostaSefaz->CNPJ))       ? $respostaSefaz->CNPJ     : $base->nu_cnpj,
                "nu_protocolo"      => (isset($respostaSefaz->nProt))      ? $respostaSefaz->nProt    : $base->nu_protocolo,
                "bStat"             => (isset($respostaSefaz->bStat))      ? $respostaSefaz->bStat    : $base->bstat,
                "cStat"             => (isset($respostaSefaz->cStat))      ? $respostaSefaz->cStat    : $base->cstat,
            );

            if ($isNew) {
                $data["cd_nfe_inutililizada"] = $this->nextId();
                $dhEmi = date(FORMATO_ESCRITA_DATA_HORA, strtotime($data['dh_recebimento']));
                $data['dh_recebimento'] = $dhEmi;
            }
//var_dump($tableData,$respostaSefaz,$data);exit;
            if (!$isNew) {
                if(!$this->update($data, array("cd_nfe_inutililizada" => $tableData->cd_nfe_inutililizada)))
                    throw new \Exception ;
            } else {
                if(!$this->insert($data))
                    throw new \Exception;
            }
            return $data['cd_nfe_inutililizada'];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}