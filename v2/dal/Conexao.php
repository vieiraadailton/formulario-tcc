<?php
// ─── dal/Conexao.php ──────────────────────────────────────────────────────────
// Responsabilidade: abrir e fechar a conexão com o MySQL.
// Nenhuma outra camada deve criar conexões diretamente.
// ─────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../configuracao/banco.php';

class Conexao
{
    // Objeto mysqli compartilhado (padrão Singleton simples)
    private static ?mysqli $instancia = null;

    // Impede instanciação externa — use Conexao::obter()
    private function __construct() {}

    /**
     * Retorna a conexão ativa com o banco.
     * Cria uma nova conexão na primeira chamada; reutiliza nas seguintes.
     *
     * @throws RuntimeException se a conexão falhar
     */
    public static function obter(): mysqli
    {
        if (self::$instancia === null) {
            $conexao = new mysqli(BD_SERVIDOR, BD_USUARIO, BD_SENHA, BD_NOME);

            if ($conexao->connect_error) {
                throw new RuntimeException(
                    'Falha ao conectar ao banco de dados: ' . $conexao->connect_error
                );
            }

            $conexao->set_charset(BD_CHARSET);
            self::$instancia = $conexao;
        }

        return self::$instancia;
    }

    /**
     * Encerra a conexão e limpa a instância armazenada.
     * Chame ao final do ciclo de vida da requisição, se necessário.
     */
    public static function encerrar(): void
    {
        if (self::$instancia !== null) {
            self::$instancia->close();
            self::$instancia = null;
        }
    }
}
