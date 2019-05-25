
var Nota = {
    init: function() {
		
		//componete foi alterado - procurar linhas comentadas
		/*$(".autocomplete").flexbox('/cliente/buscarCliente', {
			queryDelay: 800,
			minChars: 3,
			paging: {
				pageSize: 5,
				summaryTemplate: 'Exibindo {start}-{end} de {total} registros'
			},
			width: 390,
			//class: 'form-control',
			//                        watermark: 'Informe o nome do cliente',
			hiddenValue: 'name',
			initialValue: '',
			onSelect: function() {

			}
		});	*/
		
		/*setInterval(function() {
			$('#ds_nome_razao_social_input').attr('required', 'required');
			$('#ds_nome_razao_social_input').removeAttr('style');
			$('#ds_nome_razao_social_input').addClass('form-control');
		}, 200);*/
		
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
			var cdMercadoria  = $("#CD_MERCADORIA").val();
			var qtdVendida    = $("#qtd_mercadoria").val();
			var dscMercadoria = $("#ds_mercadoria").val();
			var vlUnt		  = $("#vl_preco_unitario").val();
			var vlTot		  = $("#vl_tot").val();
			var totalNota     = parseFloat( $("#totalNota").val() );
			oTablePedido.fnAddData(['<button type="button" name="chkMercadoria[]" id="chkMercadoria" value="' + cdMercadoria + '" class="btn btn-info" onclick="verificaStatus($(this))"><i class="icon-white"></i></button>'
														+ ' <input type="hidden" name="cdMercadoria[]" value="' + cdMercadoria + '" /> ',
														//+ ' <input type="hidden" name="stServico-' + value.CD_MERCADORIA + '" value="' + value.ST_SERVICO + '" /> ',
														dscMercadoria + ' <input type="hidden" name="ds_mercadoria-' + cdMercadoria + '" value="' + dscMercadoria + '" /> ',
														qtdVendida + ' <input type="hidden" name="qtdVendida-' + cdMercadoria + '" value="' + qtdVendida + '" /> ',
														vlUnt + ' <input type="hidden" name="vl_preco_unitario-' + cdMercadoria + '" value="' + vlUnt + '" /> ',
														vlTot + ' <input type="hidden" name="vl_tot-' + cdMercadoria + '" id="vl_tot-' + cdMercadoria + '" value="' + vlTot + '" /> '
													]);
			totalNota = formatReal( totalNota  + ( qtdVendida * vlUnt )); 
											
			$("#totalNota").val( totalNota );
			$("#totalNota").change();
			calculaTotalNota();
		});
		
		$("#btnExcluirMercadoria").click( function(){
			
			var totalNota     = parseFloat( $("#totalNota").val() );
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

			$("#totalNota").val( formatReal(parseFloat(totalNota) - parseFloat(totalExcluido)));
			$("#totalNota").change();
            //calculaTotalNota();
		});
		
		$("#CD_MERCADORIA").change(function (){
			var cdMercadoria = $("#CD_MERCADORIA").val();
			var totalNota    = parseFloat( $("#totalNota").val() );
			
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
							alert(data.result);
							return false;
						}
               	
						var options = "";
						$.each(data, function(key, value) {
							if (key == 'data') {

                                $.each(value, function(keyMercadoria, mercadoriaValue) {
                                    $("#CD_MERCADORIA").val(mercadoriaValue.CD_MERCADORIA);
                                    $("#qtd_mercadoria").val('1');
                                    $("#ds_mercadoria").val(decodeURIComponent(escape(mercadoriaValue.DS_MERCADORIA)));
                                    $("#vl_preco_unitario").val(formatReal(mercadoriaValue.VL_PRECO_VENDA));
                                });

								$("#vl_preco_unitario").blur();								
							}
						});
					}
				});
            	
                //$("#comboSelectServico").val("");
            }
		});
		
		$("#vl_preco_unitario").blur(function(){
			var precoUnd = parseFloat( $("#vl_preco_unitario").val() );
			$("#vl_tot").val( formatReal(precoUnd * $("#qtd_mercadoria").val()));
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
			$("#cfop").val( $("#natOp").val());
			$("#xNatOp").val( decodeURIComponent(escape($("#natOp :selected").text())) );
		});
		
		$("#cfop").change(function () {
			$("#natOp").val( $("#cfop").val());
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
		//var table = document.querySelector("table");
		//var data  = parseTable(table);
		//console.log(data);
		
		verificaStatus = function(button) {
            if (button.html() == '<i class="icon-white"></i>') {
                button.html('<i class="icon-white icon-ok"></i>');
            }else{
                button.html('<i class="icon-white"></i>');
            }
        }
		
		$("#retPis_bc").change();
		$("#retCofins_bc").change();
		$("#retCsll_bc").change();
		$("#retIrrf_bc").change();
		$("#retPrev_bc").change();
		
		calculaTotalNota = function(){
			
			$.each(oTablePedido, function(key, value) {
				//console.log(value);
			});
		}

        exibirDivRefNfe = function (finalidade) {
            if (finalidade == 4) {
                $("#divRefNfe").css('visibility','visible');
                $("#refNFe").attr('required',true);
            } else {
                $("#divRefNfe").css('visibility','hidden');
                $("#refNFe").removeAttr('required');
            }
        }

    },
	
}