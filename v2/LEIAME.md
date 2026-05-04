# Cadastro de Resultados — Arquitetura DAL

## Estrutura de Pastas

```
projeto/
│
├── index.php                          ← Ponto de entrada (orquestrador)
│
├── configuracao/
│   ├── banco.php                      ← Credenciais do MySQL (constants)
│   └── esquema.sql                    ← DDL: criação do banco e da tabela
│
├── dal/                               ← Data Access Layer
│   ├── Conexao.php                    ← Singleton de conexão mysqli
│   └── FornecedorRepositorio.php      ← Queries SQL (INSERT, SELECT…)
│
├── modelos/
│   └── Fornecedor.php                 ← Classe de dados (sem lógica de banco)
│
├── validadores/
│   └── ValidadorFornecedor.php        ← Regras de negócio por campo
│
├── auxiliares/
│   └── Sanitizador.php                ← Limpeza dos dados brutos do $_POST
│
└── publico/
    └── css/
        └── estilos.css                ← CSS da interface (dark/light mode)
```

## Responsabilidade de Cada Camada

| Camada | Arquivo(s) | Faz | Não faz |
|---|---|---|---|
| **Apresentação** | `index.php` | Orquestra o fluxo, renderiza HTML | Escreve SQL, valida campos |
| **Validação** | `ValidadorFornecedor.php` | Regras de negócio | Acessa banco, limpa dados |
| **Auxiliares** | `Sanitizador.php` | `trim`, `htmlspecialchars`, normalização | Valida regras, acessa banco |
| **Modelo** | `Fornecedor.php` | Representa o dado como objeto | Acessa banco, valida |
| **DAL** | `Conexao.php`, `FornecedorRepositorio.php` | Abre conexão, executa SQL | Valida, renderiza HTML |
| **Configuração** | `banco.php`, `esquema.sql` | Credenciais e DDL | Nada além disso |

## Como Executar

### 1. Criar o banco de dados

```bash
mysql -u root -p < configuracao/esquema.sql
```

Ou copie o conteúdo de `configuracao/esquema.sql` e execute no phpMyAdmin.

### 2. Configurar as credenciais

Edite `configuracao/banco.php`:

```php
define('BD_SERVIDOR', 'localhost');
define('BD_USUARIO',  'root');
define('BD_SENHA',    'sua_senha');
define('BD_NOME',     'cadastro_bebidas');
```

### 3. Colocar na pasta do servidor

- **XAMPP:** `C:/xampp/htdocs/projeto/`
- **WAMP:** `C:/wamp64/www/projeto/`

### 4. Acessar no navegador

```
http://localhost/projeto/
```

## Fluxo de uma Requisição POST

```
Usuário clica "Salvar"
    │
    ▼
index.php recebe $_POST
    │
    ├─► Sanitizador::limparFormulario()   → dados limpos
    │
    ├─► ValidadorFornecedor::validar()    → erros? sim → volta ao formulário
    │                                               não → continua
    ├─► Fornecedor::doCampos()            → objeto modelo
    │
    ├─► FornecedorRepositorio::inserir()  → executa INSERT
    │       └─► Conexao::obter()          → conexão mysqli (Singleton)
    │
    └─► Resposta: notificação de sucesso ou erro
```
