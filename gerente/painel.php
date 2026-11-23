
<?php
require_once "../config.php";
exigirCargo(["administrador", "gerente"]);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Gerente</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Gráfica - Gerente</h2>
    <div>
        <?= h($_SESSION["nome"]) ?>
        <a href="../logout.php">Sair</a>
    </div>
</div>

<div class="container">
    <h1>Painel do Gerente</h1>

    <div class="cards">
        <a href="vendas.php" class="card">
            <h2>💰 Vendas</h2>
            <p>Consultar vendas realizadas.</p>
        </a>

        <a href="estoques.php" class="card">
            <h2>📦 Estoque</h2>
            <p>Registrar entradas e saídas.</p>
        </a>

        <a href="pedidos.php" class="card">
            <h2>🖨️ Pedidos</h2>
            <p>Acompanhar pedidos da gráfica.</p>
        </a>

        <a href="relatorios.php" class="card">
            <h2>📊 Relatórios</h2>
            <p>Consultar e imprimir relatórios.</p>
        </a>

        <a href="../funcionario/produtos.php" class="card">
            <h2>📋 Produtos</h2>
            <p>Consultar catálogo e preços.</p>
        </a>
    </div>
</div>
</body>
</html>
