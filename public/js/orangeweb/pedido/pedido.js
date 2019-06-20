var Pedido = {
    init: function() {

		/* Table initialisation */
        oTablePedido = $('#tabelaMercadorias').dataTable({
            "sDom": "<'row'<'span8'l><'span8'f>r>t<'row'<'span8'i><'span8'p>>",
            "sScrollY": "160px",
            "bScrollCollapse": false,
            "bPaginate": false,
            "bLengthChange": false,
            "bFilter": false,
            "bInfo": false,
            "bProcessing": false,
            "bServerSide": false,
            "aoColumns": [
                {"sClass": "nowrap left", "sType": "html"},
                {"sClass": "nowrap left", "sType": "string"},
                {"sClass": "nowrap left", "sType": "string"},
                {"sClass": "nowrap left", "sType": "string"},
                {"sClass": "nowrap left", "sType": "string"}
            ]
        });
		//Inicializar
		$("#xNatOp").val( $("#natOp :selected").text() );

        $('.dataP').datepicker(
            {
                format: 'dd-mm-yyyy',
                language: 'pt-BR',
                todayBtn: "linked"
            }
        );
		
		$("#Pedido").submit( function(){
			if( $("#codCliente").val() == '' ) {
				alert("Pesquise um destinatário");
				return false;
			}
			if( $("#totalPedido").val() < 0.01 ){
				alert("Pedido com valor abaixo do permitido");
				return false;
			}
			return true;  
		});

        $("#btnVoltarListagemPedido").click(function () {
            window.location = '/pedido/lista-tablet';
        });


        $("#btnCancelarPedido").click(function () {
            if (confirm('Confirmar limpar os dados do pedido?')) {
                window.location  =   "/pedido/novo-pedido" ;
            }
        });

        $("#btnSalvarPedido").click(function(){

            var $myForm = $('#pedido');
            var $msgRedirect = 'Deseja ser redirecionado para o recebimento no caixa, após salvar este pedido? Escolha uma das opções abaixo:\n\n'+
                               ' OK - para ir direto ao caixa. \n' +
                               ' Cancelar - para continuar no modulo de pedido. \n';

            if($myForm[0].checkValidity()) {

                if (confirm($msgRedirect)) {
                    $("#flRedirecionarAoCaixa").val("S");
                } else {
                    $("#flRedirecionarAoCaixa").val("N");
                }
                $myForm[0].submit();
            } else {
                $myForm[0].reportValidity()
            }
        });


		$("#btnIncluirMercadoria").click(function () {
			var cdMercadoria            = $("#CD_MERCADORIA").val();
			var qtdVendida              = $("#qtd_mercadoria").val();
			var dscMercadoria           = $("#ds_mercadoria").val();
			var vlUnt		            = $("#vl_preco_unitario").val();
			var vlTot		            = $("#vl_tot").val();
			var totalPedido             = parseFloat( $("#totalPedido").val() );
            var isServico               = $("#isServico").val();
            var isServicoProxProduto    = $("#isServicoProxProduto").val();

            if ((cdMercadoria.trim() == "") || (dscMercadoria.trim() == "") || (vlUnt.trim() == "")) {
                return false;
            }

            if(oTablePedido.fnGetData().length >= 1){
                if( (isServicoProxProduto != isServico) && (isServico != "")) {
                    alert('Aviso: \n Não está habilitado  serviços e mercadorias no mesmo pedido. \n\n Favor emitir pedidos distintos!');

                    //Limpar os campos
                    $("#CD_MERCADORIA").val("");
                    $("#qtd_mercadoria").val("1");
                    $("#ds_mercadoria").val("");
                    $("#vl_preco_unitario").val("");
                    $("#isServicoProxProduto").val("")
                    return false;
                }
            }


            oTablePedido.fnAddData(['<button type="button" name="chkMercadoria[]" id="chkMercadoria" value="' + cdMercadoria + '" class="btn btn-info" onclick="verificaStatus($(this))"><i class="icon-white"></i></button>'
														+ ' <input type="hidden" name="cdMercadoria[]" value="' + cdMercadoria + '" /> ',
														//+ ' <input type="hidden" name="stServico-' + value.CD_MERCADORIA + '" value="' + value.ST_SERVICO + '" /> ',
														dscMercadoria + ' <input type="hidden" name="ds_mercadoria-' + cdMercadoria + '" value="' + dscMercadoria + '" /> ',
														qtdVendida + ' <input type="hidden" name="qtdVendida-' + cdMercadoria + '" value="' + qtdVendida + '" /> ',
														vlUnt + ' <input type="hidden" name="vl_preco_unitario-' + cdMercadoria + '" value="' + vlUnt + '" /> ',
														vlTot + ' <input type="hidden" name="vl_tot-' + cdMercadoria + '" id="vl_tot-' + cdMercadoria + '" value="' + vlTot + '" /> '
													]);
			totalPedido = formatReal( totalPedido  + ( qtdVendida * vlUnt )); 
											
			$("#totalPedido").val( totalPedido );
			$("#totalPedido").change();
			//calculaTotalPedido();

            $("#CD_MERCADORIA").val("");
            $("#qtd_mercadoria").val("1");
            $("#ds_mercadoria").val("");
            $("#vl_preco_unitario").val("");
		});
		
		$("#btnExcluirMercadoria").click( function(){
			
			var totalPedido     = parseFloat( $("#totalPedido").val() );
			var totalExcluido =  parseFloat(0);


			var numCheckboxMarcados = 0;
            $("button[type='button'][name^='chkMercadoria']").each(function() {
				if ($(this).html() == '<i class="icon-white icon-ok"></i>')
                {
                    //remove a mercadoria
					totalExcluido = totalExcluido +  parseFloat($("#vl_tot-"+$(this).val()).val());
                    oTablePedido.fnDeleteRow(oTablePedido.fnGetPosition($(this).closest('tr').get(0)));
                    numCheckboxMarcados++;
                }
            });
            $("#chkTodos").removeAttr("checked");//Desmarca a opção todos
			$("#totalPedido").val( formatReal(parseFloat(totalPedido) - parseFloat(totalExcluido)));
			$("#totalPedido").change();

            if(oTablePedido.fnGetData().length == 0){
                $("#isServico").val("");
                $("#isServicoProxProduto").val("")
            }
		});
		
		$("#CD_MERCADORIA").change(function (){
			var cdMercadoria = $("#CD_MERCADORIA").val();
			var totalPedido    = parseFloat( $("#totalPedido").val() );
			
			if (cdMercadoria > 0){
				$.ajax({
					type: 'post',
					dataType: "json",
					url: '/mercadoria/pesquisa-mercadoria-por-paramentro',
					data: {
						st_tipo_pesquisa: '1',
						codigoMercadoria: cdMercadoria
					},
					success: function(data) {
						if (data.result == 'erro') {
							alert(data.message);
                            $("#qtd_mercadoria").val("1");
                            $("#ds_mercadoria").val("");
                            $("#vl_preco_unitario").val("");
                            $("#isServicoProxProduto").val("")
							return false;
						}
               	
						var options = "";
						$.each(data, function(key, value) {
							if (key == 'data') {

                                $.each(value, function(keyMercadoria, mercadoriaValue) {
                                    $("#CD_MERCADORIA").val(mercadoriaValue.CD_MERCADORIA);
                                    $("#qtd_mercadoria").val('1');
                                    $("#ds_mercadoria").val( mercadoriaValue.DS_MERCADORIA);
                                    $("#vl_preco_unitario").val(formatReal(mercadoriaValue.VL_PRECO_VENDA));
                                    $("#isServicoProxProduto").val(mercadoriaValue.ST_SERVICO.trim());
                                });

								$("#vl_preco_unitario").blur();								
							}
						});
					}
				});
            }
		});

        $("#codCliente").change(function (){
            var cdCliente = $("#codCliente").val();

            if (cdCliente > 0){
                $.ajax({
                    type: 'post',
                    dataType: "json",
                    url: '/cliente/recupera-cliente-por-codigo',
                    data: {
                        cd_cliente: cdCliente
                    },
                    success: function(data) {
                        if (data.result == 'erro') {
                            alert(data.message);
                            $("#destNome").val("");
                            $("#destCNPJ").val("");
                            return false;
                        }

                        $("#destNome").val(data.data.ds_nome_razao_social);
                        $("#destCNPJ").val(data.data.nr_cgc_cpf);
                        $("#codCliente").val(data.data.cd_cliente);
                        $('.modal').modal('hide');
                    }
                });
            }
        });

        $("#vl_preco_unitario").blur(function(){
			var precoUnd = parseFloat( $("#vl_preco_unitario").val() );
			$("#vl_tot").val( formatReal(precoUnd * $("#qtd_mercadoria").val()));
		});

        $("#vl_preco_unitario").change(function(){
          alert('O valor unitário mercadoria\/serviço foi alterado. \r\n Tem certeza desta alteração?');
        });

		$("#qtd_mercadoria").blur(function(){
			var precoUnd = parseFloat( $("#vl_preco_unitario").val() );
			$("#vl_tot").val( formatReal(precoUnd * $("#qtd_mercadoria").val()));
		});		
		
		$("#btnCopiaDestDMED").click(function () {
			$("#nome_paciente").val( $("#ds_nome_razao_social_input").val());
			$("#cpf_paciente").val( $("#destCNPJ").val());
		});
		
		verificaStatus = function(button) {
            if (button.html() == '<i class="icon-white"></i>') {
                button.html('<i class="icon-white icon-ok"></i>');
            }else{
                button.html('<i class="icon-white"></i>');
            }
        }
    },
    confirmarCancelamento : function(objAhref) {
        if (confirm('Confirma o cancelamento deste pedido?')) {
            window.location.href='/pedido/cancelar?id='+$(objAhref).attr('data-value');
        } else {
            return;
        }
    },
}