<?php
// ═══════════════════════════════════════════════════════════════════════════════
//  Biblioteca.php — Núcleo DAO da aplicação
//
//  Este arquivo centraliza TODA a lógica da aplicação:
//    • Conexão com o banco de dados (padrão Singleton)
//    • Modelo de dados (classe Fornecedor)
//    • Repositório de acesso a dados (classe FornecedorDAO)
//    • Sanitização de entradas (classe Sanitizador)
//    • Validação de regras de negócio (classe ValidadorFornecedor)
//    • Controlador de formulário (classe ControladorFormulario)
//    • Constantes globais e dados auxiliares (lista de estados)
//
//  Como usar nas páginas:
//    require_once 'Biblioteca.php';
//
//  Nenhuma página precisa saber como a lógica funciona internamente.
//  Ela apenas instancia o ControladorFormulario e usa os resultados.
// ═══════════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/configuracao/banco.php';

// ───────────────────────────────────────────────────────────────────────────────
//  SEÇÃO 1 — CONEXÃO
//  Gerencia a conexão mysqli como Singleton: uma única instância por requisição.
// ───────────────────────────────────────────────────────────────────────────────

class Conexao
{
    private static ?mysqli $instancia = null;

    private function __construct() {}

    /** Retorna a conexão ativa; cria uma nova se ainda não existir. */
    public static function obter(): mysqli
    {
        if (self::$instancia === null) {
            $conexao = new mysqli(BD_SERVIDOR, BD_USUARIO, BD_SENHA, BD_NOME);

            if ($conexao->connect_error) {
                throw new RuntimeException(
                    'Falha na conexão com o banco: ' . $conexao->connect_error
                );
            }

            $conexao->set_charset(BD_CHARSET);
            self::$instancia = $conexao;
        }

        return self::$instancia;
    }

    /** Encerra e descarta a conexão armazenada. */
    public static function encerrar(): void
    {
        if (self::$instancia !== null) {
            self::$instancia->close();
            self::$instancia = null;
        }
    }
}


// ───────────────────────────────────────────────────────────────────────────────
//  SEÇÃO 2 — MODELO
//  Representa um fornecedor como objeto PHP puro, sem acesso ao banco.
// ───────────────────────────────────────────────────────────────────────────────

class Fornecedor
{
    public int    $identificador  = 0;
    public string $nomeFornecedor = '';
    public string $endereco       = '';
    public string $bairro         = '';
    public string $estado         = '';
    public string $nomeBebida     = '';
    public string $distribuidora  = '';
    public int    $statusAnalise  = 0;   // 0 = adulterado | 1 = aprovado
    public string $criadoEm       = '';

    /**
     * Popula o modelo a partir de um array associativo (ex.: campos do formulário).
     * Uso: $fornecedor = Fornecedor::doCampos($camposSanitizados);
     */
    public static function doCampos(array $campos): self
    {
        $obj = new self();
        $obj->nomeFornecedor = $campos['nome_fornecedor'] ?? '';
        $obj->endereco       = $campos['endereco']        ?? '';
        $obj->bairro         = $campos['bairro']          ?? '';
        $obj->estado         = $campos['estado']          ?? '';
        $obj->nomeBebida     = $campos['nome_bebida']     ?? '';
        $obj->distribuidora  = $campos['distribuidora']   ?? '';
        $obj->statusAnalise  = (int)($campos['status_analise'] ?? 0);
        return $obj;
    }

    /** Devolve o rótulo legível do status: "Aprovado" ou "Adulterado". */
    public function rotuloDaAnalise(): string
    {
        return $this->statusAnalise === 1 ? 'Aprovado' : 'Adulterado';
    }

    /**
     * Converte o objeto de volta para array associativo.
     * Usado para repopular os campos do formulário após erro de validação.
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


// ───────────────────────────────────────────────────────────────────────────────
//  SEÇÃO 3 — DAO (Data Access Object)
//  Toda e qualquer operação SQL fica aqui. Nenhuma outra classe escreve SQL.
// ───────────────────────────────────────────────────────────────────────────────

class FornecedorDAO
{
    private mysqli $conexao;

    public function __construct()
    {
        $this->conexao = Conexao::obter();
    }

    /**
     * Insere um fornecedor e retorna o ID gerado pelo banco.
     * Usa Prepared Statement para prevenir SQL Injection.
     */
    public function inserir(Fornecedor $fornecedor): int
    {
        $sql = "
            INSERT INTO fornecedores
                (nome_fornecedor, endereco, bairro, estado,
                 nome_bebida, distribuidora, status_analise)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->conexao->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException('Erro ao preparar consulta: ' . $this->conexao->error);
        }

        // 'ssssssi' = seis strings + um inteiro
        $stmt->bind_param(
            'ssssssi',
            $fornecedor->nomeFornecedor,
            $fornecedor->endereco,
            $fornecedor->bairro,
            $fornecedor->estado,
            $fornecedor->nomeBebida,
            $fornecedor->distribuidora,
            $fornecedor->statusAnalise
        );

        if (!$stmt->execute()) {
            throw new RuntimeException('Erro ao inserir registro: ' . $stmt->error);
        }

