var Util = {
    init: function() {
		
		$('#searchMercadoria').click(function() {
            $('#bodyMercadoria').html('');
            $('#bodyMercadoria').load('/mercadoria/modal-pesquisa-mercadoria');
            $('#pesquisaMercadoriaModal').modal('show');
        });
		
		$('#searchCliente').click(function() {
            $('#bodyPesquisaCliente').html('');
            $('#bodyPesquisaCliente').load('/cliente/modal-pesquisa-cliente');
            $('#pesquisaClienteModal').modal('show');
        });
		
		$('#cadastraCliente').click(function(){
			$('#bodyCliente').html('');
			$('#bodyCliente').load('/cliente/cadastrar');
			$('#cadastraClienteModal').modal('show');
		});
		
		$('#cadastraClienteCompleto').click(function(){
			$('#bodyCadastroCliente').html('');
			$('#bodyCadastroCliente').load('/cliente/cadastro?modal=show');
			$('#cadastraClienteCompletoModal').modal('show');
		});
	},
	
	recuperaMercadoriaPorParamentro: function() {
        $.ajax({
            type: 'post',
            dataType: "json",
            url: '/mercadoria/pesquisa-mercadoria-por-paramentro',
            data: {
                st_tipo_pesquisa: $('#st_tipo_pesquisa').val(),
                codigoMercadoria: $('#codigoMercadoria').val()
            },
            success: function(data) {
                if (data.result == 'erro') {
                    alert(data.result);
                    return false;
                }

                var options = "";
                $.each(data, function(key, value) {
                    if (key == 'data') {

                        $.each(value, function(keyMercadoria, mercadoriaValue) {
                            options += '<tr>';
                            options += '    <td><input type="radio" name="co_mercadoria" value="' + mercadoriaValue.CD_MERCADORIA + '"/>' + mercadoriaValue.CD_MERCADORIA + '</td>';
                            options += '    <td>' + decodeURIComponent(escape(mercadoriaValue.DS_MERCADORIA)) + '</td>';
                            options += '    <td>' + formatReal(mercadoriaValue.NR_QTDE_ESTOQUE) + '</td>';
                            options += '    <td>' + formatReal(mercadoriaValue.NR_QTDE_RESERVA) + '</td>';
                            options += '    <td>' + formatReal(mercadoriaValue.QTDE_DISPONIVEL) + '</td>';
                            options += '    <td>' + formatReal(mercadoriaValue.VL_PRECO_AVISTA) + '</td>';
                            options += '    <td>' + formatReal(mercadoriaValue.VL_PRECO_VENDA) + '</td>';
                            options += '    <td>' + formatReal(mercadoriaValue.VL_PRECO_VENDA_PROMOCAO) + '</td>';
                            options += '</tr>';
                        });
                    }
                });
                $('#tListaMercadoria').append(options);
                //$('#st_tipo_pesquisa').val('1');
            }
        });
    },
	
	recuperaClientePorParamentro: function() {
        $.ajax({
            type: 'post',
            dataType: "json",
            url: '/cliente/pesquisa-cliente-por-paramentro',
            data: {
                st_tipo_pesquisa: $('#st_tipo_pesquisa').val(),
                codigoCliente: $('#codigoCliente').val()
            },
            success: function(data) {
                if (data.result == 'erro') {
                    alert('Cliente não encontrado');
                    return false;
                }

                var options = "";
                $.each(data, function(key, value) {
                    if (key == 'data') {

                        $.each(value, function(keyCliente, cliente) {
                            options += '<tr>';
                            options += '    <td><input type="radio" name="co_cliente" value="' + cliente.CD_CLIENTE + '"/>' + cliente.CD_CLIENTE + '</td>';
                            options += '    <td>' + decodeURIComponent(escape(cliente.DS_NOME_RAZAO_SOCIAL)) + '</td>';
                            options += '    <td>' + cliente.DS_FANTASIA + '</td>';
                            options += '    <td>' + cliente.NR_CGC_CPF + '</td>';
                            options += '</tr>';
                        });

                    }
                });
                $('#tListaCliente').append(options);
                //$('#st_tipo_pesquisa').val('1');
            }
        });
    },
}