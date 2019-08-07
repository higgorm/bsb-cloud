
--Criando a nova tabela para armazenamento de nfe inutilizadas por faixa 
CREATE TABLE [dbo].[TB_NFE_INUTILIZADAS](
    [CD_NFE_INUTILILIZADA][int] NOT NULL,
	[CD_LOJA] [int] NOT NULL,
	[CD_USUARIO] [int]NOT NULL,
	[TP_AMBIENTE][int] NOT NULL,
	[NR_SERIE] [varchar](3) NULL,
	[NR_ANO] [int] NOT NULL,
	[NR_FAIXA_INICIAL][int] NOT NULL,
	[NR_FAIXA_FINAL][int] NOT NULL,
	[DS_JUSTIFICATIVA] [varchar](255)NOT NULL,
	[DS_X_MOTIVO] [varchar](255) NOT NULL,
	[DH_RECEBIMENTO] [DateTime] NULL,
	[TP_MODELO] [varchar](2) NULL,
	[NR_VERSAO] [varchar](10) NULL,
	[NR_VERSAO_APLICACAO] [varchar](30) NULL,
	[CD_UF][varchar](2)  NULL,
	[NU_CNPJ][varchar](14)  NULL,
	[NU_PROTOCOLO][varchar](30)  NULL,
	[BSTAT][BIT]  NULL,
	[CSTAT][varchar](4)  NULL,

	 CONSTRAINT [PK_TB_NFE_INUTILIZADAS] PRIMARY KEY (
		[CD_NFE_INUTILILIZADA] ASC
	)
 );

