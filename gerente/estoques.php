
<?php
require_once "../config.php";
exigirCargo(["administrador", "gerente"]);

$erro = "";
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarCsrf();

    try {
        $id = filter_var(
            $_POST["produto_id"] ?? null,
            FILTER_VALIDATE_INT
        );
        $qtd = filter_var(
            $_POST["quantidade"] ?? null,
            FILTER_VALIDATE_INT
        );
        $tipo = $_POST["tipo"] ?? "";
        $observacao = trim($_POST["observacao"] ?? "");

        if (
            $id === false || $id <= 0 ||
            $qtd === false || $qtd <= 0 ||
            !in_array($tipo, ["entrada", "saida"], true)
        ) {
            throw new Exception("Dados inválidos.");
        }

        $conexao->beginTransaction();

        $stmt = $conexao->prepare(
            "SELECT quantidade FROM produtos
             WHERE id=? AND ativo=1 FOR UPDATE"
        );
        $stmt->execute([$id]);
        $produto = $stmt->fetch();

        if (!$produto) {
            throw new Exception("Produto não encontrado.");
        }

        if ($tipo === "saida" && $produto["quantidade"] < $qtd) {
            throw new Exception("Estoque insuficiente.");
        }

        $diferenca = $tipo === "entrada" ? $qtd : -$qtd;

        $conexao->prepare(
            "UPDATE produtos
             SET quantidade = quantidade + ?
             WHERE id=?"
        )->execute([$diferenca, $id]);

        $conexao->prepare(
            "INSERT INTO movimentacoes_estoque
             (produto_id, funcionario_id, tipo,
              quantidade, observacao)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $id,
            $_SESSION["funcionario_id"],
            $tipo,
            $qtd,
            $observacao
        ]);

        $conexao->commit();
        $mensagem = "Estoque atualizado com sucesso.";

    } catch (Throwable $e) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        $erro = $e instanceof PDOException
            ? "Erro ao atualizar estoque."
            : $e->getMessage();
    }
}

$produtos = $conexao->query(
    "SELECT * FROM produtos
     WHERE ativo=1 ORDER BY nome"
)->fetchAll();

$movimentacoes = $conexao->query(
    "SELECT m.*, p.nome AS produto, f.nome AS funcionario
     FROM movimentacoes_estoque m
     JOIN produtos p ON p.id=m.produto_id
     JOIN funcionarios f ON f.id=m.funcionario_id
     ORDER BY m.id DESC LIMIT 50"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Controle de Estoque</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Controle de Estoque</h2>
    <a href="<?= h(voltarPainel()) ?>">Voltar</a>
</div>

<div class="container">
    <?php if ($erro): ?>
        <p class="aviso"><?= h($erro) ?></p>
    <?php endif; ?>
    <?php if ($mensagem): ?>
        <p class="aviso"><?= h($mensagem) ?></p>
    <?php endif; ?>

    <h2>Movimentar estoque</h2>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <label>Produto</label>
        <select name="produto_id" required>
            <?php foreach ($produtos as $p): ?>
                <option value="<?= $p["id"] ?>">
                    <?= h($p["nome"]) ?>
                    - <?= h($p["tamanho"]) ?>
                    - <?= h($p["cor"]) ?>
                    (<?= $p["quantidade"] ?> unidades)
                </option>
            <?php endforeach; ?>
        </select>

        <label>Tipo</label>
        <select name="tipo">
            <option value="entrada">Entrada</option>
            <option value="saida">Saída</option>
        </select>

        <label>Quantidade</label>
        <input type="number" name="quantidade"
               min="1" required>

        <label>Observação</label>
        <input name="observacao"
               placeholder="Ex.: Reposição de material">

        <button>Registrar movimentação</button>
    </form>

    <h2>Últimas movimentações</h2>

    <div class="tabela-container">
        <table class="tabela">
            <tr>
                <th>Data</th>
                <th>Produto</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Responsável</th>
                <th>Observação</th>
            </tr>

            <?php foreach ($movimentacoes as $m): ?>
            <tr>
                <td><?= h($m["data_movimentacao"]) ?></td>
                <td><?= h($m["produto"]) ?></td>
                <td><?= h($m["tipo"]) ?></td>
                <td><?= $m["quantidade"] ?></td>
                <td><?= h($m["funcionario"]) ?></td>
                <td><?= h($m["observacao"]) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
