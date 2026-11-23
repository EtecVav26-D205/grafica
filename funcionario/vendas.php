
<?php
require_once "../config.php";
exigirCargo(["administrador", "gerente", "funcionario"]);

$erro = "";
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarCsrf();

    try {
        $ids = $_POST["produto_id"] ?? [];
        $quantidades = $_POST["quantidade"] ?? [];

        if (!is_array($ids) || !is_array($quantidades)) {
            throw new Exception("Venda inválida.");
        }

        $itens = [];

        foreach ($ids as $i => $id) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            $qtd = filter_var(
                $quantidades[$i] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($id === false || $id <= 0 ||
                $qtd === false || $qtd <= 0) {
                continue;
            }

            $itens[$id] = ($itens[$id] ?? 0) + $qtd;
        }

        if (!$itens) {
            throw new Exception("Selecione produtos e quantidades.");
        }

        $conexao->beginTransaction();

        $total = 0;
        $detalhes = [];

        ksort($itens);

        foreach ($itens as $id => $qtd) {
            $stmt = $conexao->prepare(
                "SELECT * FROM produtos
                 WHERE id=? AND ativo=1 FOR UPDATE"
            );
            $stmt->execute([$id]);
            $produto = $stmt->fetch();

            if (!$produto || $produto["quantidade"] < $qtd) {
                throw new Exception(
                    "Produto indisponível ou estoque insuficiente."
                );
            }

            $subtotal = round($produto["preco"] * $qtd, 2);
            $total += $subtotal;

            $detalhes[] = [
                "id" => $id,
                "qtd" => $qtd,
                "preco" => $produto["preco"],
                "subtotal" => $subtotal
            ];
        }

        $stmt = $conexao->prepare(
            "INSERT INTO vendas
             (funcionario_id, valor_total)
             VALUES (?, ?)"
        );
        $stmt->execute([
            $_SESSION["funcionario_id"], $total
        ]);

        $vendaId = $conexao->lastInsertId();

        foreach ($detalhes as $item) {
            $conexao->prepare(
                "INSERT INTO itens_venda
                 (venda_id, produto_id, quantidade,
                  preco_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([
                $vendaId,
                $item["id"],
                $item["qtd"],
                $item["preco"],
                $item["subtotal"]
            ]);

            $conexao->prepare(
                "UPDATE produtos
                 SET quantidade = quantidade - ?
                 WHERE id=?"
            )->execute([
                $item["qtd"], $item["id"]
            ]);

            $conexao->prepare(
                "INSERT INTO movimentacoes_estoque
                 (produto_id, funcionario_id, tipo,
                  quantidade, observacao)
                 VALUES (?, ?, 'saida', ?, ?)"
            )->execute([
                $item["id"],
                $_SESSION["funcionario_id"],
                $item["qtd"],
                "Venda #" . $vendaId
            ]);
        }

        $conexao->commit();
        $mensagem = "Venda #$vendaId registrada. Total: "
                    . dinheiro($total);

    } catch (Throwable $e) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        $erro = $e instanceof PDOException
            ? "Erro ao registrar venda."
            : $e->getMessage();
    }
}

$produtos = $conexao->query(
    "SELECT * FROM produtos
     WHERE ativo=1 ORDER BY nome, tamanho, cor"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar Venda</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Registrar Venda</h2>
    <a href="<?= h(voltarPainel()) ?>">Voltar</a>
</div>

<div class="container">
    <?php if ($erro): ?>
        <p class="aviso"><?= h($erro) ?></p>
    <?php endif; ?>
    <?php if ($mensagem): ?>
        <p class="aviso"><?= h($mensagem) ?></p>
    <?php endif; ?>

    <h2>Nova venda</h2>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <?php for ($i = 0; $i < 5; $i++): ?>
            <div class="bloco">
                <h3>Item <?= $i + 1 ?></h3>

                <label>Produto</label>
                <select name="produto_id[]">
                    <option value="">Não selecionar</option>
                    <?php foreach ($produtos as $p): ?>
                        <option value="<?= $p["id"] ?>">
                            <?= h($p["nome"]) ?>
                            <?= h($p["tamanho"]) ?>
                            <?= h($p["cor"]) ?>
                            - <?= dinheiro($p["preco"]) ?>
                            (Estoque: <?= $p["quantidade"] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Quantidade</label>
                <input type="number" name="quantidade[]"
                       min="1" value="1">
            </div>
        <?php endfor; ?>

        <button>Finalizar venda</button>
    </form>

    <p>É possível selecionar até cinco itens diferentes
       por venda. Itens repetidos serão somados.</p>
</div>
</body>
</html>
