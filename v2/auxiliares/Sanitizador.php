<?php
// ─── auxiliares/Sanitizador.php ───────────────────────────────────────────────
// Responsabilidade: limpar e normalizar os dados brutos do $_POST.
// Não valida regras de negócio — apenas prepara os dados para a validação.
// ─────────────────────────────────────────────────────────────────────────────

class Sanitizador
{
    /**
     * Recebe o array $_POST e devolve os campos limpos.
     * Todos os métodos são estáticos — não é necessário instanciar a classe.
     *
     * @param  array $dadosBrutos  Normalmente $_POST
     * @return array               Campos sanitizados, prontos para validação
     */
    public static function limparFormulario(array $dadosBrutos): array
    {
        return [
            'nome_fornecedor' => self::texto($dadosBrutos['nome_fornecedor'] ?? ''),
            'endereco'        => self::texto($dadosBrutos['endereco']        ?? ''),
            'bairro'          => self::texto($dadosBrutos['bairro']          ?? ''),
            'estado'          => self::siglaEstado($dadosBrutos['estado']    ?? ''),
            'nome_bebida'     => self::texto($dadosBrutos['nome_bebida']     ?? ''),
            'distribuidora'   => self::texto($dadosBrutos['distribuidora']   ?? ''),
            'status_analise'  => self::statusBooleano($dadosBrutos['status_analise'] ?? ''),
        ];
    }

    // ── Métodos privados de sanitização ───────────────────────────────────────

    /**
     * Remove espaços extras e converte caracteres especiais HTML.
     * Protege contra XSS (Cross-Site Scripting).
     */
    private static function texto(string $valor): string
    {
        return trim(htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Normaliza a sigla do estado para maiúsculas (ex.: "sp" → "SP").
     */
    private static function siglaEstado(string $valor): string
    {
        return strtoupper(self::texto($valor));
    }

    /**
     * Mantém apenas os valores esperados ('0' ou '1') para o status booleano.
     * Qualquer outro valor é retornado como string vazia.
     */
    private static function statusBooleano(string $valor): string
    {
        return in_array($valor, ['0', '1'], true) ? $valor : '';
    }
}
