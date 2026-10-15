{{-- Texto padrão dos Termos de Uso (usado quando Configurações → Termos está vazio).
     O admin pode substituir todo o conteúdo pelo editor Quill no painel. --}}

<h2>1. Aceitação</h2>
<p>
    Estes Termos de Uso regem o acesso e a utilização da plataforma
    <strong>{{ $config->app_name ?? config('app.name') }}</strong> ("Plataforma"), destinada a
    treinadores, personal trainers, coaches e assessorias esportivas para a gestão de alunos,
    planos de treino, acompanhamento de execução e cobrança.
</p>
<p>
    Ao criar uma conta ou utilizar a Plataforma, você declara ter lido e concordado com estes
    Termos e com a <a href="{{ route('web.privacy') }}">Política de Privacidade</a>. Se não
    concordar, não utilize o serviço.
</p>

<h2>2. Cadastro e conta</h2>
<p>
    O cadastro é gratuito e destinado a maiores de 18 anos. Você é responsável pela veracidade
    das informações fornecidas e pela guarda das suas credenciais de acesso, respondendo pelas
    atividades realizadas na sua conta.
</p>
<p>
    Cada conta representa um treinador (ou equipe) e é isolada das demais: os dados dos seus
    alunos, treinos, avaliações e pagamentos não são acessíveis por outros treinadores.
</p>

<h2>3. Planos e pagamento</h2>
<ul>
    <li>Existe um plano gratuito com limite de alunos cadastrados e planos pagos com limites maiores.</li>
    <li>Os valores e limites vigentes são os exibidos na página de planos da Plataforma no momento da contratação.</li>
    <li>Os planos pagos são pré-pagos por período (mensal, trimestral ou anual) e não há cobrança automática
        sem a sua confirmação de pagamento.</li>
    <li>O pagamento é processado por parceiro especializado: os dados completos do seu cartão não são
        armazenados pela Plataforma.</li>
    <li>Você pode cancelar a qualquer momento. O acesso permanece até o fim do período já pago e,
        encerrado esse prazo, a conta retorna ao plano gratuito.</li>
</ul>

<h2>4. Responsabilidades do treinador</h2>
<p>
    A Plataforma é uma ferramenta de gestão. A prescrição, a orientação e o acompanhamento dos
    treinos são de responsabilidade técnica do treinador, que deve estar devidamente habilitado
    para a modalidade.
</p>
<p>
    Em relação aos dados dos alunos cadastrados por você, a Plataforma atua como
    <strong>operadora</strong>: cabe ao treinador garantir que possui base legal para inserir
    esses dados, orientar seus alunos sobre o tratamento e mantê-los corretos e atualizados.
</p>

<h2>5. Uso aceitável</h2>
<p>É vedado:</p>
<ul>
    <li>usar a Plataforma para fins ilícitos, para envio de conteúdo ofensivo ou spam;</li>
    <li>tentar acessar dados de outras contas, contornar limites de plano ou mecanismos de segurança;</li>
    <li>copiar, revender, sublicenciar ou realizar engenharia reversa do software;</li>
    <li>sobrecarregar a infraestrutura com automações não autorizadas.</li>
</ul>
<p>
    O descumprimento pode levar à suspensão ou ao encerramento da conta, sem prejuízo das medidas legais cabíveis.
</p>

<h2>6. Propriedade intelectual</h2>
<p>
    O software, a marca e os layouts da Plataforma pertencem aos seus titulares. O conteúdo que você
    cadastra (textos, planos, imagens, dados dos alunos) continua sendo seu, e você concede à
    Plataforma apenas a licença necessária para armazenar, processar e exibir esse conteúdo dentro
    do próprio serviço.
</p>

<h2>7. Disponibilidade e limitações</h2>
<p>
    Empregamos esforços comerciais razoáveis para manter a Plataforma disponível e segura, mas o
    serviço pode passar por manutenções ou indisponibilidades pontuais. A Plataforma não se
    responsabiliza por resultados esportivos dos alunos nem por decisões técnicas tomadas pelo treinador.
</p>

<h2>8. Alterações destes Termos</h2>
<p>
    Podemos atualizar estes Termos para refletir mudanças no serviço, na legislação ou nas práticas
    de mercado. Alterações relevantes serão comunicadas na Plataforma ou por e-mail com
    antecedência razoável. O uso continuado após a vigência implica concordância.
</p>

<h2>9. Legislação e contato</h2>
<p>
    Estes Termos são regidos pela legislação brasileira. Dúvidas podem ser encaminhadas pelos canais
    de atendimento da Plataforma.
</p>
@if (! empty($config->email))
    <p>
        E-mail de contato: <strong>{{ $config->email }}</strong>.
    </p>
@endif
