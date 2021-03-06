ALTER TABLE TB_LOJA ADD ST_WEB CHAR(1) DEFAULT NULL;

ALTER TABLE TB_NFE ADD DMED_NOME VARCHAR(255);
ALTER TABLE TB_NFE ADD DMED_CPF VARCHAR(15);
ALTER TABLE TB_NFE ADD DMED_NASCIMENTO DATE;
ALTER TABLE TB_NFE ADD CD_CLIENTE INT DEFAULT NULL;
ALTER TABLE TB_NFE ADD CFOP INT DEFAULT NULL;

ALTER TABLE TB_NFE_CONFIG ADD CD_NATUREZA_OPERACAO INT;
ALTER TABLE TB_NFE_CONFIG ADD CD_FORMA_PAGAMENTO INT;
ALTER TABLE TB_NFE_CONFIG ADD RETENCAO_pPIS NUMERIC(10,2);
ALTER TABLE TB_NFE_CONFIG ADD RETENCAO_pCOFINS NUMERIC(10,2);
ALTER TABLE TB_NFE_CONFIG ADD RETENCAO_pCSLL NUMERIC(10,2);
ALTER TABLE TB_NFE_CONFIG ADD RETENCAO_pIRRF NUMERIC(10,2);
ALTER TABLE TB_NFE_CONFIG ADD RETENCAO_pPrevidencia NUMERIC(10,2);
ALTER TABLE TB_NFE_CONFIG ADD ST_OBRIGA_RETENCAO CHAR(1);
ALTER TABLE TB_NFE_CONFIG ADD ST_OBRIGA_PACIENTE CHAR(1);
ALTER TABLE TB_NFE_CONFIG ADD NR_pTRIBUTOS NUMERIC(10,2);
ALTER TABLE TB_NFE_CONFIG ADD NR_pTRIBUTOS_EST NUMERIC(10,2);
ALTER TABLE TB_NFE_CONFIG ADD NR_pTRIBUTOS_MUN NUMERIC(10,2);

ALTER TABLE TB_CLIENTE ADD DS_COMPLEMENTO VARCHAR( 50 ) NULL;
ALTER TABLE TB_CLIENTE ADD indIE char(1) DEFAULT '9';
ALTER TABLE TB_CLIENTE ADD DS_NUMERO varchar(10);
ALTER TABLE TB_CLIENTE ADD NR_INSC_MUNICIPAL varchar(15);

--ALTER TABLE TB_NFE_PRODUTOS RENAME COLUMN CD_Mercadoria TO CD_MERCADORIA;
EXEC sp_RENAME 'TB_NFE_PRODUTOS.CD_Mercadoria' , 'CD_MERCADORIA', 'COLUMN';

ALTER TABLE TB_MERCADORIA ADD VL_PIS NUMERIC(18,0);
ALTER TABLE TB_MERCADORIA ADD ICMS_modBC char(1);
ALTER TABLE TB_MERCADORIA ADD IPI_CST char(2);
ALTER TABLE TB_MERCADORIA ADD VL_RET_ISS NUMERIC(10,2) DEFAULT NULL;

INSERT INTO TB_GRUPO VALUES ( '01', 'GERAL', '', '' );
INSERT INTO TB_SUBGRUPO VALUES('01', '01', 'GERAL', '');

ALTER TABLE TB_NFE ADD RET_Base_IRRF NUMERIC(10,2) DEFAULT NULL;
ALTER TABLE TB_NFE ADD RET_Aliq_IRRF NUMERIC(10,2) DEFAULT NULL;
ALTER TABLE TB_NFE ADD RET_Base_PREV NUMERIC(10,2) DEFAULT NULL;
ALTER TABLE TB_NFE ADD RET_Aliq_PREV NUMERIC(10,2) DEFAULT NULL;






