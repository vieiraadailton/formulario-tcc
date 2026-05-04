<?php
// ─── index.php ────────────────────────────────────────────────────────────────
// Ponto de entrada da aplicação.
// Responsabilidade: orquestrar as camadas — não contém lógica de banco nem CSS.
//
// Fluxo:
//   1. Carrega as dependências (autoload manual via require_once)
//   2. Se POST → sanitiza → valida → insere no banco
//   3. Passa resultados para o template HTML
// ─────────────────────────────────────────────────────────────────────────────

// ── Carregamento das camadas ──────────────────────────────────────────────────
require_once __DIR__ . '/auxiliares/Sanitizador.php';
require_once __DIR__ . '/validadores/ValidadorFornecedor.php';
require_once __DIR__ . '/modelos/Fornecedor.php';
require_once __DIR__ . '/dal/Conexao.php';
require_once __DIR__ . '/dal/FornecedorRepositorio.php';

// ── Variáveis de estado da página ─────────────────────────────────────────────
$notificacao   = '';        // texto exibido na faixa de retorno
$tipoNotific   = '';        // 'sucesso' | 'erro' | 'aviso'
$camposForm    = [          // espelho dos campos — mantém o que o usuário digitou
    'nome_fornecedor' => '',
    'endereco'        => '',
    'bairro'          => '',
    'estado'          => '',
    'nome_bebida'     => '',
    'distribuidora'   => '',
    'status_analise'  => '',
];
$errosForm     = [];        // erros de validação indexados por campo

// ── Lista de estados para o <select> ─────────────────────────────────────────
$estadosBrasil = [
    'AC'=>'Acre',          'AL'=>'Alagoas',          'AP'=>'Amapá',
    'AM'=>'Amazonas',      'BA'=>'Bahia',             'CE'=>'Ceará',
    'DF'=>'Distrito Federal','ES'=>'Espírito Santo',  'GO'=>'Goiás',
    'MA'=>'Maranhão',      'MT'=>'Mato Grosso',       'MS'=>'Mato Grosso do Sul',
    'MG'=>'Minas Gerais',  'PA'=>'Pará',              'PB'=>'Paraíba',
    'PR'=>'Paraná',        'PE'=>'Pernambuco',        'PI'=>'Piauí',
    'RJ'=>'Rio de Janeiro','RN'=>'Rio Grande do Norte','RS'=>'Rio Grande do Sul',
    'RO'=>'Rondônia',      'RR'=>'Roraima',           'SC'=>'Santa Catarina',
    'SP'=>'São Paulo',     'SE'=>'Sergipe',            'TO'=>'Tocantins',
];

