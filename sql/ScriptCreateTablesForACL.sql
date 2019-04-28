USE [LOGIN]


CREATE TABLE [LOGIN].[dbo].TB_PERFIL_WEB(
	[CD_PERFIL_WEB] [int] NOT NULL,
	[DS_NOME] [varchar](55) NOT NULL,
	[ST_ATIVO] [char](1) NULL,
 CONSTRAINT [PK_TB_PERFIL_WEB] PRIMARY KEY (
	[CD_PERFIL_WEB] ASC
 )
);

ALTER TABLE [LOGIN].[dbo].TB_USUARIO_WEB
	ADD [CD_PERFIL_WEB] [int];

CREATE TABLE [LOGIN].[dbo].TB_MENU_WEB_RESOURCE(
	[CD_MENU_WEB_RESOURCE] [int] IDENTITY(1,1) NOT NULL,
	[DS_MENU_RESOURCE] [varchar](100) NOT NULL,
	[CD_MENU] [int] NOT NULL,
 CONSTRAINT [PK_MENU_WEB_RESOURCE] PRIMARY KEY (
	 [CD_MENU_WEB_RESOURCE] ASC
 ),
  CONSTRAINT UK_MENU_WEB_RESOURCE UNIQUE(DS_MENU_RESOURCE,CD_MENU) 
);

CREATE TABLE [LOGIN].[dbo].TB_PERFIL_WEB_ACL(
	[CD_PERFIL_WEB] [int] NOT NULL,
	[CD_MENU] [int] NOT NULL,
 CONSTRAINT [PK_TB_PERFIL_WEB_MENUS] PRIMARY KEY (
	[CD_PERFIL_WEB] ASC, [CD_MENU] ASC
 )
);



CREATE TABLE [LOGIN].[dbo].TB_MENU_WEB(
	[CD_MENU] [int] NOT NULL,
	[DS_MENU] [varchar](100) NOT NULL,
	[CD_MENU_PAI] [int] ,
	[DS_URL] [varchar](100),
	[DS_ICONE] [varchar](100),
	[ST_ATIVO] [char](1) NOT NULL
 CONSTRAINT [PK_TB_MENU_WEB] PRIMARY KEY (
	[CD_MENU] ASC
 )
);

--Menus
INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (1,'Controle de Acesso',NULL,NULL,'fa fa-lock','S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (1,'Menus do sistema',1,'/menu-web/index',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (3,'Perfis do sistema',1,'/perfil-web/index',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (4,'Usuários Web',1,'/usuario-web/index',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (5,'Cadastros',NULL,NULL,'fa fa-qrcode','S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (6,'Clientes',5,'/cliente',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (7,'Serviço/Mercadoria',5,'/mercadoria',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (8,'CFOP',5,'/tabela/cfop',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (9,'Cartões',5,'/tabela/cartao',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (10,'Forma de Pagamento',5,'/tabela/forma-pagamento',NULL,'S');


INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (11,'Agenda',NULL,'/agenda/index','fa fa-calendar','S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (12,'Pedido',NULL,'/pedido/lista-tablet','fa fa-pencil','S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (13,'Caixa',NULL,'/caixa/caixa','fa fa-shopping-cart','S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (14,'Nota Fiscal Eletrônica',NULL,NULL,'fa fa-qrcode','S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (15,'Emitir NF-e',14,'/nota/avulsa',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (16,'Listar NF-e',14,'/nota/lista',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (17,'Consulta(Receita)',14,'/nota/consulta-receita',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (18,'Configurar',14,'/nota/configurar',NULL,'S');


INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (19,'Contas à Pagar',NULL,'/contas-pagar','fa fa-paperclip','S');

	
INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (20,'Contas à Receber',NULL,'/contas-receber','fa fa-money','S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (21,'Relatórios',NULL,NULL,'i i-graph icon','S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (22,'Atendimento - Produtos / Serviços Vendidos',21,'/relatorio-atendimento/pesquisa',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (23,'Notas Fiscais',21,'/relatorio-nota/pesquisa',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (24,'Pedidos',21,'/relatorio-pedido/pesquisa',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (25,'Agendamentos',21,'/relatorio-agendamento/pesquisa',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (26,'Caixa',21,'/relatorio-caixa/pesquisa',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (27,'Resumo de Caixa',21,'/relatorio-resumo-caixa/pesquisa',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (28,'Inventário de Estoque',21,'/relatorio-inventario-estoque/pesquisa',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (29,'Vendas Mensais Por Tipo de Pagamento',21,'/relatorio-vendas-mensal-tipo-pagamento/pesquisa',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (30,'Multi-Loja',21,'/relatorio-pedido/pesquisa-multi-loja',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (31,'CRM',NULL,NULL,'fa fa-users','S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (32,'Análise',31,'/mail/analise',NULL,'S');

INSERT INTO [LOGIN].[dbo].TB_MENU_WEB
	VALUES (33,'E-mail',31,'/mail/crm',NULL,'S');




--Perfil Básico I
 UPDATE [LOGIN].[dbo].[TB_USUARIO_WEB]
  SET [CD_PERFIL_WEB] = 2
   WHERE [CD_PERFIL_WEB] = NULL;

UPDATE [LOGIN].[dbo].[TB_USUARIO_WEB]
  SET   CD_PERFIL_WEB  = 2,
		 ST_ATIVO = 'S'
   WHERE [CD_USUARIO_WEB] = 6; -- bsbgestao

--Perfil Administrador de usuários
UPDATE [LOGIN].[dbo].[TB_USUARIO_WEB]
  SET  CD_PERFIL_WEB  = 1, 
	    ST_ATIVO = 'S'
   WHERE [CD_USUARIO_WEB] = 7; -- admin







