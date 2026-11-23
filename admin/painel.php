
<?php
require_once "../config.php";
exigirCargo("administrador");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel Administrativo</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Gráfica - Administrador</h2>
    <div>
        <?= h($_SESSION["nome"]) ?>
        <a href="../logout.php">Sair</a>
    </div>
</div>

<div class="container">
    <h1>Painel Administrativo</h1>

    <div class="cards">
        <a href="funcionarios.php" class="card">
            <h2>👥 Funcionários</h2>
            <p>Cadastrar e gerenciar funcionários.</p>
        </a>

        <a href="produtos.php" class="card">
            <h2>📦 Produtos</h2>
            <p>Gerenciar produtos e preços.</p>
        </a>

        <a href="../gerente/estoques.php" class="card">
            <h2>📋 Estoque</h2>
            <p>Controlar entradas e saídas.</p>
        </a>

        <a href="../gerente/vendas.php" class="card">
            <h2>💰 Vendas</h2>
            <p>Consultar histórico de vendas.</p>
        </a>

        <a href="../gerente/pedidos.php" class="card">
            <h2>🖨️ Pedidos</h2>
            <p>Acompanhar serviços gráficos.</p>
        </a>

        <a href="../gerente/relatorios.php" class="card">
            <h2>📊 Relatórios</h2>
            <p>Gerar relatórios gerenciais.</p>
        </a>
    </div>
</div>
</body>
</html>
