<?php

/**
 * <b>Limita os Palavras:</b> Limita a quantidade de palavras a serem exibidas em uma string!
 * @param STRING $String = Uma string qualquer
 * @return INT = $Limite = String limitada pelo $Limite
 */
function Words($String, $Limite, $Pointer = null) {
    $content = strip_tags(trim($String));
    $Format = (int) $Limite;

    $ArrWords = explode(' ', $content);
    $NumWords = count($ArrWords);
    $NewWords = implode(' ', array_slice($ArrWords, 0, $Format));

    $Pointer = (empty($Pointer) ? '...' : ' ' . $Pointer );
    $Result = ( $Format < $NumWords ? $NewWords . $Pointer : $content );
    return $Result;
}

// FUNÇÃO PARA LIMPAR O TELEFONE
function limpaTelefone($telefone)
{
    if(empty($telefone)){
        return null;
    }
	$valor = str_replace(['(',')', '-', ' '], '', $telefone);
	
    return $valor;
}

// Pega o Link do iframe google maps
function getLinkGoogleMaps($link)
{
    if(empty($link)){
        return null;
    }
	$src = str_replace('<iframe src="', '', $link);
	$src1 = str_replace('" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>', '', $src);
	
    return $src1;
}

// pega o nome da cidade a partir de um ID relacionado
function getCidadeNome($id, $tabela)
{
    if (empty($id) && empty($tabela)) {
        return null;
    }
    $cidade = Illuminate\Support\Facades\DB::table(''.$tabela.'')->where('cidade_id', '=', $id)->get();
    if(!empty($cidade)){
        return $cidade[0]->cidade_nome.'/'.$cidade[0]->cidade_uf;
    }else{
        return null;
    }
}

// pega o nome da cidade a partir de um ID relacionado
function getCidade($id, $tabela)
{
    if (empty($id) && empty($tabela)) {
        return null;
    }
    $cidade = Illuminate\Support\Facades\DB::table(''.$tabela.'')->where('cidade_id', '=', $id)->get();
    if(!empty($cidade)){
        return $cidade[0]->cidade_nome;
    }else{
        return null;
    }
}

// pega o nome da cidade a partir de um ID relacionado
function getEstado($id, $tabela, $campo = null)
{
    if (empty($id) && empty($tabela)) {
        return null;
    }
    $estado = Illuminate\Support\Facades\DB::table(''.$tabela.'')->where('estado_id', '=', $id)->get();
    if(!empty($estado)){
        if($campo == null){
            return $estado[0]->estado_nome;
        }else{
            return $estado[0]->{$campo};
        }
    }else{
        return null;
    }
}

/**
* <b>Formata Numero WhatsApp:</b> Ao executar este HELPER, ele automaticamente converte o numero para o formato aceito
* zap. retorna o link formatado!
* @return HTML = numero formatado!
*/
function getNumZap($nZap ,$textZap = null) {
   if(!empty($nZap)):
       $textZap = ($textZap == null ? getSaudacao() : $textZap);
       $zap = '55' . preg_replace("/[^0-9]/", "", $nZap);
       return "https://api.whatsapp.com/send?l=pt_pt&phone={$zap}&text={$textZap}";
   endif;
   return null;
}

/**
* <b>Saudação:</b> Ao executar este HELPER, dependendo do horário envia uma saudação
* nome. retorna o texto informado + a saudação!
* @return HTML = texto informado + a saudação!
*/
function getSaudacao($nome = null) {
   date_default_timezone_set('America/Sao_Paulo');
   $hora = date('H');		
   if($hora >= 6 && $hora <= 12):
       return (empty($nome) ? '' : $nome).' bom dia';		
   elseif( $hora > 12 && $hora <=18  ):
       return (empty($nome) ? '' : $nome).' boa tarde';		
   else:			
       return (empty($nome) ? '' : $nome).' boa noite';	
   endif;
}

/*****************************
    FUNÇÃO PARA PEGAR SOMENTE O PRIMEIRO NOME DO USUÁRIO
*****************************/
function getPrimeiroNome($pNome) {
    if(!empty($pNome)):
        $pData = explode(" ",$pNome);
        return count( $pData ) > 0 ? $pData[0] : $pNome;
    else:
        return false;
    endif;
    // return false;
}
/*****************************
    FUNÇÃO PARA PEGAR SOMENTE O USUÁRIO DA URL FACEBOOK
*****************************/
function fbUser($url)
{
    $regex ='/https?\:\/\/(?:www\.|web\.|m\.|touch\.)?(?:facebook\.com|fb(?:\.me|\.com))\/(\d+|[A-Za-z0-9\.]+)\/?/';
    if( preg_match( $regex, $url, $matches ) ){
        return $matches['1'];
    }else{
        return false;
    } 
 }
/*****************************
    FUNÇÃO PARA VALIDAR CPF
*****************************/
function validaCPF($cpf) {
 
    // Extrai somente os números
    $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
     
    // Verifica se foi informado todos os digitos corretamente
    if (strlen($cpf) != 11) {
        return false;
    }

    // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Faz o calculo para validar o CPF
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

function limpaCPF_CNPJ($valor){
    $valor = trim($valor);
    $valor = str_replace(".", "", $valor);
    $valor = str_replace(",", "", $valor);
    $valor = str_replace("-", "", $valor);
    $valor = str_replace("/", "", $valor);
    return $valor;
}

