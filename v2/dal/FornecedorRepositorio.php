<?php
// ─── dal/FornecedorRepositorio.php ───────────────────────────────────────────
// Responsabilidade: todas as operações SQL da tabela `fornecedores`.
// Camadas superiores nunca escrevem SQL — usam os métodos desta classe.
// ─────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../modelos/Fornecedor.php';

class FornecedorRepositorio
{
    private mysqli $conexao;

    public function __construct()
    {
        // Obtém a conexão já aberta pela classe Conexao (Singleton)
        $this->conexao = Conexao::obter();
    }

    // ── Inserção ──────────────────────────────────────────────────────────────

    /**
     * Insere um fornecedor no banco e retorna o ID gerado.
     * Usa Prepared Statement para prevenir SQL Injection.
     *
     * @param  Fornecedor $fornecedor  Objeto populado pelo modelo
     * @return int                     ID da linha inserida
     * @throws RuntimeException        Se a execução falhar
     */
    public function inserir(Fornecedor $fornecedor): int
    {
        $consulta = "
            INSERT INTO fornecedores
                (nome_fornecedor, endereco, bairro, estado,
                 nome_bebida, distribuidora, status_analise)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $declaracao = $this->conexao->prepare($consulta);

        if (!$declaracao) {
            throw new RuntimeException('Erro ao preparar consulta: ' . $this->conexao->error);
        }

        // 'ssssssi' → 6 strings + 1 inteiro
        $declaracao->bind_param(
            'ssssssi',
            $fornecedor->nomeFornecedor,
            $fornecedor->endereco,
            $fornecedor->bairro,
            $fornecedor->estado,
            $fornecedor->nomeBebida,
            $fornecedor->distribuidora,
            $fornecedor->statusAnalise
        );

        if (!$declaracao->execute()) {
            throw new RuntimeException('Erro ao executar inserção: ' . $declaracao->error);
        }

        $idGerado = (int)$this->conexao->insert_id;
        $declaracao->close();

        return $idGerado;
    }

    // ── Consultas (prontas para expansão futura) ───────────────────────────────

    /**
     * Retorna todos os fornecedores ordenados do mais recente.
     *
     * @return Fornecedor[]
     */
    public function listarTodos(): array
    {
        $consulta   = "SELECT * FROM fornecedores ORDER BY criado_em DESC";
        $resultado  = $this->conexao->query($consulta);
        $lista      = [];

        while ($linha = $resultado->fetch_assoc()) {
            $fornecedor                = new Fornecedor();
            $fornecedor->identificador = (int)$linha['id'];
            $fornecedor->nomeFornecedor= $linha['nome_fornecedor'];
            $fornecedor->endereco      = $linha['endereco'];
            $fornecedor->bairro        = $linha['bairro'];
            $fornecedor->estado        = $linha['estado'];
            $fornecedor->nomeBebida    = $linha['nome_bebida'];
            $fornecedor->distribuidora = $linha['distribuidora'];
            $fornecedor->statusAnalise = (int)$linha['status_analise'];
            $fornecedor->criadoEm      = $linha['criado_em'];
            $lista[]                   = $fornecedor;
        }

        return $lista;
    }

    /**
     * Busca um fornecedor pelo ID.
     *
     * @param  int              $identificador
     * @return Fornecedor|null  Null se não encontrado
     */
    public function buscarPorId(int $identificador): ?Fornecedor
    {
        $declaracao = $this->conexao->prepare(
            "SELECT * FROM fornecedores WHERE id = ? LIMIT 1"
        );
        $declaracao->bind_param('i', $identificador);
        $declaracao->execute();

        $resultado = $declaracao->get_result();
        $linha     = $resultado->fetch_assoc();
        $declaracao->close();

        if (!$linha) {
            return null;
        }

        $fornecedor                = new Fornecedor();
        $fornecedor->identificador = (int)$linha['id'];
        $fornecedor->nomeFornecedor= $linha['nome_fornecedor'];
        $fornecedor->endereco      = $linha['endereco'];
        $fornecedor->bairro        = $linha['bairro'];
        $fornecedor->estado        = $linha['estado'];
        $fornecedor->nomeBebida    = $linha['nome_bebida'];
        $fornecedor->distribuidora = $linha['distribuidora'];
        $fornecedor->statusAnalise = (int)$linha['status_analise'];
        $fornecedor->criadoEm      = $linha['criado_em'];

        return $fornecedor;
    }
}
