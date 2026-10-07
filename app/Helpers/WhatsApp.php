<?php

namespace App\Helpers;

class WhatsApp
{
    /**
    * <b>Formata Numero WhatsApp:</b> Ao executar este HELPER, ele automaticamente 
    * converte o numero para o formato aceito
    * zap. retorna o link formatado!
    * @return HTML = numero formatado!
    */
    public static function getNumZap($nZap ,$textZap = null)
    {
        if(!empty($nZap)):
            $textZap = ($textZap == null ? Renato::getSaudacao() : $textZap);
            $zap = '55' . preg_replace("/[^0-9]/", "", $nZap);
            return "https://web.whatsapp.com/send?l=pt_pt&phone={$zap}&text={$textZap}";
        endif;
        return null;
    }
}