<?php

return array(
    'admin' => array(

        // LAYOUT
        'index/',
        'index/logout',
        'index/login',
        'index/index',
        'index/validasenha',
        'Application\Controller\Index/index',
        'layout/login',
        'home',
        'home/default',
        'js/fullcalendar',

        // PAINEL CONTROLLER
        'painel/index',
        'Application\Controller\Painel/index',
		
		// PERFIL
		'perfil/index',

        // CLIENTE CONTROLLER
        'cliente/index',
        'cliente/cadastrar',
        'cliente/cadastrarclienterapidoagendamento',
        'cliente/editar',
        'cliente/remover',
        'cliente/buscarcliente',
        'cliente/agendacadastrar',
		'cliente/cadastro',
		'cliente/modal-pesquisa-cliente',
		'cliente/modal-pesquisa-lista-cliente',
		'cliente/pesquisa-cliente-por-paramentro',
		'cliente/recupera-cliente-por-codigo',
		
		// MERCADORIAS
		'mercadoria/index',
		'mercadoria/editar',
		'mercadoria/cadastrar',
		'mercadoria/remover',
		'mercadoria/pesquisar',
		'mercadoria/modal-pesquisa-mercadoria',
		'mercadoria/modal-pesquisa-lista-mercadoria',
		'mercadoria/pesquisa-mercadoria-por-paramentro',
		'mercadoria/recupera-mercadoria-por-codigo',
		
		// TABELA
		'tabela/forma-pagamento',
		'tabela/forma-pagamento-editar',
		'tabela/forma-pagamento-cadastrar',
		'tabela/cfop',
		'tabela/cfop-cadastrar',
		'tabela/cfop-editar',
		'tabela/cartao',
		'tabela/cartao-cadastrar',
		'tabela/cartao-editar',
		
        // AGENDA CONTROLLER
        'agenda/agendamento-cliente',
        'agenda/index',
        'agenda/agenda',
        'agenda/horariosAgenda',
        'agenda/cadastrar-cliente-agenda',
        'agenda/recupera-servico-por-codigo',
        'agenda/situacaoagendamento',
        'agenda/verificaagenda',
        'agenda/atendecliente',
        'agenda/limpaagendamento',

        // MACA CONTROLLER
        'maca/index',
        'maca/cadastrar',

        // PEDIDO CONTROLLER
		'pedido/novo-pedido',
        'pedido/lista-tablet',
        'pedido/grid-lista',
        'pedido/pedido-tablet',
        'pedido/salvar-pedido',
		'pedido/salvar-novo-pedido',
        'pedido/verifica-estoque',
        'pedido/orcamento',
        'pedido/grid-mercadoria',
        'pedido/modal-lista-mercadoria',
        'pedido/modal-pesquisa-lista-mercadoria',
        'pedido/modal-pesquisa-mercadoria',
        'pedido/modal-lista-mercadoria',
        'pedido/verifica-numero-pedido',
        'pedido/recupera-dados-pedido',
        'pedido/recupera-dados-mercadoria',
        'pedido/recupera-mercadoria-por-codigo',
        'pedido/inserir-pedido-orcamento',
        'pedido/salva-mercadoria-pedido',
        'pedido/pesquisa-mercadoria-por-paramentro',
        'pedido/cpf-nota',
        'pedido/recupera-historico-por-data',
		
		// NOTA CONTROLLER
		'nota/lista',
		'nota/avulsa',
		'nota/pedido',
		'nota/ordemServico',
		'nota/cartaCorrecao',
		'nota/configurar',
		'nota/gera-nfe',
		'nota/status-nfe',
		'nota/consulta-receita',
		'nota/imprime-danfe',
		'nota/cancela',
		'nota/send',
		'nota/abrir',
		'nota/visualiza-danfe',
		'nota/save-nota',

        // RELATORIO CONTROLLER
        'relatorio/index',
        'relatorio-agendamento/pesquisa',
        'relatorio-agendamento/filtro',
        'relatorio-agendamento/relatorio',
        'relatorio-atendimento/pesquisa',
        'relatorio-atendimento/relatorio',
        'relatorio-vendas-mensal-tipo-pagamento/pesquisa',
        'relatorio-vendas-mensal-tipo-pagamento/relatorio',
        'relatorio-vendas-mensal-tipo-pagamento/app',
        'relatorio-inventario-estoque/pesquisa',
        'relatorio-inventario-estoque/relatorio',
        'relatorio/relatorio-inventario-estoque',
        'relatorio-resumo-caixa/pesquisa',
        'relatorio-resumo-caixa/relatorio',
        'relatorio-resumo-caixa/filtro',
        'relatorio-caixa/pesquisa',
        'relatorio-caixa/filtro',
        'relatorio-caixa/relatorio',
        'relatorio-caixa/relatorio-detalhado',
        'relatorio/relatorio-bi',
        'relatorio-pedido/pesquisa',
        'relatorio-pedido/relatorio',
        'relatorio-pedido/pesquisa-multi-loja',
        'relatorio-pedido/detalhe-multi-loja',
		'relatorio-nota/pesquisa',
        'relatorio-nota/relatorio',
        'relatorio-vendas-secao/pesquisa',
        'relatorio-vendas-secao/relatorio',

        //CAIXA
        'caixa/index',
        'caixa/caixa',
        'caixa/caixafuncionario',
        'caixa/validaaberturacaixa',
        'caixa/fechamentocaixa',
        'caixa/recebepedido',
        'caixa/reaberturacaixa',
        'caixa/movimentacaocaixa',
        'caixa/gravamovimentacaocaixa',
        'caixa/pesquisamovimentacaocaixa',

        // MAIL
        'mail/crm',
        'mail/pesquisa',
        'mail/send-mail',
        'mail/gerar-planilha',
        'mail/relatorio',
        'mail/analise',
        'mail/ver-clientes',

        //CONTAS
        'contas-receber/index',
        'contas-receber/pesquisa',
        'contas-receber/cadastrar',
        'contas-receber/editar',
        'contas-receber/buscarcliente',

        'contas-pagar/index',
        'contas-pagar/pesquisa',
        'contas-pagar/cadastrar',
        'contas-pagar/editar',
        'contas-pagar/baixa',

        //Estoque
        'estoque/index',
        'estoque/movimentacao',
        'estoque/pesquisa',
        'estoque/cadastrar',
        'estoque/editar',
        'estoque/remover',

        // AJAX
        'ajax/get-cidade-por-uf',
        'ajax/get-historico-movimentacao',
        'ajax/get-classificacao-financeira',
        'ajax/get-operador-loja',

        //ACL controller's
        'perfil/index',
        'perfil/cadastrar',
        'perfil/editar',
        'usuario-web/index',
        'usuario-web/cadastro',
        'usuario-web/inativar',

    )
);
