
var Orcamento = {
    init: function() {

        $('nav nav-tabs a').click(function(e) {
            e.preventDefault();
            $(this).tab('show');
        });

        $('#btnLista').click(function() {
            $('#bodyListaMercadoria').html('');
            $('#bodyListaMercadoria').load('/pedido/modal-lista-mercadoria');
            $('#listaMercadoriaModal').modal('show');
        });

        $('#vl_desconto').blur(function() {
            if ($('#vl_desconto').val() == '')
                return false;

            // autorizacao/senha liberação desconto
            //-- modal senha --//
        });

        $('#ds_razao_social').blur(function() {
            if ($('#ds_razao_social').val() === '') {
                $('#cd_cliente').val('');
            }
        });

        $('#cd_mercadoria').blur(function() {
            if ($('#cd_mercadoria').val() == '')
                return false;

            if ($('#cd_funcionario').val() == '') {
                alert('O campo REPRESENTANTE é obrigatório.');
                return false;
            }

            if ($('#ds_razao_social').val() == '') {
                alert('O campo CLIENTE é obrigatório.');
                return false;
            }

            $.ajax({
                url: "/pedido/recupera-mercadoria-por-codigo",
                type: "POST",
                dataType: "json",
                data: {
                    cd_mercadoria: $('#cd_mercadoria').val()
                },
                success: function(data) {
                    if (data.result == 'erro') {
                        alert(data.message);
                        return false;
                    }

                    $('#ds_mercadoria').val(data.data.DS_MERCADORIA);
                    $('#vl_preco_unitario').val(formatReal(data.data.VL_PRECO_VENDA));
                    $('#tp_retirada').val('E');
                    $('#qt_mercadoria').val('1');
                }
            });
        });

        $('#cd_uf').change(function() {
            if ($('#cd_uf').val() == '') {
                $('#cd_cidade').val('');
                return false;
            }

            $.ajax({
                url: "/ajax/get-cidade-por-uf",
                type: "POST",
                dataType: "json",
                data: {
                    cd_uf: $('#cd_uf').val()
                },
                success: function(data) {
                    var options = "";
                    $.each(data, function(key, value) {
                        options += '<option value="' + key + '">' + value + '</option>';
                    });
                    $("#cd_cidade").html(options);
                }
            });

        });

        $('.submit').click(function() {
            Orcamento.recuperaDadosPedido();
        });

        if ($('#nu_pedido').val() != '') {
            Orcamento.recuperaDadosPedido();
        }

        $('.btn-success').click(function() {
            Orcamento.salvaMercadoriaPedido();
        });

        $('.pesquisa-historico').click(function() {
            Orcamento.recuperaHistoricoPorData();
        });

        $('#insereMercadoria').click(function() {
            if ($('#cd_mercadoria').val() == '')
                return false;
            if ($('#ds_mercadoria').val() == '')
                return false;
            if ($('#vl_preco_unitario').val() == '')
                return false;
            if ($('#qt_mercadoria').val() == '') {
                alert('Informe a QUANTIDADE de mercadoria.');
                return false;
            }
            if ($('#qt_mercadoria').val() == '') {
                alert('Informe a QUANTIDADE de mercadoria.');
                return false;
            }

            $.ajax({
                type: 'post',
                dataType: "json",
                url: '/pedido/recupera-dados-mercadoria',
                data: {
                    cd_cliente: $('#cd_cliente').val(),
                    cd_loja: $('#cd_loja').val(),
                    cd_mercadoria: $('#cd_mercadoria').val(),
                    qt_mercadoria: $('#qt_mercadoria').val(),
                    vl_desconto: $('#vl_desconto').val(),
                    nr_pedido: $('#nr_pedido').val(),
                    tp_retirada: $('#tp_retirada').val(),
                },
                success: function(data) {
                    if (data.result == 'erro') {
                        alert(data.message);
                        return false;
                    }
                    Orcamento.inserirMercadoriaAba(data);
                }
            });

        });

        $('#searchMercadoria').click(function() {
            $('#bodyMercadoria').html('');
            $('#bodyMercadoria').load('/pedido/modal-pesquisa-mercadoria');
            $('#pesquisaMercadoriaModal').modal('show');
        });
    },
    recuperaDadosPedido: function() {
        return $.ajax({
            type: 'post',
            dataType: "json",
            url: '/pedido/recupera-dados-pedido',
            data: {
                cd_cliente: $('#cd_cliente').val(),
                nu_pedido: $('#nu_pedido').val(),
                dt_pedido: $('#dt_pedido').val(),
                cd_tipo_mercadoria: $('#cd_tipo_mercadoria').val(),
                cd_funcionario: $('#cd_funcionario').val(),
                nr_livro: $('#nr_livro').val(),
                cd_prazo: $('#cd_prazo').val(),
                cd_loja: $('#cd_loja').val(),
                cd_tipo_pedido: $('#cd_tipo_pedido').val()
            },
            success: function(data) {
                if (data.result == 'erro') {
                    alert(data.message);
                }
                if (data.result == 'passou') {
                    $('.btn-success').hide();
                    alert(data.message);
                }
                Orcamento.preencheCamposTelaPedidoPesquisa(data);
                Orcamento.preencheCamposAbaMercadoria(data);
                Orcamento.preencheCamposAbaDadosAdicionais(data);
                Orcamento.preencheCamposAbaUltimacompra(data);
                Orcamento.preencheCamposAbaHistoricoCliente(data);
                Orcamento.preencheCamposAbaPedidoAnterior(data);

                $('.divAbasPedidos').removeClass('hide');
            }
        });
    },
    preencheCamposTelaPedidoPesquisa: function(data) {
        $('#nu_pedido').val(data.data.pedido.NR_PEDIDO);
        $('#dt_pedido').val(data.data.pedido.DT_PEDIDO);
        $('#cd_tipo_mercadoria').val(data.data.pedido.CD_TIPO_MERCADORIA);
        $('#cd_funcionario').val(data.data.pedido.CD_FUNCIONARIO);
        $('#nr_livro').val(data.data.pedido.CD_LIVRO);
//        $('#cd_prazo').val(data.data.pedido.CD_PRAZO);
//        $('#ds_razao_social').val(data.data.pedido.DS_NOME_RAZAO_SOCIAL);
//        $('#cd_cliente').val(data.data.pedido.CD_CLIENTE);
//        $('#cd_tipo_pedido').val(data.data.pedido.CD_TIPO_PEDIDO);
        return true;
    },
    preencheCamposAbaMercadoria: function(data) {
        if (data.data.mercadoriaPedido != null) {
            var options = "";
            $.each(data.data.mercadoriaPedido, function(key, value) {
                options += '<tr>';
                options += '    <td>' + value.CD_MERCADORIA + ' <input type="hidden" name="checkBoxMercadoria[]" value="' + value.CD_MERCADORIA + '"/></td>';
                options += '    <td>' + value.DS_MERCADORIA + '</td>';
                options += '    <td>' + parseFloat(value.QTD).toFixed(2) + '<input type="hidden" name="lsQuantidade[]" value="' + value.CD_MERCADORIA + '"/></td>';
                options += '    <td>' + parseFloat(value.NR_DESCONTO).toFixed(2) + '<input type="hidden" name="lsDesconto[]" value="' + value.NR_DESCONTO + '"/></td>';
                options += '    <td>' + formatReal(value.VL_DESCONTO) + '<input type="hidden" name="lsValorDesconto[]" value="' + value.VL_DESCONTO + '"/></td>';
                options += '    <td>' + formatReal(value.VL_NOMINAL) + '<input type="hidden" name="lsNominal[]" value="' + value.VL_NOMINAL + '"/></td>';
                options += '    <td>' + formatReal(value.VL_TOTAL) + '<input type="hidden" name="lsTotal[]" value="' + value.VL_TOTAL + '"/></td>';
                options += '    <td>N</td>';
                options += '    <td>N</td>';
                options += '    <td>' + value.RETIRADA + '<input type="hidden" name="lsRetirada[]" value="' + value.RETIRADA + '"/></td>';
                options += '</tr>';
            });
            $('#tMercadorias').html('');
            $('#tMercadorias').append(options);
            $('#mercadoria input[type="text"]').val('');
            $('.btn-success').removeClass('hide');
        }
        return true;
    },
    preencheCamposAbaDadosAdicionais: function(data) {

    },
    preencheCamposAbaUltimacompra: function(data) {
        var optionsAba = "";
        $.each(data.data.ultimasCompras, function(key, value) {
            var ativa = '';
            if (key == 0) {
                ativa = 'active'
            }
            optionsAba += '<li class="' + ativa + '"><a href="#aba' + key + '" data-toggle="tab">' + value.DT_PEDIDO + '</a></li>';
            var optionsDiv = "<div id='aba" + key + "' class='tab-pane '>";
            optionsDiv += "<table class='table table-striped m-b-none'>";
            optionsDiv += " <tr>";
            optionsDiv += "     <td>Tipo de Pedido: " + value.DS_TIPO_PEDIDO + "</td>";
            optionsDiv += "     <td>Tipo de Mercadoria: " + value.DS_TIPO_PEDIDO + "</td>";
            optionsDiv += " </tr>";
            optionsDiv += " <tr>";
            optionsDiv += "     <td>Representante: " + value.DS_FUNCIONARIO + "</td>";
            optionsDiv += "     <td>Prazo Tabela de Preço: " + value.DS_PRAZO + "</td>";
            optionsDiv += " </tr>";
            optionsDiv += " <tr>";
            optionsDiv += "     <td colspan='6'>";

            optionsDiv += '         <table>';
            optionsDiv += '             <tr>';
            optionsDiv += '                 <td>CODIGO</td>';
            optionsDiv += '                 <td>DESCRIÇÃO</td>';
            optionsDiv += '                 <td>QTDE</td>';
            optionsDiv += '                 <td>PREÇO TABELA</td>';
            optionsDiv += '                 <td>PREÇO VENDA</td>';
            optionsDiv += '                 <td>TOTAL</td>';
            optionsDiv += '             </tr>';

            $.each(value.MERCADORIA, function(k, v) {
                optionsDiv += '             <tr>';
                optionsDiv += '                 <td>' + v.CD_MERCADORIA + '</td>';
                optionsDiv += '                 <td>' + v.DS_MERCADORIA + '</td>';
                optionsDiv += '                 <td>' + v.NR_QTDE_VENDIDA + '</td>';
                optionsDiv += '                 <td>' + formatReal(v.VL_PRECO_CUSTO) + '</td>';
                optionsDiv += '                 <td>' + formatReal(v.VL_PRECO_VENDA) + '</td>';
                optionsDiv += '                 <td>' + formatReal(v.TOTAL) + '</td>';
                optionsDiv += '             </tr>';
            });

            optionsDiv += '         </table>';
            optionsDiv += "     </td>";
            optionsDiv += " </tr>";
            optionsDiv += "</table>";
            optionsDiv += "</div>";
            $("#listaUltimasCompras").append(optionsDiv);
        });
        $("#abaUltimasCompras").html(optionsAba);
    },
    preencheCamposAbaHistoricoCliente: function(data) {
        var options = "";
        $.each(data.data.historicoCliente, function(key, value) {
            options += '<tr>';
            options += '<td>' + value.NR_PEDIDO + '</td>';
            options += '<td>' + value.DT_PEDIDO + '</td>';
            options += '<td>' + value.NR_CGC_CPF + '</td>';
            options += '<td>' + value.DS_NOME_RAZAO_SOCIAL + '</td>';
            options += '<td>' + formatReal(value.VL_TOTAL_BRUTO) + '</td>';
            options += '<td>' + formatReal(value.VL_TOTAL_LIQUIDO) + '</td>';
            options += '<td>' + value.DS_FUNCIONARIO + '</td>';
            options += '<td>' + value.DS_PRAZO + '</td>';
            options += '<td>' + value.DS_CIDADE + '</td>';
            options += '<td>' + value.CD_UF + '</td>';
            options += '</tr>';
        });
        $("#tHistoricoCliente").html(options);
    },
    preencheCamposAbaPedidoAnterior: function(data) {
        var options = "";
        $.each(data.data.pedidoAnterior, function(key, value) {
            options += '<td>' + value + '</td>';
        });
        $("#tPedidoAnterior").html(options);
    },
    inserirMercadoriaAba: function(data) {
        var options = "";
        options += '<tr>';
        options += '    <td>' + data.data.CD_MERCADORIA + ' <input type="hidden" name="checkBoxMercadoria[]" value="' + data.data.CD_MERCADORIA + '"/></td>';
        options += '    <td>' + data.data.DS_MERCADORIA + '</td>';
        options += '    <td>' + data.data.QTD + '<input type="hidden" name="lsQuantidade[]" value="' + data.data.CD_MERCADORIA + '"/></td>';
        options += '    <td>' + data.data.NR_DESCONTO + '<input type="hidden" name="lsDesconto[]" value="' + data.data.NR_DESCONTO + '"/></td>';
        options += '    <td>' + formatReal(data.data.VL_DESCONTO) + '<input type="hidden" name="lsValorDesconto[]" value="' + data.data.VL_DESCONTO + '"/></td>';
        options += '    <td>' + formatReal(data.data.VL_NOMINAL) + '<input type="hidden" name="lsNominal[]" value="' + data.data.VL_NOMINAL + '"/></td>';
        options += '    <td>' + formatReal(data.data.VL_TOTAL) + '<input type="hidden" name="lsTotal[]" value="' + data.data.VL_TOTAL + '"/></td>';
        options += '    <td>N</td>';
        options += '    <td>N</td>';
        options += '    <td>' + data.data.RETIRADA + '<input type="hidden" name="lsRetirada[]" value="' + data.data.RETIRADA + '"/></td>';
        options += '</tr>';
        $('#tMercadorias').append(options);
        $('#mercadoria input[type="text"]').val('');
        $('.btn-success').removeClass('hide');
    },
    salvaMercadoriaPedido: function() {
        var arrayMercadoria = new Array();
        $('input[name="checkBoxMercadoria[]"]').each(
                function() {
                    arrayMercadoria.push($(this).val());
                }
        );
        var arrayQuantidade = new Array();
        $('input[name="lsQuantidade[]"]').each(
                function() {
                    arrayQuantidade.push($(this).val());
                }
        );
        var arrayPDesconto = new Array();
        $('input[name="lsDesconto[]"]').each(
                function() {
                    arrayPDesconto.push($(this).val());
                }
        );
        var arrayDesconto = new Array();
        $('input[name="lsValorDesconto[]"]').each(
                function() {
                    arrayDesconto.push($(this).val());
                }
        );
        var arrayValorNominal = new Array();
        $('input[name="lsNominal[]"]').each(
                function() {
                    arrayValorNominal.push($(this).val());
                }
        );
        var arrayValorTotal = new Array();
        $('input[name="lsTotal[]"]').each(
                function() {
                    arrayValorTotal.push($(this).val());
                }
        );
        var arrayRetirada = new Array();
        $('input[name="lsRetirada[]"]').each(
                function() {
                    arrayRetirada.push($(this).val());
                }
        );

        $.ajax({
            type: 'post',
            dataType: "json",
            url: '/pedido/salva-mercadoria-pedido',
            data: {
                cdMercadoria: arrayMercadoria,
                qtMercadoria: arrayQuantidade,
                nrDesconto: arrayPDesconto,
                vlDesconto: arrayDesconto,
                vlNominal: arrayValorNominal,
                vlTotal: arrayValorTotal,
                tpRetirada: arrayRetirada,
                cd_loja: $('#cd_loja').val(),
                cd_cliente: $('#cd_cliente').val(),
                nr_pedido: $('#nu_pedido').val(),
                cd_funcionario: $('#cd_funcionario').val()
            },
            success: function(data) {
                if (data.result == 'success') {
                    $('#nu_pedido').val(data.NR_PEDIDO);
                    alert('Anote o numero do pedido - ' + data.NR_PEDIDO + '.');
                    location.reload();
                }
            }
        });
    },
    recuperaMercadoriaPorParamentro: function() {
        $.ajax({
            type: 'post',
            dataType: "json",
            url: '/pedido/pesquisa-mercadoria-por-paramentro',
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
                        options += '<tr>';
                        options += '    <td><input type="radio" name="co_mercadoria" value="' + data.data.CD_MERCADORIA + '"/>' + value.CD_MERCADORIA + '</td>';
                        options += '    <td>' + value.DS_MERCADORIA + '</td>';
                        options += '    <td>' + value.NR_QTDE_ESTOQUE + '</td>';
                        options += '    <td>' + value.NR_QTDE_RESERVA + '</td>';
                        options += '    <td>' + value.QTDE_DISPONIVEL + '</td>';
                        options += '    <td>' + formatReal(value.VL_PRECO_AVISTA) + '</td>';
                        options += '    <td>' + formatReal(value.VL_PRECO_VENDA) + '</td>';
                        options += '    <td>' + formatReal(value.VL_PRECO_VENDA_PROMOCAO) + '</td>';
                        options += '</tr>';
                    }
                });
                $('#tListaMercadoria').append(options);
                $('#st_tipo_pesquisa').val('1');
            }
        });
    },
    recuperaHistoricoPorData: function() {
        if ($('#cd_cliente').val() == '')
            return false;
        if ($('#dt_inicial').val() == '')
            return false;

        $.ajax({
            type: 'post',
            dataType: "json",
            url: '/pedido/recupera-historico-por-data',
            data: {
                cd_cliente: $('#cd_cliente').val(),
                dt_inicial: $('#dt_inicial').val(),
                dt_final: $('#dt_final').val(),
            },
            success: function(data) {
                var options = "";
                $('#tHistoricoCliente').html('');
                
                if (data.result == 'erro') {
                    options += '<tr>';
                    options += '    <td colspan="10">Nenhum registro encontrado.</td>';
                    options += '</tr>';
                    $("#tHistoricoCliente").html(options);
                    return false;
                }

                var options = "";
                $.each(data.data, function(key, value) {
                    options += '<tr>';
                    options += '<td>' + value.NR_PEDIDO + '</td>';
                    options += '<td>' + value.DT_PEDIDO + '</td>';
                    options += '<td>' + value.NR_CGC_CPF + '</td>';
                    options += '<td>' + value.DS_NOME_RAZAO_SOCIAL + '</td>';
                    options += '<td>' + formatReal(value.VL_TOTAL_BRUTO) + '</td>';
                    options += '<td>' + formatReal(value.VL_TOTAL_LIQUIDO) + '</td>';
                    options += '<td>' + value.DS_FUNCIONARIO + '</td>';
                    options += '<td>' + value.DS_PRAZO + '</td>';
                    options += '<td>' + value.DS_CIDADE + '</td>';
                    options += '<td>' + value.CD_UF + '</td>';
                    options += '</tr>';
                });
                $("#tHistoricoCliente").html(options);
            }
        });
    },
}