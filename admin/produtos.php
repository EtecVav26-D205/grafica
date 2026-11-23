
<?php
require_once "../config.php";

exigirCargo(["administrador"]);

$erro = "";
$sucesso = "";
$editando = null;

if (empty($_SESSION["csrf_produtos"])) {
    $_SESSION["csrf_produtos"] = bin2hex(random_bytes(32));
}

$token = $_SESSION["csrf_produtos"];

function escProduto($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($token, $_POST["csrf"] ?? "")) {
        $erro = "Requisição inválida.";
    } else {

        try {
            $acao = $_POST["acao"] ?? "";

            if ($acao === "salvar") {

                $id = (int)($_POST["id"] ?? 0);
                $nome = trim($_POST["nome"] ?? "");
                $precoTexto = str_replace(
                    ",",
                    ".",
                    trim($_POST["preco"] ?? "")
                );
                $quantidadeTexto = trim(
                    $_POST["quantidade"] ?? ""
                );

                if ($nome === "" || mb_strlen($nome) > 100) {
                    throw new Exception(
                        "Informe um nome de até 100 caracteres."
                    );
                }

                if (
                    !is_numeric($precoTexto) ||
                    (float)$precoTexto < 0 ||
                    (float)$precoTexto > 99999999.99
                ) {
                    throw new Exception("Preço inválido.");
                }

                $quantidadeValidada = filter_var(
                    $quantidadeTexto,
                    FILTER_VALIDATE_INT,
                    ["options" => ["min_range" => 0]]
                );

                if ($quantidadeValidada === false) {
                    throw new Exception("Quantidade inválida.");
                }

                $preco = round((float)$precoTexto, 2);
                $quantidade = $quantidadeValidada;

                $conexao->beginTransaction();

                if ($id > 0) {

                    $stmt = $conexao->prepare("
                        SELECT quantidade
                        FROM produtos
                        WHERE id = ?
                        FOR UPDATE
                    ");
                    $stmt->execute([$id]);
                    $anterior = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$anterior) {
                        throw new Exception("Produto não encontrado.");
                    }

                    $quantidadeAnterior = (int)$anterior["quantidade"];

                    $stmt = $conexao->prepare("
                        UPDATE produtos
                        SET nome = ?,
                            preco = ?,
                            quantidade = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $nome,
                        $preco,
                        $quantidade,
                        $id
                    ]);

                    $sucesso = "Produto atualizado com sucesso.";

                } else {

                    $stmt = $conexao->prepare("
                        INSERT INTO produtos
                            (nome, preco, quantidade, ativo)
                        VALUES (?, ?, ?, 1)
                    ");

                    $stmt->execute([
                        $nome,
                        $preco,
                        $quantidade
                    ]);

                    $id = (int)$conexao->lastInsertId();
                    $quantidadeAnterior = 0;

                    $sucesso = "Produto cadastrado com sucesso.";
                }

                $diferenca = $quantidade - $quantidadeAnterior;

                if ($diferenca !== 0) {

                    $tipo = $diferenca > 0 ? "entrada" : "saida";

                    $stmt = $conexao->prepare("
                        INSERT INTO movimentacoes_estoque
                            (
                                produto_id,
                                funcionario_id,
                                tipo,
                                quantidade,
                                observacao
                            )
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $id,
                        (int)$_SESSION["funcionario_id"],
                        $tipo,
                        abs($diferenca),
                        "Ajuste manual pelo administrador"
                    ]);
                }

                $conexao->commit();

            } elseif ($acao === "status") {

                $id = (int)($_POST["id"] ?? 0);
                $ativo = (int)($_POST["ativo"] ?? -1);

                if (
                    $id <= 0 ||
                    !in_array($ativo, [0, 1], true)
                ) {
                    throw new Exception("Dados inválidos.");
                }

                $stmt = $conexao->prepare("
                    UPDATE produtos
                    SET ativo = ?
                    WHERE id = ?
                ");

                $stmt->execute([$ativo, $id]);

                $sucesso = $ativo
                    ? "Produto ativado com sucesso."
                    : "Produto desativado com sucesso.";

            } else {
                throw new Exception("Ação inválida.");
            }

        } catch (Throwable $e) {

            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            $erro = $e->getMessage();
        }
    }
}

if (isset($_GET["editar"])) {

    $idEditar = (int)$_GET["editar"];

    $stmt = $conexao->prepare("
        SELECT *
        FROM produtos
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$idEditar]);
    $editando = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editando) {
        $erro = "Produto não encontrado.";
    }
}

