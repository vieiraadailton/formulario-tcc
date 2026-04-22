<?php
// ─── Configuração do Banco de Dados ───────────────────────────────────────────
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'sua_senha';
$db_name = 'cadastro_bebidas';

// ─── Conexão com MySQL ────────────────────────────────────────────────────────
function conectarDB($host, $user, $pass, $name) {
    $conn = new mysqli($host, $user, $pass, $name);
    if ($conn->connect_error) {
        return null;
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// ─── Script SQL para criar tabela (execute uma vez) ───────────────────────────
/*
CREATE DATABASE IF NOT EXISTS cadastro_bebidas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cadastro_bebidas;
CREATE TABLE IF NOT EXISTS fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_fornecedor VARCHAR(150) NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    bairro VARCHAR(100) NOT NULL,
    estado CHAR(2) NOT NULL,
    nome_bebida VARCHAR(150) NOT NULL,
    distribuidora VARCHAR(150) NOT NULL,
    status_analise TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=adulterado, 1=aprovado',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);
*/

// ─── Variáveis de controle ────────────────────────────────────────────────────
$mensagem    = '';
$tipo_msg    = '';
$dados       = [
    'nome_fornecedor' => '',
    'endereco'        => '',
    'bairro'          => '',
    'estado'          => '',
    'nome_bebida'     => '',
    'distribuidora'   => '',
    'status_analise'  => ''    // 0 = adulterado | 1 = aprovado
];
$erros       = [];

// ─── Processamento do formulário ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar') {

    // Sanitização
    $dados['nome_fornecedor'] = trim(htmlspecialchars($_POST['nome_fornecedor'] ?? ''));
    $dados['endereco']        = trim(htmlspecialchars($_POST['endereco']        ?? ''));
    $dados['bairro']          = trim(htmlspecialchars($_POST['bairro']          ?? ''));
    $dados['estado']          = strtoupper(trim(htmlspecialchars($_POST['estado'] ?? '')));
    $dados['nome_bebida']     = trim(htmlspecialchars($_POST['nome_bebida']     ?? ''));
    $dados['distribuidora']   = trim(htmlspecialchars($_POST['distribuidora']   ?? ''));
    $dados['status_analise']  = $_POST['status_analise'] ?? '';

    // ── Validações ─────────────────────────────────────────────────────────────
    if (empty($dados['nome_fornecedor'])) {
        $erros['nome_fornecedor'] = 'Nome do fornecedor é obrigatório.';
    } elseif (strlen($dados['nome_fornecedor']) < 3) {
        $erros['nome_fornecedor'] = 'Nome deve ter pelo menos 3 caracteres.';
    } elseif (strlen($dados['nome_fornecedor']) > 150) {
        $erros['nome_fornecedor'] = 'Nome deve ter no máximo 150 caracteres.';
    }

    if (empty($dados['endereco'])) {
        $erros['endereco'] = 'Endereço é obrigatório.';
    } elseif (strlen($dados['endereco']) > 255) {
        $erros['endereco'] = 'Endereço deve ter no máximo 255 caracteres.';
    }

    if (empty($dados['bairro'])) {
        $erros['bairro'] = 'Bairro é obrigatório.';
    } elseif (strlen($dados['bairro']) > 100) {
        $erros['bairro'] = 'Bairro deve ter no máximo 100 caracteres.';
    }

    $estados_validos = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS',
                        'MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC',
                        'SP','SE','TO'];
    if (empty($dados['estado'])) {
        $erros['estado'] = 'Estado é obrigatório.';
    } elseif (!in_array($dados['estado'], $estados_validos)) {
        $erros['estado'] = 'Selecione um estado válido.';
    }

    if (empty($dados['nome_bebida'])) {
        $erros['nome_bebida'] = 'Nome da bebida é obrigatório.';
    } elseif (strlen($dados['nome_bebida']) > 150) {
        $erros['nome_bebida'] = 'Nome da bebida deve ter no máximo 150 caracteres.';
    }

    if (empty($dados['distribuidora'])) {
        $erros['distribuidora'] = 'Distribuidora é obrigatória.';
    } elseif (strlen($dados['distribuidora']) > 150) {
        $erros['distribuidora'] = 'Distribuidora deve ter no máximo 150 caracteres.';
    }

    if ($dados['status_analise'] === '') {
        $erros['status_analise'] = 'Selecione o status da análise.';
    } elseif (!in_array($dados['status_analise'], ['0', '1'])) {
        $erros['status_analise'] = 'Status inválido.';
    }
    $status_bool = ($dados['status_analise'] === '1') ? 1 : 0;

    // ── Inserção no banco ──────────────────────────────────────────────────────
    if (empty($erros)) {
        $conn = conectarDB($db_host, $db_user, $db_pass, $db_name);

        if ($conn === null) {
            $mensagem = '⚠️ Não foi possível conectar ao banco de dados. Verifique as configurações.';
            $tipo_msg = 'aviso';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO fornecedores
                 (nome_fornecedor, endereco, bairro, estado, nome_bebida, distribuidora, status_analise)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                'ssssssi',
                $dados['nome_fornecedor'],
                $dados['endereco'],
                $dados['bairro'],
                $dados['estado'],
                $dados['nome_bebida'],
                $dados['distribuidora'],
                $status_bool
            );

            if ($stmt->execute()) {
                $label_status = $status_bool ? 'Aprovado' : 'Adulterado';
                $mensagem = '✅ Resultado cadastrado com sucesso! (ID: ' . $conn->insert_id . ' — Status: ' . $label_status . ')';
                $tipo_msg = 'sucesso';
                // Limpa campos após sucesso
                $dados = array_fill_keys(array_keys($dados), '');
            } else {
                $mensagem = '❌ Erro ao salvar no banco de dados: ' . $stmt->error;
                $tipo_msg = 'erro';
            }

            $stmt->close();
            $conn->close();
        }
    } else {
        $mensagem = '⚠️ Por favor, corrija os erros indicados abaixo.';
        $tipo_msg = 'aviso';
    }
}

