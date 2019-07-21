
var Nota = {
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
		
		$("#nota").submit( function(){
			if( $("#codCliente").val() == '' ) {
				alert("Pesquise um destinatário");
				return false;
			}
			if( $("#totalNota").val() < 0.01 ){
				alert("Nota com valor abaixo do permitido");
				return false;
			}
			return true;  
		});

		$("#btnIncluirMercadoria").click(function () {
			var cdMercadoria            = $("#CD_MERCADORIA").val();
			var qtdVendida              = $("#qtd_mercadoria").val();
			var dscMercadoria           = $("#ds_mercadoria").val();
			var vlUnt		            = $("#vl_preco_unitario").val();
            var vlDesc                  = $("#vl_preco_unitario").val(); //Na inclusao , o valor de desconto é o mesmo do preço unitario
			var vlTot		            = parseFloat( $("#vl_tot").val().replace(",","."));
			var totalNota               = parseFloat( $("#totalNota").val().replace(",","."));
            var subTotalNota            = parseFloat( $("#subTotalNota").val().replace(",","."));
			var isServico               = $("#isServico").val();
            var isServicoProxProduto    = $("#isServicoProxProduto").val();
            var flProdutoJaadicionado   = false;

            var limparCampos = function(){
                $("#CD_MERCADORIA").val("");
                $("#qtd_mercadoria").val("1");
                $("#ds_mercadoria").val("");
                $("#vl_preco_unitario").val("");
                $("#isServicoProxProduto").val("");
                $("#vl_tot").val("");
            }

			if ((cdMercadoria.trim() == "") || (dscMercadoria.trim() == "") || (vlUnt.trim() == "")) {
				return false;
			}

            $("button[type='button'][name^='chkMercadoria']").each(function() {
                if ($(this).val() == cdMercadoria) {
                    flProdutoJaadicionado = true;
                }
            });

            if (flProdutoJaadicionado) {
                //Limpar os campos
                limparCampos();

                //msgAlerta
                alert('Aviso:\n Produto / Serviço já foi adicionado a nota fiscal!');
                return false;
            }

            if(oTablePedido.fnGetData().length >= 1){
                if( (isServicoProxProduto != isServico) && (isServico != "")) {
                    alert('Aviso: \n Este emissor não está habilitado para emissão de serviços e mercadorias na mesma nota. \n\n Favor emitir notas distintas!');

                   //Limpar os campos
                    limparCampos();
                    return false;
                }
            }

            $("#isServico").val($("#isServicoProxProduto").val());

			oTablePedido.fnAddData(['<button type="button" name="chkMercadoria[]" id="chkMercadoria" value="' + cdMercadoria + '" class="btn btn-info" onclick="verificaStatus($(this))"><i class="icon-white"></i></button>'
														+ ' <input type="hidden" name="cdMercadoria[]" value="' + cdMercadoria + '" /> ',
														dscMercadoria + ' <input type="hidden" name="ds_mercadoria-' + cdMercadoria + '" value="' + dscMercadoria + '" /> ',
														qtdVendida + ' <input type="hidden" id="qtdVendida-' + cdMercadoria + '"  name="qtdVendida-' + cdMercadoria + '" value="' + qtdVendida + '" /> ',
														vlUnt + ' <input type="hidden" id="vl_preco_unitario-' + cdMercadoria + '" name="vl_preco_unitario-' + cdMercadoria + '" value="' + vlUnt + '" /> ',

                                                        '<span id="span_vl_preco_desconto-' + cdMercadoria + '">'+ formatReal(vlDesc) +'</span>' +
                                                        ' <input type="hidden" id="vl_preco_desconto-' + cdMercadoria + '" name="vl_preco_desconto-' + cdMercadoria + '" value="' + vlDesc + '" /> ',

                                                        '<span id="span_vl_tot-' + cdMercadoria + '">'+ formatReal(vlTot) +'</span>' +
                                                        ' <input type="hidden" id="vl_tot-' + cdMercadoria + '"  name="vl_tot-' + cdMercadoria + '"  value="' + vlTot + '" /> '
													]);

            subTotalNota = formatReal( subTotalNota  + ( qtdVendida * vlUnt ));
            $("#subTotalNota").val( subTotalNota );
            $("#nrPercentualDesconto").change();
            limparCampos();

		});
		
		$("#btnExcluirMercadoria").click( function(){
			
			var subTotalNota  = parseFloat( $("#subTotalNota").val().replace(",","."));
			var totalExcluido =  parseFloat(0);
			
			var numCheckboxMarcados = 0;
            $("button[type='button'][name^='chkMercadoria']").each(function() {
				if ($(this).html() == '<i class="icon-white icon-ok"></i>')
                {
                    //remove a mercadoria
					//totalExcluido = totalExcluido +  parseFloat($("#vl_tot-"+$(this).val()).val());
                    totalExcluido = totalExcluido +  (parseFloat($("#vl_preco_unitario-"+$(this).val()).val()) * $("#qtdVendida-"+$(this).val()).val());
                    oTablePedido.fnDeleteRow(oTablePedido.fnGetPosition($(this).closest('tr').get(0)));
                    numCheckboxMarcados++;
                }
            });

            $("#chkTodos").removeAttr("checked");//Desmarca a opção todos
            $("#subTotalNota").val( formatReal(parseFloat(subTotalNota) - parseFloat(totalExcluido)));
            $("#nrPercentualDesconto").change();

            if(oTablePedido.fnGetData().length == 0){
                $("#isServico").val("");
                $("#isServicoProxProduto").val("");
                $("#subTotalNota").val("0.00");
                $("#nrPercentualDesconto").val("0.00").change();
            }
		});

        $("#btnLimparNota").click( function(){
            if (confirm('Confirmar limpar os dados da nota?')) {
                window.location  =   "/nota/avulsa" ;
            }

        });
		
		$("#CD_MERCADORIA").change(function (){
			var cdMercadoria = $("#CD_MERCADORIA").val();
			var totalNota    = parseFloat( $("#totalNota").val() );
			
			if (cdMercadoria > 0) {
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
               	
						var options                = "";

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
		
		$("#natOp").change(function () {
			$("#cfop").val(  Number($("#natOp").val()));
			$("#xNatOp").val( decodeURIComponent(escape($("#natOp :selected").text())) );
		});
		
		$("#cfop").change(function () {
			$("#natOp").val( Number($("#cfop").val()));
			$("#xNatOp").val(  decodeURIComponent(escape($("#natOp :selected").text())) );
		});
		//Copiar total para total de Retenção
		$("#totalNota").change(function(){
			$("#retPis_bc").val( $("#totalNota").val());
			$("#retPis_bc").change();
			$("#retCofins_bc").val( $("#totalNota").val());
			$("#retCofins_bc").change();
			$("#retCsll_bc").val( $("#totalNota").val());
			$("#retCsll_bc").change();
			$("#retIrrf_bc").val( $("#totalNota").val());
			$("#retIrrf_bc").change();
			$("#retPrev_bc").val( $("#totalNota").val());
			$("#retPrev_bc").change();
		});
		//Fazer calculos de Total de retenção
		$("#retPis_bc").change(function(){
			$("#retPis_total").val( formatReal(( parseFloat( $("#retPis_bc").val() ) * parseFloat( $("#retPis_aliq").val() ) ) / 100 ) );
		});
		$("#retCofins_bc").change(function(){
			$("#retCofins_total").val( formatReal(( parseFloat( $("#retCofins_bc").val() ) * parseFloat( $("#retCofins_aliq").val() ) ) / 100 ) );
		});
		$("#retCsll_bc").change(function(){
			$("#retCsll_total").val( formatReal(( parseFloat( $("#retCsll_bc").val() ) * parseFloat( $("#retCsll_aliq").val() ) ) / 100 ) );
		});
		$("#retIrrf_bc").change(function(){
			$("#retIrrf_total").val( formatReal(( parseFloat( $("#retIrrf_bc").val() ) * parseFloat( $("#retIrrf_aliq").val() ) ) / 100 ) );
		});
		$("#retPrev_bc").change(function(){
			$("#retPrev_total").val( formatReal(( parseFloat( $("#retPrev_bc").val() ) * parseFloat( $("#retPrev_aliq").val() ) ) / 100 ) );
		});
		//---------------------------------------------------------------------------------
		$("#retPis_aliq").change(function(){
			$("#retPis_total").val( formatReal(( parseFloat( $("#retPis_bc").val() ) * parseFloat( $("#retPis_aliq").val() ) ) / 100 ) );
		});
		$("#retCofins_aliq").change(function(){
			$("#retCofins_total").val( formatReal(( parseFloat( $("#retCofins_bc").val() ) * parseFloat( $("#retCofins_aliq").val() ) ) / 100 ) );
		});
		$("#retCsll_aliq").change(function(){
			$("#retCsll_total").val( formatReal(( parseFloat( $("#retCsll_bc").val() ) * parseFloat( $("#retCsll_aliq").val() ) ) / 100 ) );
		});
		$("#retIrrf_aliq").change(function(){
			$("#retIrrf_total").val( formatReal(( parseFloat( $("#retIrrf_bc").val() ) * parseFloat( $("#retIrrf_aliq").val() ) ) / 100 ) );
		});
		$("#retPrev_aliq").change(function(){
			$("#retPrev_total").val( formatReal(( parseFloat( $("#retPrev_bc").val() ) * parseFloat( $("#retPrev_aliq").val() ) ) / 100 ) );
		});
		
		verificaStatus = function(button) {
            if (button.html() == '<i class="icon-white"></i>') {
                button.html('<i class="icon-white icon-ok"></i>');
            }else{
                button.html('<i class="icon-white"></i>');
            }
        },
		
		$("#retPis_bc").change();
		$("#retCofins_bc").change();
		$("#retCsll_bc").change();
		$("#retIrrf_bc").change();
		$("#retPrev_bc").change();

        $("#valorDesconto").change(function(){

            var valorDesconto           = parseFloat($(this).val().replace(",","."));
            var subTotalNota            = parseFloat($("#subTotalNota").val().replace(",","."));
            var desconto                = 0;

            if (isNaN(valorDesconto)) {
                valorDesconto = 0;
            }

            if ((valorDesconto >= 0) && (subTotalNota > 0)) {
                desconto          = parseFloat((valorDesconto * 100) / subTotalNota).toFixed(4);
                $("#nrPercentualDesconto").val(desconto).change();

            } else {
                $("#nrPercentualDesconto").val("0.00").change();
            }

        });

        $("#nrPercentualDesconto").change(function(){

            var nrPercentualDesconto    = parseFloat($(this).val());
            var desconto                = 0;

            if (isNaN(nrPercentualDesconto)) {
                nrPercentualDesconto = 0;
            }

            if (nrPercentualDesconto >= 0) {

                var subTotalNota          = parseFloat( $("#subTotalNota").val().replace(",","."));
                var totalNota             = parseFloat( $("#totalNota").val().replace(",",".") );

                totalNota       = subTotalNota * parseFloat( 1 - (nrPercentualDesconto/100));
                desconto        = parseFloat(subTotalNota - totalNota);

                $("#valorDesconto").val(formatReal(desconto));
                $("#totalNota").val(formatReal(totalNota)).change();
                aplicaDescontoMercadoriaPorItem(nrPercentualDesconto);
            }

        });

        aplicaDescontoMercadoriaPorItem = function(nrPercentualDesconto){
            $("button[type='button'][name^='chkMercadoria']").each(function() {
                var codigoMercadoria            =  $(this).val();
                var qtdeVendida                 =  $('#qtdVendida-'+codigoMercadoria).val();
                var valorUnitario               =  parseFloat($('#vl_preco_unitario-'+codigoMercadoria).val());
                var vlPrecoDescontoMercadoria   =  (valorUnitario - parseFloat((valorUnitario * nrPercentualDesconto)/100)).toFixed(2);
                var vlPrecoTotalMercadoria      =  parseFloat(qtdeVendida * vlPrecoDescontoMercadoria).toFixed(2);

                //preço com desconto
                $('#vl_preco_desconto-'+codigoMercadoria).val(vlPrecoDescontoMercadoria);
                $('#span_vl_preco_desconto-'+codigoMercadoria).text(vlPrecoDescontoMercadoria);

                //preço com desconto X a quantidade
                $('#vl_tot-'+codigoMercadoria).val(vlPrecoTotalMercadoria);
                $('#span_vl_tot-'+codigoMercadoria).text(vlPrecoTotalMercadoria);
            });
        },


        exibirDivRefNfe = function (finalidade) {
            if (finalidade == 4) {
                $("#divRefNfe").css('visibility','visible');
                $("#refNFe").attr('required',true);
                $("#indPag2").trigger('click');
            } else {
                $("#divRefNfe").css('visibility','hidden');
                $("#refNFe").removeAttr('required');
                $("#indPag0").trigger('click');
            }
        }

    },
	
}