        $idGerado = (int) $this->conexao->insert_id;
        $stmt->close();

        return $idGerado;
    }

    /**
     * Retorna todos os fornecedores ordenados do mais recente para o mais antigo.
     *
     * @return Fornecedor[]
     */
    public function listarTodos(): array
    {
        $resultado = $this->conexao->query(
            "SELECT * FROM fornecedores ORDER BY criado_em DESC"
        );

        $lista = [];

        while ($linha = $resultado->fetch_assoc()) {
            $obj                = new Fornecedor();
            $obj->identificador = (int) $linha['id'];
            $obj->nomeFornecedor= $linha['nome_fornecedor'];
            $obj->endereco      = $linha['endereco'];
            $obj->bairro        = $linha['bairro'];
            $obj->estado        = $linha['estado'];
            $obj->nomeBebida    = $linha['nome_bebida'];
            $obj->distribuidora = $linha['distribuidora'];
            $obj->statusAnalise = (int) $linha['status_analise'];
            $obj->criadoEm      = $linha['criado_em'];
            $lista[]            = $obj;
        }

        return $lista;
    }

    /**
     * Busca um fornecedor pelo ID.
     * Retorna null se não encontrado.
     */
    public function buscarPorId(int $id): ?Fornecedor
    {
        $stmt = $this->conexao->prepare(
            "SELECT * FROM fornecedores WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $linha = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$linha) return null;

        $obj                = new Fornecedor();
        $obj->identificador = (int) $linha['id'];
        $obj->nomeFornecedor= $linha['nome_fornecedor'];
        $obj->endereco      = $linha['endereco'];
        $obj->bairro        = $linha['bairro'];
        $obj->estado        = $linha['estado'];
        $obj->nomeBebida    = $linha['nome_bebida'];
        $obj->distribuidora = $linha['distribuidora'];
        $obj->statusAnalise = (int) $linha['status_analise'];
        $obj->criadoEm      = $linha['criado_em'];

        return $obj;
    }
}


// ───────────────────────────────────────────────────────────────────────────────
//  SEÇÃO 4 — SANITIZADOR
//  Limpa os dados brutos do $_POST antes de qualquer validação.
// ───────────────────────────────────────────────────────────────────────────────

class Sanitizador
{
    /**
     * Recebe o array bruto (ex.: $_POST) e devolve os campos limpos.
     * Não valida regras de negócio — apenas normaliza os valores.
     */
    public static function limparFormulario(array $dadosBrutos): array
    {
        return [
            'nome_fornecedor' => self::texto($dadosBrutos['nome_fornecedor'] ?? ''),
            'endereco'        => self::texto($dadosBrutos['endereco']        ?? ''),
            'bairro'          => self::texto($dadosBrutos['bairro']          ?? ''),
            'estado'          => self::sigla($dadosBrutos['estado']          ?? ''),
            'nome_bebida'     => self::texto($dadosBrutos['nome_bebida']     ?? ''),
            'distribuidora'   => self::texto($dadosBrutos['distribuidora']   ?? ''),
            'status_analise'  => self::booleanoTexto($dadosBrutos['status_analise'] ?? ''),
        ];
    }

    // Remove espaços e escapa caracteres HTML (protege contra XSS)
    private static function texto(string $v): string
    {
        return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8'));
    }

    // Força maiúsculas para siglas de estado (ex.: "sp" → "SP")
    private static function sigla(string $v): string
    {
        return strtoupper(self::texto($v));
    }

    // Aceita apenas '0' ou '1'; qualquer outro valor vira string vazia
    private static function booleanoTexto(string $v): string
    {
        return in_array($v, ['0', '1'], true) ? $v : '';
    }
}


// ───────────────────────────────────────────────────────────────────────────────
//  SEÇÃO 5 — VALIDADOR
//  Aplica as regras de negócio. Retorna array de erros por campo.
// ───────────────────────────────────────────────────────────────────────────────

class ValidadorFornecedor
{
    private const ESTADOS_VALIDOS = [
        'AC','AL','AP','AM','BA','CE','DF','ES','GO',
        'MA','MT','MS','MG','PA','PB','PR','PE','PI',
        'RJ','RN','RS','RO','RR','SC','SP','SE','TO',
    ];

    /**
     * Valida todos os campos e retorna um array de erros.
     * Array vazio = nenhum erro encontrado, pode prosseguir para salvar.
     */
    public static function validar(array $campos): array
    {
        return array_merge(
            self::campo($campos['nome_fornecedor'] ?? '', 'nome_fornecedor', 'Nome do fornecedor', 3,  150),
            self::campo($campos['endereco']        ?? '', 'endereco',        'Endereço',           1,  255),
            self::campo($campos['bairro']          ?? '', 'bairro',          'Bairro',             1,  100),
            self::validarEstado($campos['estado']  ?? ''),
            self::campo($campos['nome_bebida']     ?? '', 'nome_bebida',     'Nome da bebida',     1,  150),
            self::campo($campos['distribuidora']   ?? '', 'distribuidora',   'Distribuidora',      1,  150),
            self::validarStatus($campos['status_analise'] ?? '')
        );
    }

