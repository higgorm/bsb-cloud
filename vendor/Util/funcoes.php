<?php
//Classe com funções util
Class Util{
	
	function retornaOptionUF( $id, $selected ){
		$aUF = array(
		    'AC',
		    'AL',
		    'AM',
		    'AP',
		    'BA',
		    'CE',
		    'DF',
		    'ES',
		    'GO',
		    'MA',
		    'MG',
		    'MS',
		    'MT',
		    'PA',
		    'PB',
		    'PE',
		    'PI',
		    'PR',
		    'RJ',
		    'RN',
		    'RO',
		    'RR',
		    'RS',
		    'SC',
		    'SE',
		    'SP',
		    'TO'
		);
		
		$selUF = 	'<select id="'.$id.'" name="'.$id.'" class="form-control">
						<option value="">Selecione</option>';
		foreach ($aUF as $sigla) {
		    $option = '';
		    if ($sigla == $selected) {
		        $option = 'SELECTED';
		    }
		    $selUF .= '<option value="'.$sigla.'" '.$option.'>'.$sigla.'</option>';
		}
		$selUF .= '</select>';
		
		return $selUF;
	}
	
	function mask($val, $mask){
		$maskared = '';
		$k = 0;
		for($i = 0; $i<=strlen($mask)-1; $i++){
			if($mask[$i] == '#'){
				if(isset($val[$k]))
					$maskared .= $val[$k++];
			}else{
				if(isset($mask[$i]))
					$maskared .= $mask[$i];
			}
		}
		return $maskared;
	}

}

?>