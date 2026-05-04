<?php
// ─── modelos/Fornecedor.php ───────────────────────────────────────────────────
// Responsabilidade: representar um fornecedor como objeto PHP.
// Esta classe NÃO acessa o banco — ela apenas carrega e expõe os dados.
// ─────────────────────────────────────────────────────────────────────────────

class Fornecedor
{
    // ── Identificação ─────────────────────────────────────────────────────────
    public int    $identificador   = 0;        // id gerado pelo banco (0 = ainda não salvo)

    // ── Dados do fornecedor ───────────────────────────────────────────────────
    public string $nomeFornecedor  = '';
    public string $endereco        = '';
    public string $bairro          = '';
    public string $estado          = '';       // sigla da UF, ex.: "SP"

    // ── Produto ───────────────────────────────────────────────────────────────
    public string $nomeBebida      = '';
    public string $distribuidora   = '';

    // ── Resultado da análise ──────────────────────────────────────────────────
    public int    $statusAnalise   = 0;        // 0 = adulterado | 1 = aprovado
    public string $criadoEm        = '';       // preenchido pelo banco ao inserir

    /**
     * Constrói o objeto a partir de um array associativo.
     * Útil para popular o modelo diretamente do $_POST sanitizado.
     *
     * Exemplo:
     *   $fornecedor = Fornecedor::doCampos($dadosSanitizados);
     */
    public static function doCampos(array $campos): self
    {
        $instancia = new self();

        $instancia->nomeFornecedor = $campos['nome_fornecedor'] ?? '';
        $instancia->endereco       = $campos['endereco']        ?? '';
        $instancia->bairro         = $campos['bairro']          ?? '';
        $instancia->estado         = $campos['estado']          ?? '';
        $instancia->nomeBebida     = $campos['nome_bebida']     ?? '';
        $instancia->distribuidora  = $campos['distribuidora']   ?? '';
        $instancia->statusAnalise  = (int)($campos['status_analise'] ?? 0);

        return $instancia;
    }

    /**
     * Retorna o rótulo legível do status.
     *
     * Exemplo:
     *   echo $fornecedor->rotuloDaAnalise(); // "Aprovado" ou "Adulterado"
     */
    public function rotuloDaAnalise(): string
    {
        return $this->statusAnalise === 1 ? 'Aprovado' : 'Adulterado';
    }

    /**
     * Converte o objeto de volta para array associativo.
     * Prático para repopular o formulário HTML após erros de validação.
     */
    public function paraCampos(): array
    {
        return [
            'nome_fornecedor' => $this->nomeFornecedor,
            'endereco'        => $this->endereco,
            'bairro'          => $this->bairro,
            'estado'          => $this->estado,
            'nome_bebida'     => $this->nomeBebida,
            'distribuidora'   => $this->distribuidora,
            'status_analise'  => (string)$this->statusAnalise,
        ];
    }
}
