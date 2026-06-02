# Cadastro de Resultados — Arquitetura DAO com Biblioteca.php

## Estrutura de Arquivos

```
projeto/
│
├── Biblioteca.php              ← NÚCLEO: toda a lógica da aplicação
│
├── index.php                   ← Página de cadastro (só HTML + include)
│
├── configuracao/
│   ├── banco.php               ← Credenciais MySQL (chamado por Biblioteca.php)
│   └── esquema.sql             ← DDL: cria banco e tabela
│
└── publico/
    ├── css/
    │   └── estilos.css         ← Estilos globais (dark/light mode)
    └── js/
        └── formulario.js       ← Comportamentos do formulário
```

---

## O que está em Biblioteca.php

O arquivo reúne 7 seções, cada uma com responsabilidade única:

| Seção | Classe / Dado | Responsabilidade |
|---|---|---|
| 1 | `Conexao` | Singleton de conexão mysqli |
| 2 | `Fornecedor` | Modelo de dados (objeto PHP puro) |
| 3 | `FornecedorDAO` | Queries SQL (INSERT, SELECT…) |
| 4 | `Sanitizador` | Limpeza dos dados brutos do $_POST |
| 5 | `ValidadorFornecedor` | Regras de negócio por campo |
| 6 | `ControladorFormulario` | Orquestra sanitização → validação → persistência |
| 7 | `$ESTADOS_BRASIL` | Array auxiliar com todos os estados |

---

## Como usar em uma nova página

Qualquer página que precise da lógica precisa apenas de **duas linhas**:

```php
<?php
require_once __DIR__ . '/Biblioteca.php';

$controlador = new ControladorFormulario();
$controlador->processar();
?>
```

Depois, no HTML, acesse os resultados via `$controlador`:

```php
// Notificação de retorno
$controlador->notificacao   // texto da mensagem
$controlador->tipoNotific   // 'sucesso' | 'erro' | 'aviso'

// Campos (repopulados após erro de validação)
$controlador->camposForm['nome_fornecedor']
$controlador->camposForm['estado']
// ... etc.

// Erros por campo
$controlador->errosForm['nome_fornecedor']  // string com a mensagem de erro
// Array vazio = sem erros
```

Para acessar diretamente o banco em outra página:

```php
require_once __DIR__ . '/Biblioteca.php';

$dao   = new FornecedorDAO();
$lista = $dao->listarTodos();   // Fornecedor[]

foreach ($lista as $fornecedor) {
    echo $fornecedor->nomeFornecedor;
    echo $fornecedor->rotuloDaAnalise(); // "Aprovado" ou "Adulterado"
}
```

---

## Fluxo de uma Requisição POST

```
Usuário clica "Salvar"
    │
    ▼
index.php
    └── require_once 'Biblioteca.php'
    └── $controlador->processar()
            │
            ├─ Sanitizador::limparFormulario($_POST)
            │
            ├─ ValidadorFornecedor::validar($campos)
            │     ├── erros? → $controlador->tipoNotific = 'aviso'  → volta ao form
            │     └── ok?    → continua
            │
            ├─ Fornecedor::doCampos($campos)          → objeto modelo
            │
            ├─ FornecedorDAO::inserir($fornecedor)    → Prepared Statement
            │       └── Conexao::obter()              → conexão Singleton
            │
            └─ $controlador->tipoNotific = 'sucesso'  → exibe confirmação
```

---

## Como Executar

**1. Criar o banco de dados:**
```bash
mysql -u root -p < configuracao/esquema.sql
```

**2. Ajustar credenciais em** `configuracao/banco.php`:
```php
define('BD_USUARIO', 'seu_usuario');
define('BD_SENHA',   'sua_senha');
```

**3. Colocar a pasta no servidor:**
- XAMPP: `C:/xampp/htdocs/projeto/`
- WAMP:  `C:/wamp64/www/projeto/`

**4. Acessar:**
```
http://localhost/projeto/
```
