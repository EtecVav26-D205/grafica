
<?php
require_once "../config.php";
exigirCargo(["administrador", "gerente"]);

$totalVendas = $conexao->query(
    "SELECT COUNT(*) FROM vendas"
)->fetchColumn();

$faturamento = $conexao->query(
    "SELECT COALESCE(SUM(valor_total), 0) FROM vendas"
)->fetchColumn();

$totalProdutos = $conexao->query(
    "SELECT COUNT(*) FROM produtos WHERE ativo=1"
)->fetchColumn();

$totalPedidos = $conexao->query(
    "SELECT COUNT(*) FROM pedidos"
)->fetchColumn();

$pendentes = $conexao->query(
    "SELECT COUNT(*) FROM pedidos
     WHERE status='pendente'"
)->fetchColumn();

$finalizados = $conexao->query(
    "SELECT COUNT(*) FROM pedidos
     WHERE status='finalizado'"
)->fetchColumn();

$estoque = $conexao->query(
    "SELECT nome, tamanho, cor, quantidade, preco
     FROM produtos WHERE ativo=1
     ORDER BY nome"
)->fetchAll();

$vendas = $conexao->query(
    "SELECT DATE(data_venda) AS dia,
            COUNT(*) AS quantidade,
            SUM(valor_total) AS total
     FROM vendas
     GROUP BY DATE(data_venda)
     ORDER BY dia DESC"
)->fetchAll();

$pedidos = $conexao->query(
    "SELECT status, COUNT(*) AS quantidade
     FROM pedidos GROUP BY status"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatórios da Gráfica</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Relatórios Gerenciais</h2>
    <a href="<?= h(voltarPainel()) ?>">Voltar</a>
</div>

<div class="container">
    <h1>Relatório da Gráfica</h1>
    <p>Emitido em <?= date("d/m/Y H:i") ?></p>
    <p>Responsável: <?= h($_SESSION["nome"]) ?></p>

    <div class="resumo">
        <div class="bloco">
            <h3>Vendas</h3>
            <h2><?= $totalVendas ?></h2>
        </div>

        <div class="bloco">
            <h3>Faturamento registrado</h3>
            <h2><?= dinheiro($faturamento) ?></h2>
        </div>

        <div class="bloco">
            <h3>Produtos ativos</h3>
            <h2><?= $totalProdutos ?></h2>
        </div>

        <div class="bloco">
            <h3>Pedidos</h3>
            <h2><?= $totalPedidos ?></h2>
        </div>
    </div>

    <div class="bloco">
        <h2>Situação dos pedidos</h2>
        <p>Pendentes: <?= $pendentes ?></p>
        <p>Finalizados: <?= $finalizados ?></p>

        <?php foreach ($pedidos as $p): ?>
            <p>
                <?= h(str_replace("_", " ", $p["status"])) ?>:
                <?= $p["quantidade"] ?>
            </p>
        <?php endforeach; ?>
    </div>

    <h2>Vendas por dia</h2>

    <div class="tabela-container">
        <table class="tabela">
            <tr>
                <th>Data</th>
                <th>Quantidade de vendas</th>
                <th>Valor total</th>
            </tr>

            <?php foreach ($vendas as $v): ?>
            <tr>
                <td><?= h($v["dia"]) ?></td>
                <td><?= $v["quantidade"] ?></td>
                <td><?= dinheiro($v["total"]) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <h2>Relatório de estoque</h2>

    <div class="tabela-container">
        <table class="tabela">
            <tr>
                <th>Produto</th>
                <th>Tamanho</th>
                <th>Cor</th>
                <th>Quantidade</th>
                <th>Preço</th>
                <th>Situação</th>
            </tr>

            <?php foreach ($estoque as $p): ?>
            <tr>
                <td><?= h($p["nome"]) ?></td>
                <td><?= h($p["tamanho"]) ?></td>
                <td><?= h($p["cor"]) ?></td>
                <td><?= $p["quantidade"] ?></td>
                <td><?= dinheiro($p["preco"]) ?></td>
                <td>
                    <?= $p["quantidade"] == 0
                        ? "Esgotado"
                        : ($p["quantidade"] <= 5
                            ? "Estoque baixo" : "Disponível") ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <button onclick="window.print()">
        Imprimir / Salvar em PDF
    </button>
</div>
</body>
</html>
