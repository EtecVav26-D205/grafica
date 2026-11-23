
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
    <title>Consultar Estoque</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Estoque da Gráfica</h2>
    <a href="<?= h(voltarPainel()) ?>">Voltar</a>
</div>

<div class="container">
    <h1>Produtos disponíveis</h1>

    <div class="tabela-container">
        <table class="tabela">
            <tr>
                <th>Produto</th>
                <th>Marca</th>
                <th>Tamanho</th>
                <th>Cor</th>
                <th>Preço</th>
                <th>Quantidade</th>
                <th>Situação</th>
            </tr>

            <?php foreach ($produtos as $p): ?>
            <tr>
                <td><?= h($p["nome"]) ?></td>
                <td><?= h($p["marca"]) ?></td>
                <td><?= h($p["tamanho"]) ?></td>
                <td><?= h($p["cor"]) ?></td>
                <td><?= dinheiro($p["preco"]) ?></td>
                <td><?= $p["quantidade"] ?></td>
                <td>
                    <?php
                    if ($p["quantidade"] == 0) {
                        echo "Esgotado";
                    } elseif ($p["quantidade"] <= 5) {
                        echo "Estoque baixo";
                    } else {
                        echo "Disponível";
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