try {

    $stmt = $conexao->query("
        SELECT *
        FROM produtos
        ORDER BY id DESC
    ");

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    $produtos = [];
    $erro = "Erro ao carregar produtos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - SGG</title>
    <link rel="stylesheet" href="../assets/style.css?v=4">
</head>

<body>

<div class="topo">
    <h2>SGG / Produtos</h2>

    <div>
        <span>
            <?= escProduto($_SESSION["nome"] ?? "Administrador") ?>
        </span>

        <a href="painel.php">Voltar ao painel</a>
        <a href="../logout.php">Sair</a>
    </div>
</div>

<main class="container">

    <h1>Gerenciamento de Produtos</h1>

    <p>Cadastre, edite e organize os produtos da gráfica.</p>

    <?php if ($erro): ?>
        <div class="aviso" style="background:#fef2f2;color:#b91c1c;">
            <?= escProduto($erro) ?>
        </div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="aviso" style="background:#f0fdf4;color:#15803d;">
            <?= escProduto($sucesso) ?>
        </div>
    <?php endif; ?>

    <div class="bloco">

        <h2>
            <?= $editando ? "Editar Produto" : "Cadastrar Produto" ?>
        </h2>

        <form method="POST">

            <input type="hidden" name="csrf"
                   value="<?= escProduto($token) ?>">

            <input type="hidden" name="acao" value="salvar">

            <input type="hidden" name="id"
                   value="<?= (int)($editando["id"] ?? 0) ?>">

            <label for="nome">Nome do produto</label>

            <input
                type="text"
                id="nome"
                name="nome"
                maxlength="100"
                placeholder="Ex.: Camiseta personalizada"
                value="<?= escProduto($editando["nome"] ?? "") ?>"
                required
            >

            <label for="preco">Preço (R$)</label>

            <input
                type="number"
                id="preco"
                name="preco"
                step="0.01"
                min="0"
                placeholder="Ex.: 39.90"
                value="<?= escProduto($editando["preco"] ?? "") ?>"
                required
            >

            <label for="quantidade">Quantidade em estoque</label>

            <input
                type="number"
                id="quantidade"
                name="quantidade"
                min="0"
                step="1"
                value="<?= (int)($editando["quantidade"] ?? 0) ?>"
                required
            >

            <div class="acoes" style="margin-top:20px;">

                <button type="submit">
                    <?= $editando
                        ? "Salvar Alterações"
                        : "Cadastrar Produto" ?>
                </button>

                <?php if ($editando): ?>
                    <a href="produtos.php" class="botao">
                        Cancelar Edição
                    </a>
                <?php endif; ?>

            </div>

        </form>

    </div>

    <h2>Produtos Cadastrados</h2>

    <div class="tabela-container">

        <table class="tabela">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Produto</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

            <?php if (empty($produtos)): ?>

                <tr>
                    <td colspan="6">
                        Nenhum produto cadastrado.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($produtos as $produto): ?>

                    <tr>

                        <td><?= (int)$produto["id"] ?></td>

                        <td>
                            <?= escProduto($produto["nome"]) ?>
                        </td>

                        <td>
                            R$ <?= number_format(
                                (float)$produto["preco"],
                                2,
                                ",",
                                "."
                            ) ?>
                        </td>

                        <td>
                            <?= (int)$produto["quantidade"] ?>
                        </td>

                        <td>
                            <?php if ((int)$produto["ativo"] === 1): ?>
                                <span style="color:#16a34a;font-weight:700;">
                                    Ativo
                                </span>
                            <?php else: ?>
                                <span style="color:#dc2626;font-weight:700;">
                                    Inativo
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>

                            <div class="acoes">

                                <a
                                    href="produtos.php?editar=<?= (int)$produto["id"] ?>"
                                    class="botao"
                                >
                                    Editar
                                </a>

                                <form method="POST">

                                    <input type="hidden" name="csrf"
                                           value="<?= escProduto($token) ?>">

                                    <input type="hidden" name="acao"
                                           value="status">

                                    <input type="hidden" name="id"
                                           value="<?= (int)$produto["id"] ?>">

                                    <input
                                        type="hidden"
                                        name="ativo"
                                        value="<?= (int)$produto["ativo"] === 1 ? 0 : 1 ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="<?= (int)$produto["ativo"] === 1 ? "perigo" : "sucesso" ?>"
                                    >
                                        <?= (int)$produto["ativo"] === 1
                                            ? "Desativar"
                                            : "Ativar" ?>
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>
</html>
