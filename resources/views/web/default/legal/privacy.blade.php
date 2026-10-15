{{-- Texto padrão da Política de Privacidade (usado quando Configurações → Privacidade
     está vazio). O admin pode substituir todo o conteúdo pelo editor Quill no painel. --}}

<h2>1. Quem trata os seus dados</h2>
<p>
    A plataforma <strong>{{ $config->app_name ?? config('app.name') }}</strong> é a
    <strong>controladora</strong> dos dados pessoais de treinadores, visitantes e demais usuários
    que criam conta no serviço.
</p>
<p>
    Os dados dos <strong>alunos</strong> cadastrados por um treinador são de responsabilidade desse
    treinador (controlador), que decide o que armazenar e para quê. Nesse caso, a Plataforma atua
    como <strong>operadora</strong>, tratando os dados apenas para prestar o serviço contratado.
</p>

<h2>2. Dados que tratamos</h2>
<ul>
    <li><strong>Dados de cadastro:</strong> nome, e-mail, telefone, dados de identificação profissional
        (como CREF e especialidade) e, opcionalmente, foto de perfil e endereço.</li>
    <li><strong>Dados de uso:</strong> registros de acesso, páginas visitadas, endereço IP e informações
        do dispositivo, usados para segurança e melhoria do serviço.</li>
    <li><strong>Dados dos alunos</strong> inseridos pelo treinador: nome, contato, data de nascimento,
        gênero, altura, nível de condicionamento, disponibilidade de dias, avaliações físicas
        (peso, percentual de gordura, frequência cardíaca, FTP e ritmos), execuções de treino e
        observações.</li>
    <li><strong>Dados de pagamento:</strong> informações do plano contratado e identificador da
        transação. Os dados completos do cartão são digitados e criptografados no seu navegador e
        processados diretamente pelo parceiro de pagamentos — <strong>não</strong> são armazenados
        nos nossos servidores.</li>
</ul>

<h2>3. Para que usamos e com qual base legal</h2>
<ul>
    <li><strong>Executar o contrato:</strong> criar e manter a sua conta, publicar treinos, registrar
        execuções, controlar o plano contratado e processar pagamentos.</li>
    <li><strong>Obrigação legal:</strong> guardar registros fiscais e de acesso quando exigido por lei.</li>
    <li><strong>Legítimo interesse:</strong> prevenir fraudes, garantir a segurança da Plataforma e
        corrigir problemas técnicos.</li>
    <li><strong>Consentimento:</strong> envio de comunicações de produto e a publicação da página
        pública do treinador (quando você ativa essa opção).</li>
</ul>

<h2>4. Compartilhamento</h2>
<p>
    Não vendemos dados pessoais. Compartilhamos o necessário com
    <strong>operadores</strong> que viabilizam o serviço, como provedores de hospedagem e banco de
    dados, serviços de envio de e-mail transacional e processadores de pagamento, sempre limitados à
    finalidade contratada. Também podemos atender requisições de autoridades quando houver
    determinação legal.
</p>

<h2>5. Cookies</h2>
<p>
    Utilizamos apenas cookies e armazenamento local essenciais ao funcionamento do painel (sessão de
    login, preferências de interface e segurança). Não usamos cookies de publicidade nem
    compartilhamos informações para marketing de terceiros.
</p>

<h2>6. Por quanto tempo guardamos</h2>
<p>
    Os dados da sua conta são mantidos enquanto ela existir. Ao solicitar o encerramento, os dados
    são eliminados ou anonimizados, salvo aqueles que precisamos manter por obrigação legal, fiscal
    ou para exercício regular de direitos. Dados de alunos inseridos pelo treinador são mantidos
    conforme a orientação dele, que é o controlador dessas informações.
</p>

<h2>7. Seus direitos</h2>
<p>Nos termos da Lei nº 13.709/2018 (LGPD), você pode solicitar:</p>
<ul>
    <li>confirmação da existência de tratamento e acesso aos dados;</li>
    <li>correção de dados incompletos, inexatos ou desatualizados;</li>
    <li>anonimização, bloqueio ou eliminação de dados desnecessários ou tratados em desconformidade;</li>
    <li>portabilidade a outro fornecedor, mediante requisição expressa;</li>
    <li>informações sobre compartilhamentos e sobre a possibilidade de não consentir;</li>
    <li>revogação do consentimento e oposição a tratamentos específicos.</li>
</ul>
<p>
    Alunos devem procurar primeiro o seu treinador para dados de treino e avaliação; para os dados
    tratados diretamente pela Plataforma, o pedido pode ser feito pelos canais de atendimento
    abaixo.
</p>

<h2>8. Segurança</h2>
<p>
    Adotamos medidas técnicas e administrativas para proteger os dados, como senhas armazenadas com
    algoritmos de hash, controle de acesso por perfil, isolamento dos dados de cada treinador e
    tráfego criptografado. Nenhum sistema é totalmente imune a incidentes; caso ocorra um incidente
    relevante, comunicaremos os titulares e a autoridade competente conforme a lei.
</p>

<h2>9. Menores de idade</h2>
<p>
    A conta na Plataforma é destinada a maiores de 18 anos. O cadastro de alunos menores de idade é
    responsabilidade do treinador, que deve obter o consentimento específico e destacado do
    responsável legal.
</p>

<h2>10. Alterações desta Política</h2>
<p>
    Podemos atualizar esta Política para refletir mudanças no serviço ou na legislação. A versão
    vigente estará sempre disponível nesta página, com a data da última atualização indicada no topo.
</p>

<h2>11. Contato</h2>
<p>
    Para exercer seus direitos ou esclarecer dúvidas sobre o tratamento de dados pessoais, fale com
    a gente pelos canais de atendimento da Plataforma.
</p>
@if (! empty($config->email))
    <p>
        E-mail de contato: <strong>{{ $config->email }}</strong>.
    </p>
@endif
