
<?php
require_once "../config.php";
exigirCargo(["administrador", "gerente", "funcionario"]);

$produtos = $conexao->query(
    "SELECT * FROM produtos
     WHERE ativo=1 ORDER BY nome, tamanho, cor"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Produtos</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Catálogo de Produtos</h2>
    <a href="<?= h(voltarPainel()) ?>">Voltar</a>
</div>

<div class="container">
    <h1>Produtos da gráfica</h1>

    <div class="tabela-container">
        <table class="tabela">
            <tr>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Marca</th>
                <th>Tamanho</th>
                <th>Cor</th>
                <th>Preço</th>
            </tr>

            <?php foreach ($produtos as $p): ?>
            <tr>
                <td><?= h($p["nome"]) ?></td>
                <td><?= h($p["descricao"]) ?></td>
                <td><?= h($p["marca"]) ?></td>
                <td><?= h($p["tamanho"]) ?></td>
                <td><?= h($p["cor"]) ?></td>
                <td><?= dinheiro($p["preco"]) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
