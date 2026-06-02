// ═══════════════════════════════════════════════════════════════════════════
//  publico/js/formulario.js
//  Comportamentos do formulário de cadastro de resultados.
//  Funções: contarCaracteres · limparFormulario · alternarTema · init
// ═══════════════════════════════════════════════════════════════════════════

// ── Contador de caracteres em tempo real ─────────────────────────────────────
function contarCaracteres(campoEntrada, idContador, maximo) {
    document.getElementById(idContador).textContent =
        campoEntrada.value.length + '/' + maximo;
}

// ── Limpa o formulário e remove todos os estados de erro ─────────────────────
function limparFormulario() {
    const formulario = document.getElementById('formulario-fornecedor');
    formulario.reset();

    // Zera os contadores de caracteres
    const mapaContadores = {
        'cont-nome': 150, 'cont-endereco': 255, 'cont-bairro': 100,
        'cont-bebida': 150, 'cont-distribuidora': 150
    };

    Object.entries(mapaContadores).forEach(([idContador, maximo]) => {
        const elemento = document.getElementById(idContador);
        if (elemento) elemento.textContent = '0/' + maximo;
    });

    // Remove bordas de erro dos inputs e selects
    formulario.querySelectorAll('.invalido')
        .forEach(el => el.classList.remove('invalido'));

    // Remove destaque de erro do grupo de radio
    formulario.querySelectorAll('.grupo-radio-invalido')
        .forEach(el => el.classList.remove('grupo-radio-invalido'));
}

// ── Alterna entre tema escuro e claro ────────────────────────────────────────
function alternarTema() {
    const raizHtml   = document.documentElement;
    const estaEscuro = raizHtml.getAttribute('data-tema') !== 'claro';
    const icone      = document.getElementById('icone-tema');
    const rotulo     = document.getElementById('rotulo-tema');

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

// ── Inicialização ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // 1. Restaura o tema escolhido na sessão anterior
    const temaArmazenado = localStorage.getItem('tema-preferido');
    const icone          = document.getElementById('icone-tema');
    const rotulo         = document.getElementById('rotulo-tema');

    if (temaArmazenado === 'claro') {
        document.documentElement.setAttribute('data-tema', 'claro');
        if (icone)  icone.textContent  = '🌙';
        if (rotulo) rotulo.textContent = 'ESCURO';
    }

    // 2. Atualiza contadores com os comprimentos atuais
    //    (necessário quando PHP repopula os campos após erro de validação)
    const mapaContadores = {
        'nome_fornecedor': ['cont-nome',          150],
        'endereco':        ['cont-endereco',       255],
        'bairro':          ['cont-bairro',         100],
        'nome_bebida':     ['cont-bebida',         150],
        'distribuidora':   ['cont-distribuidora',  150],
    };

    Object.entries(mapaContadores).forEach(([nomeCampo, [idContador, maximo]]) => {
        const campo = document.querySelector(`[name="${nomeCampo}"]`);
        if (campo) contarCaracteres(campo, idContador, maximo);
    });
});