    // Valida um campo de texto genérico (obrigatório + tamanho mínimo e máximo)
    private static function campo(
        string $valor,
        string $chave,
        string $rotulo,
        int    $minimo,
        int    $maximo
    ): array {
        if ($valor === '') {
            return [$chave => "{$rotulo} é obrigatório(a)."];
        }
        if (mb_strlen($valor) < $minimo) {
            return [$chave => "{$rotulo} deve ter pelo menos {$minimo} caracteres."];
        }
        if (mb_strlen($valor) > $maximo) {
            return [$chave => "{$rotulo} deve ter no máximo {$maximo} caracteres."];
        }
        return [];
    }

    private static function validarEstado(string $valor): array
    {
        if ($valor === '') return ['estado' => 'Estado é obrigatório.'];
        if (!in_array($valor, self::ESTADOS_VALIDOS, true)) {
            return ['estado' => 'Selecione um estado válido.'];
        }
        return [];
    }

    private static function validarStatus(string $valor): array
    {
        if ($valor === '') return ['status_analise' => 'Selecione o status da análise.'];
        if (!in_array($valor, ['0', '1'], true)) {
            return ['status_analise' => 'Status inválido.'];
        }
        return [];
    }
}


// ───────────────────────────────────────────────────────────────────────────────
//  SEÇÃO 6 — CONTROLADOR DE FORMULÁRIO
//  Orquestra sanitização → validação → persistência.
//  É o único ponto de contato entre as páginas e a lógica interna.
// ───────────────────────────────────────────────────────────────────────────────

class ControladorFormulario
{
    // Estado público — acessado pelas páginas após processar()
    public string $notificacao  = '';
    public string $tipoNotific  = '';
    public array  $camposForm   = [
        'nome_fornecedor' => '',
        'endereco'        => '',
        'bairro'          => '',
        'estado'          => '',
        'nome_bebida'     => '',
        'distribuidora'   => '',
        'status_analise'  => '',
    ];
    public array  $errosForm    = [];

    /**
     * Processa a requisição atual.
     * Se for POST com acao=salvar: sanitiza, valida e persiste.
     * Se for GET: não faz nada (exibe o formulário vazio).
     *
     * Chamada obrigatória no topo de cada página que usa o formulário.
     * Exemplo: $controlador = new ControladorFormulario(); $controlador->processar();
     */
    public function processar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['acao'] ?? '') !== 'salvar') {
            return; // Requisição GET — nada a processar
        }

        // Passo 1: limpa os dados brutos
        $this->camposForm = Sanitizador::limparFormulario($_POST);

        // Passo 2: valida as regras de negócio
        $this->errosForm = ValidadorFornecedor::validar($this->camposForm);

        if (!empty($this->errosForm)) {
            $this->notificacao = '⚠️ Por favor, corrija os erros indicados abaixo.';
            $this->tipoNotific = 'aviso';
            return;
        }

        // Passo 3: persiste no banco
        try {
            $fornecedor  = Fornecedor::doCampos($this->camposForm);
            $dao         = new FornecedorDAO();
            $idGerado    = $dao->inserir($fornecedor);
            $rotulo      = $fornecedor->rotuloDaAnalise();

            $this->notificacao = "✅ Resultado cadastrado com sucesso! (ID: {$idGerado} — Status: {$rotulo})";
            $this->tipoNotific = 'sucesso';
            $this->camposForm  = array_fill_keys(array_keys($this->camposForm), '');

        } catch (RuntimeException $excecao) {
            $this->notificacao = '❌ ' . $excecao->getMessage();
            $this->tipoNotific = 'erro';
        } finally {
            Conexao::encerrar();
        }
    }
}


// ───────────────────────────────────────────────────────────────────────────────
//  SEÇÃO 7 — DADOS AUXILIARES GLOBAIS
//  Disponíveis em qualquer página que inclua Biblioteca.php
// ───────────────────────────────────────────────────────────────────────────────

/** Lista completa de estados brasileiros para uso no <select> de UF */
$ESTADOS_BRASIL = [
    'AC'=>'Acre',           'AL'=>'Alagoas',           'AP'=>'Amapá',
    'AM'=>'Amazonas',       'BA'=>'Bahia',              'CE'=>'Ceará',
    'DF'=>'Distrito Federal','ES'=>'Espírito Santo',    'GO'=>'Goiás',
    'MA'=>'Maranhão',       'MT'=>'Mato Grosso',        'MS'=>'Mato Grosso do Sul',
    'MG'=>'Minas Gerais',   'PA'=>'Pará',               'PB'=>'Paraíba',
    'PR'=>'Paraná',         'PE'=>'Pernambuco',         'PI'=>'Piauí',
    'RJ'=>'Rio de Janeiro', 'RN'=>'Rio Grande do Norte','RS'=>'Rio Grande do Sul',
    'RO'=>'Rondônia',       'RR'=>'Roraima',            'SC'=>'Santa Catarina',
    'SP'=>'São Paulo',      'SE'=>'Sergipe',             'TO'=>'Tocantins',
];
