<?php
// ─── validadores/ValidadorFornecedor.php ──────────────────────────────────────
// Responsabilidade: aplicar as regras de negócio a cada campo do formulário.
// Recebe os dados já sanitizados e devolve um array de erros (vazio = tudo ok).
// ─────────────────────────────────────────────────────────────────────────────

class ValidadorFornecedor
{
    // Lista oficial de siglas de estados brasileiros
    private const ESTADOS_VALIDOS = [
        'AC','AL','AP','AM','BA','CE','DF','ES','GO',
        'MA','MT','MS','MG','PA','PB','PR','PE','PI',
        'RJ','RN','RS','RO','RR','SC','SP','SE','TO',
    ];

    /**
     * Valida todos os campos do formulário de uma vez.
     *
     * @param  array $campos  Dados já sanitizados pelo Sanitizador
     * @return array          Array associativo de erros por campo.
     *                        Vazio = nenhum erro encontrado.
     *
     * Exemplo de retorno com erros:
     *   [
     *     'nome_fornecedor' => 'Nome do fornecedor é obrigatório.',
     *     'estado'          => 'Selecione um estado válido.',
     *   ]
     */
    public static function validar(array $campos): array
    {
        $erros = [];

        $erros = array_merge($erros, self::validarNomeFornecedor($campos['nome_fornecedor'] ?? ''));
        $erros = array_merge($erros, self::validarEndereco($campos['endereco']               ?? ''));
        $erros = array_merge($erros, self::validarBairro($campos['bairro']                   ?? ''));
        $erros = array_merge($erros, self::validarEstado($campos['estado']                   ?? ''));
        $erros = array_merge($erros, self::validarNomeBebida($campos['nome_bebida']          ?? ''));
        $erros = array_merge($erros, self::validarDistribuidora($campos['distribuidora']     ?? ''));
        $erros = array_merge($erros, self::validarStatusAnalise($campos['status_analise']    ?? ''));

        return $erros;
    }

    // ── Regras por campo ──────────────────────────────────────────────────────

    private static function validarNomeFornecedor(string $valor): array
    {
        if ($valor === '') {
            return ['nome_fornecedor' => 'Nome do fornecedor é obrigatório.'];
        }
        if (mb_strlen($valor) < 3) {
            return ['nome_fornecedor' => 'Nome deve ter pelo menos 3 caracteres.'];
        }
        if (mb_strlen($valor) > 150) {
            return ['nome_fornecedor' => 'Nome deve ter no máximo 150 caracteres.'];
        }
        return [];
    }

    private static function validarEndereco(string $valor): array
    {
        if ($valor === '') {
            return ['endereco' => 'Endereço é obrigatório.'];
        }
        if (mb_strlen($valor) > 255) {
            return ['endereco' => 'Endereço deve ter no máximo 255 caracteres.'];
        }
        return [];
    }

    private static function validarBairro(string $valor): array
    {
        if ($valor === '') {
            return ['bairro' => 'Bairro é obrigatório.'];
        }
        if (mb_strlen($valor) > 100) {
            return ['bairro' => 'Bairro deve ter no máximo 100 caracteres.'];
        }
        return [];
    }

    private static function validarEstado(string $valor): array
    {
        if ($valor === '') {
            return ['estado' => 'Estado é obrigatório.'];
        }
        if (!in_array($valor, self::ESTADOS_VALIDOS, true)) {
            return ['estado' => 'Selecione um estado válido.'];
        }
        return [];
    }

    private static function validarNomeBebida(string $valor): array
    {
        if ($valor === '') {
            return ['nome_bebida' => 'Nome da bebida é obrigatório.'];
        }
        if (mb_strlen($valor) > 150) {
            return ['nome_bebida' => 'Nome da bebida deve ter no máximo 150 caracteres.'];
        }
        return [];
    }

    private static function validarDistribuidora(string $valor): array
    {
        if ($valor === '') {
            return ['distribuidora' => 'Distribuidora é obrigatória.'];
        }
        if (mb_strlen($valor) > 150) {
            return ['distribuidora' => 'Distribuidora deve ter no máximo 150 caracteres.'];
        }
        return [];
    }

    private static function validarStatusAnalise(string $valor): array
    {
        if ($valor === '') {
            return ['status_analise' => 'Selecione o status da análise.'];
        }
        if (!in_array($valor, ['0', '1'], true)) {
            return ['status_analise' => 'Status de análise inválido.'];
        }
        return [];
    }
}