// ─── Lista de estados brasileiros ─────────────────────────────────────────────
$estados = [
    'AC'=>'Acre','AL'=>'Alagoas','AP'=>'Amapá','AM'=>'Amazonas','BA'=>'Bahia',
    'CE'=>'Ceará','DF'=>'Distrito Federal','ES'=>'Espírito Santo','GO'=>'Goiás',
    'MA'=>'Maranhão','MT'=>'Mato Grosso','MS'=>'Mato Grosso do Sul',
    'MG'=>'Minas Gerais','PA'=>'Pará','PB'=>'Paraíba','PR'=>'Paraná',
    'PE'=>'Pernambuco','PI'=>'Piauí','RJ'=>'Rio de Janeiro',
    'RN'=>'Rio Grande do Norte','RS'=>'Rio Grande do Sul','RO'=>'Rondônia',
    'RR'=>'Roraima','SC'=>'Santa Catarina','SP'=>'São Paulo',
    'SE'=>'Sergipe','TO'=>'Tocantins'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Resultados</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700&family=Barlow:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Base ──────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Tema Dark (padrão) ─────────────────────────────────────────────── */
        :root {
            --amber:     #e8a020;
            --amber-d:   #c47a10;
            --bg:        #111418;
            --surface:   #1a1f26;
            --surface2:  #232930;
            --border:    #2e3540;
            --text:      #e8e2d6;
            --muted:     #7a8494;
            --error:     #e05555;
            --success:   #4caf78;
            --warn:      #e8a020;
            --radius:    6px;
            --radio-adulterado: #e05555;
            --radio-aprovado:   #4caf78;
        }

        /* ── Tema Light ─────────────────────────────────────────────────────── */
        [data-theme="light"] {
            --amber:     #c47a10;
            --amber-d:   #9e5e08;
            --bg:        #b6b7ba;
            --surface:   #dfdcdc;
            --surface2:  #bdbdbe;
            --border:    #d4dae3;
            --text:      #1a1f26;
            --muted:     #6b7685;
            --error:     #c0392b;
            --success:   #27ae60;
            --warn:      #c47a10;
            --radio-adulterado: #c0392b;
            --radio-aprovado:   #27ae60;
        }

        body {
            font-family: 'Barlow', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 16px 60px;
            transition: background .25s, color .25s;
        }

        /* ── Container ─────────────────────────────────────────────────────── */
        .wrapper {
            width: 100%;
            max-width: 680px;
        }

        /* ── Header ────────────────────────────────────────────────────────── */
        .header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 36px;
        }

        .header-icon {
            width: 52px;
            height: 52px;
            background: var(--amber);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .header-title {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1;
            color: var(--text);
        }

        .header-sub {
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
            letter-spacing: 0.02em;
        }

        /* ── Mensagem de feedback ───────────────────────────────────────────── */
        .mensagem {
            padding: 14px 18px;
            border-radius: var(--radius);
            margin-bottom: 28px;
            font-size: 14px;
            font-weight: 500;
            border-left: 4px solid;
        }

        .mensagem.sucesso { background: rgba(76,175,120,.12); border-color: var(--success); color: var(--success); }
        .mensagem.erro    { background: rgba(224,85,85,.12);  border-color: var(--error);   color: var(--error);   }
        .mensagem.aviso   { background: rgba(232,160,32,.12); border-color: var(--warn);    color: var(--warn);    }

        /* ── Card do formulário ─────────────────────────────────────────────── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .card-section {
            padding: 28px 32px;
            border-bottom: 1px solid var(--border);
        }

        .card-section:last-child { border-bottom: none; }

        .section-label {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--amber);
            margin-bottom: 20px;
        }

        /* ── Grid de campos ─────────────────────────────────────────────────── */
        .grid { display: grid; gap: 18px; }
        .grid-2 { grid-template-columns: 1fr 1fr; }
        .grid-3 { grid-template-columns: 2fr 1fr; }

        @media (max-width: 520px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .card-section { padding: 24px 20px; }
        }

        /* ── Campo ──────────────────────────────────────────────────────────── */
        .campo { display: flex; flex-direction: column; gap: 7px; }

        label {
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.04em;
            color: var(--muted);
            text-transform: uppercase;
        }

        input[type="text"],
        select {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            font-family: 'Barlow', sans-serif;
            font-size: 15px;
            padding: 11px 14px;
            width: 100%;
            transition: border-color .18s, box-shadow .18s;
            outline: none;
            -webkit-appearance: none;
        }

        input[type="text"]:focus,
        select:focus {
            border-color: var(--amber);
            box-shadow: 0 0 0 3px rgba(232,160,32,.15);
        }

        input[type="text"].invalido,
        select.invalido {
            border-color: var(--error);
            box-shadow: 0 0 0 3px rgba(224,85,85,.12);
        }

        select { cursor: pointer; }

        select option { background: var(--surface2); }

        .erro-campo {
            font-size: 12px;
            color: var(--error);
            margin-top: 2px;
        }

        /* ── Contador de caracteres ─────────────────────────────────────────── */
        .contador {
            font-size: 11px;
            color: var(--muted);
            text-align: right;
            margin-top: -4px;
        }

        /* ── Botões ─────────────────────────────────────────────────────────── */
        .botoes {
            display: flex;
            gap: 12px;
            padding: 24px 32px;
            background: var(--surface);
        }

        @media (max-width: 400px) {
            .botoes { flex-direction: column; }
        }

        .btn {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: none;
            border-radius: var(--radius);
            padding: 12px 28px;
            cursor: pointer;
            transition: background .15s, transform .1s, opacity .15s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:active { transform: scale(0.97); }

        .btn-primary {
            background: var(--amber);
            color: #111;
            flex: 1;
        }

        .btn-primary:hover { background: var(--amber-d); }

        .btn-secondary {
            background: var(--surface2);
            color: var(--muted);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover { background: var(--border); color: var(--text); }

        /* ── Toggle de Tema ─────────────────────────────────────────────────── */
        .theme-toggle {
            position: fixed;
            top: 16px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 6px 14px;
            cursor: pointer;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: var(--muted);
            transition: background .2s, color .2s, border-color .2s;
            user-select: none;
            z-index: 100;
        }

        .theme-toggle:hover { color: var(--text); border-color: var(--amber); }

        .theme-toggle .toggle-icon { font-size: 16px; transition: transform .3s; }

        /* ── Radio Buttons Customizados ─────────────────────────────────────── */
        .radio-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .radio-option {
            flex: 1;
            min-width: 140px;
            position: relative;
        }

        .radio-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 18px;
            background: var(--surface2);
            border: 2px solid var(--border);
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
            color: var(--muted);
            transition: border-color .18s, background .18s, color .18s;
            user-select: none;
        }

        .radio-label .radio-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: border-color .18s, background .18s;
        }

        .radio-label .radio-dot::after {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: transparent;
            transition: background .18s;
        }

        /* Estado: Adulterado selecionado */
        .radio-option input[value="0"]:checked + .radio-label {
            border-color: var(--radio-adulterado);
            background: rgba(224,85,85,.08);
            color: var(--radio-adulterado);
        }

        .radio-option input[value="0"]:checked + .radio-label .radio-dot {
            border-color: var(--radio-adulterado);
        }

        .radio-option input[value="0"]:checked + .radio-label .radio-dot::after {
            background: var(--radio-adulterado);
        }

        /* Estado: Aprovado selecionado */
        .radio-option input[value="1"]:checked + .radio-label {
            border-color: var(--radio-aprovado);
            background: rgba(76,175,120,.08);
            color: var(--radio-aprovado);
        }

        .radio-option input[value="1"]:checked + .radio-label .radio-dot {
            border-color: var(--radio-aprovado);
        }

        .radio-option input[value="1"]:checked + .radio-label .radio-dot::after {
            background: var(--radio-aprovado);
        }

        /* Hover nos radio não selecionados */
        .radio-option input[type="radio"]:not(:checked) + .radio-label:hover {
            border-color: var(--amber);
            color: var(--text);
        }

        .radio-invalido .radio-label {
            border-color: var(--error) !important;
        }
    </style>
</head>
<body>
<!-- Toggle de Tema -->
<button class="theme-toggle" onclick="alternarTema()" id="theme-btn" title="Alternar tema">
    <span class="toggle-icon" id="toggle-icon">☀️</span>
    <span id="toggle-label">LIGHT</span>
</button>

<div class="wrapper">

    <!-- Cabeçalho -->
    <div class="header">
        <div class="header-icon">🍺</div>
        <div>
            <div class="header-title">Cadastro de Resultados</div>
            <div class="header-sub">Registro de resultados encontrados</div>
        </div>
    </div>

    <!-- Mensagem de retorno -->
    <?php if ($mensagem): ?>
        <div class="mensagem <?= $tipo_msg ?>"><?= $mensagem ?></div>
    <?php endif; ?>

    <!-- Formulário -->
    <form method="POST" action="" id="form-fornecedor" novalidate>
        <input type="hidden" name="acao" value="salvar">

        <div class="card">

            <!-- Seção: Dados do Fornecedor -->
            <div class="card-section">
                <div class="section-label">Dados do Fornecedor</div>
                <div class="grid">
                    <div class="campo">
                        <label for="nome_fornecedor">Nome do Fornecedor *</label>
                        <input
                            type="text"
                            id="nome_fornecedor"
                            name="nome_fornecedor"
                            placeholder="Ex.: Distribuidora Central Ltda."
                            maxlength="150"
                            value="<?= htmlspecialchars($dados['nome_fornecedor']) ?>"
                            class="<?= isset($erros['nome_fornecedor']) ? 'invalido' : '' ?>"
                            oninput="contarChars(this,'c-nome',150)"
                        >
                        <span class="contador" id="c-nome"><?= strlen($dados['nome_fornecedor']) ?>/150</span>
                        <?php if (isset($erros['nome_fornecedor'])): ?>
                            <span class="erro-campo"><?= $erros['nome_fornecedor'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seção: Localização -->
            <div class="card-section">
                <div class="section-label">Localização</div>
                <div class="grid">
                    <div class="campo">
                        <label for="endereco">Endereço *</label>
                        <input
                            type="text"
                            id="endereco"
                            name="endereco"
                            placeholder="Rua, número, complemento"
                            maxlength="255"
                            value="<?= htmlspecialchars($dados['endereco']) ?>"
                            class="<?= isset($erros['endereco']) ? 'invalido' : '' ?>"
                            oninput="contarChars(this,'c-end',255)"
                        >
                        <span class="contador" id="c-end"><?= strlen($dados['endereco']) ?>/255</span>
                        <?php if (isset($erros['endereco'])): ?>
                            <span class="erro-campo"><?= $erros['endereco'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-3">
                        <div class="campo">
                            <label for="bairro">Bairro *</label>
                            <input
                                type="text"
                                id="bairro"
                                name="bairro"
                                placeholder="Ex.: Centro"
                                maxlength="100"
                                value="<?= htmlspecialchars($dados['bairro']) ?>"
                                class="<?= isset($erros['bairro']) ? 'invalido' : '' ?>"
                                oninput="contarChars(this,'c-bairro',100)"
                            >
                            <span class="contador" id="c-bairro"><?= strlen($dados['bairro']) ?>/100</span>
                            <?php if (isset($erros['bairro'])): ?>
                                <span class="erro-campo"><?= $erros['bairro'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="campo">
                            <label for="estado">Estado *</label>
                            <select
                                id="estado"
                                name="estado"
                                class="<?= isset($erros['estado']) ? 'invalido' : '' ?>"
                            >
                                <option value="">— UF —</option>
                                <?php foreach ($estados as $uf => $nome): ?>
                                    <option value="<?= $uf ?>" <?= ($dados['estado'] === $uf) ? 'selected' : '' ?>>
                                        <?= $uf ?> — <?= $nome ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($erros['estado'])): ?>
                                <span class="erro-campo"><?= $erros['estado'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção: Produto -->
            <div class="card-section">
                <div class="section-label">Produto</div>
                <div class="grid grid-2">
                    <div class="campo">
                        <label for="nome_bebida">Nome da Bebida *</label>
                        <input
                            type="text"
                            id="nome_bebida"
                            name="nome_bebida"
                            placeholder="Ex.: Cerveja Pilsen 600ml"
                            maxlength="150"
                            value="<?= htmlspecialchars($dados['nome_bebida']) ?>"
                            class="<?= isset($erros['nome_bebida']) ? 'invalido' : '' ?>"
                            oninput="contarChars(this,'c-beb',150)"
                        >
                        <span class="contador" id="c-beb"><?= strlen($dados['nome_bebida']) ?>/150</span>
                        <?php if (isset($erros['nome_bebida'])): ?>
                            <span class="erro-campo"><?= $erros['nome_bebida'] ?></span>
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
                            value="<?= htmlspecialchars($dados['distribuidora']) ?>"
                            class="<?= isset($erros['distribuidora']) ? 'invalido' : '' ?>"
                            oninput="contarChars(this,'c-dist',150)"
                        >
                        <span class="contador" id="c-dist"><?= strlen($dados['distribuidora']) ?>/150</span>
                        <?php if (isset($erros['distribuidora'])): ?>
                            <span class="erro-campo"><?= $erros['distribuidora'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seção: Status da Análise -->
            <div class="card-section">
                <div class="section-label">Status da Análise</div>
                <div class="radio-group <?= isset($erros['status_analise']) ? 'radio-invalido' : '' ?>">

                    <div class="radio-option">
                        <input
                            type="radio"
                            id="status_adulterado"
                            name="status_analise"
                            value="0"
                            <?= ($dados['status_analise'] === '0') ? 'checked' : '' ?>
                        >
                        <label class="radio-label" for="status_adulterado">
                            <span class="radio-dot"></span>
                            ⚠️ Adulterado
                        </label>
                    </div>

                    <div class="radio-option">
                        <input
                            type="radio"
                            id="status_aprovado"
                            name="status_analise"
                            value="1"
                            <?= ($dados['status_analise'] === '1') ? 'checked' : '' ?>
                        >
                        <label class="radio-label" for="status_aprovado">
                            <span class="radio-dot"></span>
                            ✅ Aprovado
                        </label>
                    </div>

                </div>
                <?php if (isset($erros['status_analise'])): ?>
                    <span class="erro-campo" style="margin-top:8px;display:block;"><?= $erros['status_analise'] ?></span>
                <?php endif; ?>
            </div>

            <!-- Botões -->
            <div class="botoes">
                <button type="submit" class="btn btn-primary">
                    💾 Salvar Resultado
                </button>
                <button type="button" class="btn btn-secondary" onclick="limparFormulario()">
                    🗑 Limpar
                </button>
            </div>

        </div><!-- /card -->
    </form>

</div><!-- /wrapper -->

<script>
    // Contador de caracteres em tempo real
    function contarChars(input, spanId, max) {
        document.getElementById(spanId).textContent = input.value.length + '/' + max;
    }

    // Limpa todos os campos do formulário
    function limparFormulario() {
        const form = document.getElementById('form-fornecedor');
        form.reset();
        // Zera contadores
        ['c-nome','c-end','c-bairro','c-beb','c-dist'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = el.textContent.replace(/^\d+/, '0');
        });
        // Remove classes de erro
        form.querySelectorAll('.invalido').forEach(el => el.classList.remove('invalido'));
        form.querySelectorAll('.radio-invalido').forEach(el => el.classList.remove('radio-invalido'));
    }

    // Alterna entre dark e light mode
    function alternarTema() {
        const html = document.documentElement;
        const isDark = html.getAttribute('data-theme') !== 'light';
        const icon  = document.getElementById('toggle-icon');
        const label = document.getElementById('toggle-label');

        if (isDark) {
            html.setAttribute('data-theme', 'light');
            icon.textContent  = '🌙';
            label.textContent = 'DARK';
            localStorage.setItem('tema', 'light');
        } else {
            html.removeAttribute('data-theme');
            icon.textContent  = '☀️';
            label.textContent = 'LIGHT';
            localStorage.setItem('tema', 'dark');
        }
    }

    // Inicializa contadores com valores já preenchidos (retorno do PHP)
    document.addEventListener('DOMContentLoaded', () => {
        const mapa = {
            'nome_fornecedor': ['c-nome', 150],
            'endereco':        ['c-end',  255],
            'bairro':          ['c-bairro',100],
            'nome_bebida':     ['c-beb',  150],
            'distribuidora':   ['c-dist', 150],
        };
        Object.entries(mapa).forEach(([name, [spanId, max]]) => {
            const el = document.querySelector(`[name="${name}"]`);
            if (el) contarChars(el, spanId, max);
        });

        // Restaura preferência de tema salva
        const temaSalvo = localStorage.getItem('tema');
        const icon  = document.getElementById('toggle-icon');
        const label = document.getElementById('toggle-label');
        if (temaSalvo === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
            icon.textContent  = '🌙';
            label.textContent = 'DARK';
        }
    });
</script>
</body>
</html>