// ── Processamento do formulário (somente em requisições POST) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar') {

    // 1. Sanitização — limpa os dados brutos
    $camposForm = Sanitizador::limparFormulario($_POST);

    // 2. Validação — aplica as regras de negócio
    $errosForm = ValidadorFornecedor::validar($camposForm);

    if (!empty($errosForm)) {
        // Encontrou erros: notifica o usuário e mantém os campos preenchidos
        $notificacao = '⚠️ Por favor, corrija os erros indicados abaixo.';
        $tipoNotific = 'aviso';

    } else {
        // 3. Monta o modelo com os dados validados
        $fornecedor = Fornecedor::doCampos($camposForm);

        try {
            // 4. Persiste no banco via repositório
            $repositorio  = new FornecedorRepositorio();
            $idGerado     = $repositorio->inserir($fornecedor);
            $rotulo       = $fornecedor->rotuloDaAnalise();

            $notificacao = "✅ Resultado cadastrado com sucesso! (ID: {$idGerado} — Status: {$rotulo})";
            $tipoNotific = 'sucesso';

            // Limpa os campos após salvar com sucesso
            $camposForm = array_fill_keys(array_keys($camposForm), '');

        } catch (RuntimeException $excecao) {
            $notificacao = '❌ ' . $excecao->getMessage();
            $tipoNotific = 'erro';
        } finally {
            Conexao::encerrar();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Resultados</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700&family=Barlow:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="publico/css/estilos.css">
</head>
<body>

<!-- ── Botão de alternância de tema ────────────────────────────────────────── -->
<button class="alternador-tema" onclick="alternarTema()" title="Alternar tema">
    <span class="icone-tema" id="icone-tema">☀️</span>
    <span id="rotulo-tema">CLARO</span>
</button>

<!-- ── Conteúdo principal ──────────────────────────────────────────────────── -->
<div class="container">

    <!-- Cabeçalho -->
    <div class="cabecalho">
        <div class="cabecalho-icone">🍺</div>
        <div>
            <div class="cabecalho-titulo">Cadastro de Resultados</div>
            <div class="cabecalho-subtitulo">Registro de resultados encontrados</div>
        </div>
    </div>

    <!-- Faixa de notificação (aparece somente após submissão) -->
    <?php if ($notificacao !== ''): ?>
        <div class="mensagem <?= $tipoNotific ?>"><?= $notificacao ?></div>
    <?php endif; ?>

    <!-- Formulário principal -->
    <form method="POST" action="" id="formulario-fornecedor" novalidate>
        <input type="hidden" name="acao" value="salvar">

        <div class="cartao">

            <!-- ── Seção 1: Dados do Fornecedor ──────────────────────────── -->
            <div class="cartao-secao">
                <div class="secao-rotulo">Dados do Fornecedor</div>
                <div class="grade">
                    <div class="campo">
                        <label for="nome_fornecedor">Nome do Fornecedor *</label>
                        <input
                            type="text"
                            id="nome_fornecedor"
                            name="nome_fornecedor"
                            placeholder="Ex.: Distribuidora Central Ltda."
                            maxlength="150"
                            value="<?= htmlspecialchars($camposForm['nome_fornecedor']) ?>"
                            class="<?= isset($errosForm['nome_fornecedor']) ? 'invalido' : '' ?>"
                            oninput="contarCaracteres(this, 'cont-nome', 150)"
                        >
                        <span class="contador" id="cont-nome"><?= mb_strlen($camposForm['nome_fornecedor']) ?>/150</span>
                        <?php if (isset($errosForm['nome_fornecedor'])): ?>
                            <span class="erro-campo"><?= $errosForm['nome_fornecedor'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Seção 2: Localização ───────────────────────────────────── -->
            <div class="cartao-secao">
                <div class="secao-rotulo">Localização</div>
                <div class="grade">
                    <div class="campo">
                        <label for="endereco">Endereço *</label>
                        <input
                            type="text"
                            id="endereco"
                            name="endereco"
                            placeholder="Rua, número, complemento"
                            maxlength="255"
                            value="<?= htmlspecialchars($camposForm['endereco']) ?>"
                            class="<?= isset($errosForm['endereco']) ? 'invalido' : '' ?>"
                            oninput="contarCaracteres(this, 'cont-endereco', 255)"
                        >
                        <span class="contador" id="cont-endereco"><?= mb_strlen($camposForm['endereco']) ?>/255</span>
                        <?php if (isset($errosForm['endereco'])): ?>
                            <span class="erro-campo"><?= $errosForm['endereco'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="grade grade-3-colunas">
                        <div class="campo">
                            <label for="bairro">Bairro *</label>
                            <input
                                type="text"
                                id="bairro"
                                name="bairro"
                                placeholder="Ex.: Centro"
                                maxlength="100"
                                value="<?= htmlspecialchars($camposForm['bairro']) ?>"
                                class="<?= isset($errosForm['bairro']) ? 'invalido' : '' ?>"
                                oninput="contarCaracteres(this, 'cont-bairro', 100)"
                            >
                            <span class="contador" id="cont-bairro"><?= mb_strlen($camposForm['bairro']) ?>/100</span>
                            <?php if (isset($errosForm['bairro'])): ?>
                                <span class="erro-campo"><?= $errosForm['bairro'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="campo">
                            <label for="estado">Estado *</label>
                            <select
                                id="estado"
                                name="estado"
                                class="<?= isset($errosForm['estado']) ? 'invalido' : '' ?>"
                            >
                                <option value="">— UF —</option>
                                <?php foreach ($estadosBrasil as $sigla => $nomeEstado): ?>
                                    <option
                                        value="<?= $sigla ?>"
                                        <?= ($camposForm['estado'] === $sigla) ? 'selected' : '' ?>
                                    >
                                        <?= $sigla ?> — <?= $nomeEstado ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errosForm['estado'])): ?>
                                <span class="erro-campo"><?= $errosForm['estado'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Seção 3: Produto ───────────────────────────────────────── -->
            <div class="cartao-secao">
                <div class="secao-rotulo">Produto</div>
                <div class="grade grade-2-colunas">
                    <div class="campo">
                        <label for="nome_bebida">Nome da Bebida *</label>
                        <input
                            type="text"
                            id="nome_bebida"
                            name="nome_bebida"
                            placeholder="Ex.: Cerveja Pilsen 600ml"
                            maxlength="150"
                            value="<?= htmlspecialchars($camposForm['nome_bebida']) ?>"
                            class="<?= isset($errosForm['nome_bebida']) ? 'invalido' : '' ?>"
                            oninput="contarCaracteres(this, 'cont-bebida', 150)"
                        >
                        <span class="contador" id="cont-bebida"><?= mb_strlen($camposForm['nome_bebida']) ?>/150</span>
                        <?php if (isset($errosForm['nome_bebida'])): ?>
                            <span class="erro-campo"><?= $errosForm['nome_bebida'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="campo">
                        <label for="distribuidora">Distribuidora *</label>
                        <input
                            type="text"
                            id="distribuidora"
                            name="distribuidora"
                            placeholder="Ex.: Ambev, Heineken…"
                            maxlength="150"
                            value="<?= htmlspecialchars($camposForm['distribuidora']) ?>"
                            class="<?= isset($errosForm['distribuidora']) ? 'invalido' : '' ?>"
                            oninput="contarCaracteres(this, 'cont-distribuidora', 150)"
                        >
                        <span class="contador" id="cont-distribuidora"><?= mb_strlen($camposForm['distribuidora']) ?>/150</span>
                        <?php if (isset($errosForm['distribuidora'])): ?>
                            <span class="erro-campo"><?= $errosForm['distribuidora'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Seção 4: Status da Análise ─────────────────────────────── -->
            <div class="cartao-secao">
                <div class="secao-rotulo">Status da Análise</div>
                <div class="grupo-radio <?= isset($errosForm['status_analise']) ? 'grupo-radio-invalido' : '' ?>">

                    <div class="opcao-radio">
                        <input
                            type="radio"
                            id="status-adulterado"
                            name="status_analise"
                            value="0"
                            <?= ($camposForm['status_analise'] === '0') ? 'checked' : '' ?>
                        >
                        <label class="rotulo-radio" for="status-adulterado">
                            <span class="indicador-radio"></span>
                            ⚠️ Adulterado
                        </label>
                    </div>

                    <div class="opcao-radio">
                        <input
                            type="radio"
                            id="status-aprovado"
                            name="status_analise"
                            value="1"
                            <?= ($camposForm['status_analise'] === '1') ? 'checked' : '' ?>
                        >
                        <label class="rotulo-radio" for="status-aprovado">
                            <span class="indicador-radio"></span>
                            ✅ Aprovado
                        </label>
                    </div>

                </div>
                <?php if (isset($errosForm['status_analise'])): ?>
                    <span class="erro-campo" style="margin-top:8px;display:block;">
                        <?= $errosForm['status_analise'] ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- ── Botões ─────────────────────────────────────────────────── -->
            <div class="area-botoes">
                <button type="submit" class="botao botao-principal">
                    💾 Salvar Resultado
                </button>
                <button type="button" class="botao botao-secundario" onclick="limparFormulario()">
                    🗑 Limpar
                </button>
            </div>

        </div><!-- /cartao -->
    </form>

</div><!-- /container -->

<script>
    // ── Contador de caracteres em tempo real ──────────────────────────────────
    function contarCaracteres(campoEntrada, idContador, maximo) {
        document.getElementById(idContador).textContent =
            campoEntrada.value.length + '/' + maximo;
    }

    // ── Limpa o formulário e remove estados de erro ───────────────────────────
    function limparFormulario() {
        const formulario = document.getElementById('formulario-fornecedor');
        formulario.reset();

        // Zera todos os contadores de caracteres
        const mapaContadores = {
            'cont-nome': 150, 'cont-endereco': 255, 'cont-bairro': 100,
            'cont-bebida': 150, 'cont-distribuidora': 150
        };
        Object.entries(mapaContadores).forEach(([idContador, maximo]) => {
            const elemento = document.getElementById(idContador);
            if (elemento) elemento.textContent = '0/' + maximo;
        });

        // Remove bordas vermelhas dos campos de texto/select
        formulario.querySelectorAll('.invalido')
            .forEach(el => el.classList.remove('invalido'));

        // Remove destaque vermelho do grupo de radio
        formulario.querySelectorAll('.grupo-radio-invalido')
            .forEach(el => el.classList.remove('grupo-radio-invalido'));
    }

    // ── Alterna entre tema escuro e claro ─────────────────────────────────────
    function alternarTema() {
        const raizHtml   = document.documentElement;
        const temaAtual  = raizHtml.getAttribute('data-tema');
        const estaEscuro = temaAtual !== 'claro';

        const icone  = document.getElementById('icone-tema');
        const rotulo = document.getElementById('rotulo-tema');

        if (estaEscuro) {
            raizHtml.setAttribute('data-tema', 'claro');
            icone.textContent  = '🌙';
            rotulo.textContent = 'ESCURO';
            localStorage.setItem('tema-preferido', 'claro');
        } else {
            raizHtml.removeAttribute('data-tema');
            icone.textContent  = '☀️';
            rotulo.textContent = 'CLARO';
            localStorage.setItem('tema-preferido', 'escuro');
        }
    }

    // ── Inicialização ao carregar a página ────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {

        // 1. Restaura o tema que o usuário escolheu anteriormente
        const temaArmazenado = localStorage.getItem('tema-preferido');
        const icone  = document.getElementById('icone-tema');
        const rotulo = document.getElementById('rotulo-tema');

        if (temaArmazenado === 'claro') {
            document.documentElement.setAttribute('data-tema', 'claro');
            icone.textContent  = '🌙';
            rotulo.textContent = 'ESCURO';
        }

        // 2. Inicializa contadores com os valores atuais dos campos
        //    (necessário quando o PHP repopula os campos após erro de validação)
        const mapaContadores = {
            'nome_fornecedor':  ['cont-nome',         150],
            'endereco':         ['cont-endereco',      255],
            'bairro':           ['cont-bairro',        100],
            'nome_bebida':      ['cont-bebida',        150],
            'distribuidora':    ['cont-distribuidora', 150],
        };

        Object.entries(mapaContadores).forEach(([nomeCampo, [idContador, maximo]]) => {
            const campo = document.querySelector(`[name="${nomeCampo}"]`);
            if (campo) contarCaracteres(campo, idContador, maximo);
        });
    });
</script>
</body>
</html>
