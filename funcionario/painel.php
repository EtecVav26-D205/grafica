
<?php
require_once "../config.php";
exigirCargo(["administrador", "gerente", "funcionario"]);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Funcionário</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Gráfica - Funcionário</h2>
    <div>
        <?= h($_SESSION["nome"]) ?>
        <a href="../logout.php">Sair</a>
    </div>
</div>

<div class="container">
    <h1>Painel do Funcionário</h1>

    <div class="cards">
        <a href="vendas.php" class="card">
            <h2>💰 Registrar Venda</h2>
            <p>Cadastrar uma nova venda.</p>
        </a>

        <a href="pedidos.php" class="card">
            <h2>🖨️ Pedidos</h2>
            <p>Registrar serviços gráficos.</p>
        </a>

        <a href="produtos.php" class="card">
            <h2>📋 Produtos</h2>
            <p>Consultar produtos e preços.</p>
        </a>

        <a href="estoque.php" class="card">
            <h2>📦 Estoque</h2>
            <p>Consultar disponibilidade.</p>
        </a>
    </div>
</div>
</body>
</html>
