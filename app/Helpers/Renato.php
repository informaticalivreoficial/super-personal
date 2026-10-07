<?php

namespace App\Helpers;

class Renato
{
    /**
    * <b>Saudação:</b> Ao executar este HELPER, dependendo do horário envia uma saudação
    * nome. retorna o texto informado + a saudação!
    * @return HTML = texto informado + a saudação!
    */
    public static function getSaudacao($nome = null)
    {
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

    /**
    * <b>Primeiro Nome:</b> Ao executar este HELPER, é retornado o primeiro nome
    * do usuário!
    * @return HTML = texto informado primeiro nome!
    */
    public static function getPrimeiroNome($pNome) {
        if(!empty($pNome)):
            $pData = explode(" ",$pNome);
            return count( $pData ) > 0 ? $pData[0] : $pNome;
        else:
            return false;
        endif;
    }

    /**
    * <b>Limpa Telefone:</b> Ao executar este HELPER, são eliminados
    * espaços traços e outros caracteres do numero de telefone
    * @return HTML = texto informado número limpo!
    */
    public static function limpaTelefone($telefone)
    {
        if(empty($telefone)){
            return null;
        }
        $valor = str_replace(['(',')', '-', ' '], '', $telefone);
        
        return $valor;
    }

    /**
    * <b>Compara datas:</b> compara duas datas e retorna se expirado
    * @return HTML = texto Expirado!
    */
    public static function comparaDataExpira($data)
    {
        if(empty($data)){
            return null;
        }

        $dt_atual		     = date("Y-m-d"); // data atual
        $timestamp_dt_atual  = strtotime($dt_atual); // converte para timestamp
        $dt_expira		     = $data; // data de expiração
        $timestamp_dt_expira = strtotime($dt_expira); // converte para timestamp

        if($timestamp_dt_atual > $timestamp_dt_expira){
            return '<span style="color:red;">Expirado</span>';
        }else{
            return date('d/m/Y', strtotime($data));
        }
    }

    /**
    * <b>Limpa Cpf Cnpj:</b> remove todos os caracteres e retorna 
    * somente os números
    * @return HTML = número sem caracteres!
    */
    public static function limpaCPF_CNPJ($valor){

        if(empty($valor)){
            return null;
        }
        
        $valor = trim($valor);
        $valor = str_replace(".", "", $valor);
        $valor = str_replace(",", "", $valor);
        $valor = str_replace("-", "", $valor);
        $valor = str_replace("/", "", $valor);

        return $valor;
    }

    /**
    * <b>Valida CPF:</b> valida cpf 
    * somente os números
    * @return BOOLEAN = retorna se o cpf é válido ou não True ou False!
    */
    public static function validaCPF($cpf) {

        if(empty($cpf)){
            return null;
        }
 
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

    /**
     * <b>Limita os Palavras:</b> Limita a quantidade de palavras a serem exibidas em uma string!
     * @param STRING $String = Uma string qualquer
     * @return INT = $Limite = String limitada pelo $Limite
     */
    public static function Words($String, $Limite, $Pointer = null) {
        $content = strip_tags(trim($String));
        $Format = (int) $Limite;

        $ArrWords = explode(' ', $content);
        $NumWords = count($ArrWords);
        $NewWords = implode(' ', array_slice($ArrWords, 0, $Format));

        $Pointer = (empty($Pointer) ? '...' : ' ' . $Pointer );
        $Result = ( $Format < $NumWords ? $NewWords . $Pointer : $content );
        return $Result;
    }

}