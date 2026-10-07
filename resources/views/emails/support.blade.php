<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Solicitação de Suporte</title>
</head>
<body style="margin:0; padding:0; font-family:'Segoe UI', Arial, sans-serif; background-color:#f5f5f5;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f5f5; padding:30px 0;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
                    <!-- Cabeçalho -->
                    <tr>
                        <td style="background-color:#0d9488; color:#ffffff; padding:20px 30px; font-size:22px; font-weight:600;">
                            Solicitação de Suporte 🆘
                        </td>
                    </tr>

                    <!-- Conteúdo -->
                    <tr>
                        <td style="padding:30px; color:#333333;">

                            <p style="font-size:16px; margin-bottom:15px;">
                                <strong>Data do envio:</strong> {{ now()->format('d/m/Y H:i') }}
                            </p>

                            <h2 style="font-size:18px; color:#0d9488; border-bottom:1px solid #e0e0e0; padding-bottom:8px;">Dados Pessoais</h2>
                            <table width="100%" style="font-size:12px; margin-bottom:20px;">
                                <tr>
                                    <td><strong>Cliente:</strong> {{ $Cliente ?? '-' }}</td>
                                    <td><strong>E-mail:</strong> {{ $email ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Responsável:</strong> {{ $nome ?? '-' }}</td>
                                    <td></td>
                                </tr>
                            </table>                    
                        </td>
                    </tr> 
                    <tr>
                        <td style="padding:0 30px 30px 30px; color:#333333;">
                            <h2 style="font-size:18px; color:#0d9488; border-bottom:1px solid #e0e0e0; padding-bottom:8px; margin-top:0;">Mensagem</h2>
                            <p style="font-size:14px; line-height:1.5;">
                                {{ nl2br(e($messageText)) ?? '-' }}
                            </p>
                        </td>                   
                    <tr>
                        <td style="background-color:#f9fafb; padding:15px 30px; text-align:center; font-size:11px; color:#888;">                                                       
                            {{ config('app.name') }} © {{ date('Y') }}<br>
                            Desenvolvido por 
                            <a href="mailto:{{ env('DESENVOLVEDOR_EMAIL') }}" style="color:#0d9488; text-decoration:none;">
                                {{ env('DESENVOLVEDOR') }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
