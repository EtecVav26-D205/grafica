
<?php
require_once "../config.php";
exigirCargo(["administrador", "gerente"]);

$vendas = $conexao->query(
    "SELECT v.*, f.nome AS funcionario
     FROM vendas v
     JOIN funcionarios f ON f.id=v.funcionario_id
     ORDER BY v.id DESC"
)->fetchAll();

$itens = $conexao->query(
    "SELECT i.*, p.nome AS produto
     FROM itens_venda i
     JOIN produtos p ON p.id=i.produto_id
     ORDER BY i.venda_id DESC"
)->fetchAll();

$porVenda = [];

foreach ($itens as $item) {
    $porVenda[$item["venda_id"]][] = $item;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Consultar Vendas</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Histórico de Vendas</h2>
    <a href="<?= h(voltarPainel()) ?>">Voltar</a>
</div>

<div class="container">
    <h1>Vendas realizadas</h1>

    <?php foreach ($vendas as $v): ?>
        <div class="bloco">
            <h3>Venda #<?= $v["id"] ?></h3>

            <p>
                <strong>Data:</strong>
                <?= h($v["data_venda"]) ?>
            </p>

            <p>
                <strong>Funcionário:</strong>
                <?= h($v["funcionario"]) ?>
            </p>

            <table class="tabela">
                <tr>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Preço unitário</th>
                    <th>Subtotal</th>
                </tr>

                <?php foreach (
                    $porVenda[$v["id"]] ?? [] as $item
                ): ?>
                <tr>
                    <td><?= h($item["produto"]) ?></td>
                    <td><?= $item["quantidade"] ?></td>
                    <td><?= dinheiro($item["preco_unitario"]) ?></td>
                    <td><?= dinheiro($item["subtotal"]) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h3>Total: <?= dinheiro($v["valor_total"]) ?></h3>
        </div>
    <?php endforeach; ?>

    <?php if (!$vendas): ?>
        <p>Nenhuma venda registrada.</p>
    <?php endif; ?>
</div>
</body>
</html>
