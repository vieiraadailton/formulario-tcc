<?php
// ─── index.php ────────────────────────────────────────────────────────────────
// Página de cadastro de resultados.
// Toda a lógica vive em Biblioteca.php — este arquivo só monta a interface.
// ─────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/Biblioteca.php';

// Instancia o controlador e processa a requisição atual (GET ou POST)
$controlador = new ControladorFormulario();
$controlador->processar();
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

<!-- Alternador de tema (escuro / claro) -->
<button class="alternador-tema" onclick="alternarTema()" title="Alternar tema">
    <span class="icone-tema" id="icone-tema">☀️</span>
    <span id="rotulo-tema">CLARO</span>
</button>

<div class="container">

    <!-- Cabeçalho -->
    <div class="cabecalho">
        <div class="cabecalho-icone">🍺</div>
        <div>
            <div class="cabecalho-titulo">Cadastro de Resultados</div>
            <div class="cabecalho-subtitulo">Registro de resultados encontrados</div>
        </div>
    </div>

    <!-- Notificação de retorno (sucesso / aviso / erro) -->
    <?php if ($controlador->notificacao !== ''): ?>
        <div class="notificacao <?= $controlador->tipoNotific ?>">
            <?= $controlador->notificacao ?>
        </div>
    <?php endif; ?>

    <!-- Formulário -->
    <form method="POST" action="" id="formulario-fornecedor" novalidate>
        <input type="hidden" name="acao" value="salvar">

        <div class="cartao">

            <!-- Seção 1: Dados do Fornecedor -->
            <div class="cartao-secao">
                <div class="secao-rotulo">Dados do Fornecedor</div>
                <div class="grade">
                    <div class="campo">
                        <label for="nome_fornecedor">Nome do Fornecedor *</label>
                        <input type="text" id="nome_fornecedor" name="nome_fornecedor"
                            placeholder="Ex.: Distribuidora Central Ltda."
                            maxlength="150"
                            value="<?= htmlspecialchars($controlador->camposForm['nome_fornecedor']) ?>"
                            class="<?= isset($controlador->errosForm['nome_fornecedor']) ? 'invalido' : '' ?>"
                            oninput="contarCaracteres(this,'cont-nome',150)">
                        <span class="contador" id="cont-nome">
                            <?= mb_strlen($controlador->camposForm['nome_fornecedor']) ?>/150
                        </span>
                        <?php if (isset($controlador->errosForm['nome_fornecedor'])): ?>
                            <span class="erro-campo"><?= $controlador->errosForm['nome_fornecedor'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seção 2: Localização -->
            <div class="cartao-secao">
                <div class="secao-rotulo">Localização</div>
                <div class="grade">
                    <div class="campo">
                        <label for="endereco">Endereço *</label>
                        <input type="text" id="endereco" name="endereco"
                            placeholder="Rua, número, complemento"
                            maxlength="255"
                            value="<?= htmlspecialchars($controlador->camposForm['endereco']) ?>"
                            class="<?= isset($controlador->errosForm['endereco']) ? 'invalido' : '' ?>"
                            oninput="contarCaracteres(this,'cont-endereco',255)">
                        <span class="contador" id="cont-endereco">
                            <?= mb_strlen($controlador->camposForm['endereco']) ?>/255
                        </span>
                        <?php if (isset($controlador->errosForm['endereco'])): ?>
                            <span class="erro-campo"><?= $controlador->errosForm['endereco'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="grade grade-3-colunas">
                        <div class="campo">
                            <label for="bairro">Bairro *</label>
                            <input type="text" id="bairro" name="bairro"
                                placeholder="Ex.: Centro"
                                maxlength="100"
                                value="<?= htmlspecialchars($controlador->camposForm['bairro']) ?>"
                                class="<?= isset($controlador->errosForm['bairro']) ? 'invalido' : '' ?>"
                                oninput="contarCaracteres(this,'cont-bairro',100)">
                            <span class="contador" id="cont-bairro">
                                <?= mb_strlen($controlador->camposForm['bairro']) ?>/100
                            </span>
                            <?php if (isset($controlador->errosForm['bairro'])): ?>
                                <span class="erro-campo"><?= $controlador->errosForm['bairro'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="campo">
                            <label for="estado">Estado *</label>
                            <select id="estado" name="estado"
                                class="<?= isset($controlador->errosForm['estado']) ? 'invalido' : '' ?>">
                                <option value="">— UF —</option>
                                <?php foreach ($ESTADOS_BRASIL as $sigla => $nomeEstado): ?>
                                    <option value="<?= $sigla ?>"
                                        <?= ($controlador->camposForm['estado'] === $sigla) ? 'selected' : '' ?>>
                                        <?= $sigla ?> — <?= $nomeEstado ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($controlador->errosForm['estado'])): ?>
                                <span class="erro-campo"><?= $controlador->errosForm['estado'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção 3: Produto -->
            <div class="cartao-secao">
                <div class="secao-rotulo">Produto</div>
                <div class="grade grade-2-colunas">
                    <div class="campo">
                        <label for="nome_bebida">Nome da Bebida *</label>
                        <input type="text" id="nome_bebida" name="nome_bebida"
                            placeholder="Ex.: Cerveja Pilsen 600ml"
                            maxlength="150"
                            value="<?= htmlspecialchars($controlador->camposForm['nome_bebida']) ?>"
                            class="<?= isset($controlador->errosForm['nome_bebida']) ? 'invalido' : '' ?>"
                            oninput="contarCaracteres(this,'cont-bebida',150)">
                        <span class="contador" id="cont-bebida">
                            <?= mb_strlen($controlador->camposForm['nome_bebida']) ?>/150
                        </span>
                        <?php if (isset($controlador->errosForm['nome_bebida'])): ?>
                            <span class="erro-campo"><?= $controlador->errosForm['nome_bebida'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="campo">
                        <label for="distribuidora">Distribuidora *</label>
                        <input type="text" id="distribuidora" name="distribuidora"
                            placeholder="Ex.: Ambev, Heineken…"
                            maxlength="150"
                            value="<?= htmlspecialchars($controlador->camposForm['distribuidora']) ?>"
                            class="<?= isset($controlador->errosForm['distribuidora']) ? 'invalido' : '' ?>"
                            oninput="contarCaracteres(this,'cont-distribuidora',150)">
                        <span class="contador" id="cont-distribuidora">
                            <?= mb_strlen($controlador->camposForm['distribuidora']) ?>/150
                        </span>
                        <?php if (isset($controlador->errosForm['distribuidora'])): ?>
                            <span class="erro-campo"><?= $controlador->errosForm['distribuidora'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seção 4: Status da Análise -->
            <div class="cartao-secao">
                <div class="secao-rotulo">Status da Análise</div>
                <div class="grupo-radio <?= isset($controlador->errosForm['status_analise']) ? 'grupo-radio-invalido' : '' ?>">
                    <div class="opcao-radio">
                        <input type="radio" id="status-adulterado" name="status_analise" value="0"
                            <?= ($controlador->camposForm['status_analise'] === '0') ? 'checked' : '' ?>>
                        <label class="rotulo-radio" for="status-adulterado">
                            <span class="indicador-radio"></span>⚠️ Adulterado
                        </label>
                    </div>
                    <div class="opcao-radio">
                        <input type="radio" id="status-aprovado" name="status_analise" value="1"
                            <?= ($controlador->camposForm['status_analise'] === '1') ? 'checked' : '' ?>>
                        <label class="rotulo-radio" for="status-aprovado">
                            <span class="indicador-radio"></span>✅ Aprovado
                        </label>
                    </div>
                </div>
                <?php if (isset($controlador->errosForm['status_analise'])): ?>
                    <span class="erro-campo" style="margin-top:8px;display:block;">
                        <?= $controlador->errosForm['status_analise'] ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Botões -->
            <div class="area-botoes">
                <button type="submit" class="botao botao-principal">💾 Salvar Resultado</button>
                <button type="button" class="botao botao-secundario" onclick="limparFormulario()">🗑 Limpar</button>
            </div>

        </div><!-- /cartao -->
    </form>

</div><!-- /container -->

<script src="publico/js/formulario.js"></script>
</body>
</html>
