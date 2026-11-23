
<?php
require_once "../config.php";
exigirCargo(["administrador", "gerente", "funcionario"]);

$erro = "";
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarCsrf();

    $descricao = trim($_POST["descricao"] ?? "");

    if ($descricao === "") {
        $erro = "Informe a descrição do serviço.";
    } else {
        $stmt = $conexao->prepare(
            "INSERT INTO pedidos
             (descricao, funcionario_id, status)
             VALUES (?, ?, 'pendente')"
        );

        $stmt->execute([
            $descricao,
            $_SESSION["funcionario_id"]
        ]);

        $mensagem = "Pedido cadastrado com sucesso.";
    }
}

$pedidos = $conexao->query(
    "SELECT p.*, f.nome AS funcionario
     FROM pedidos p
     JOIN funcionarios f ON f.id=p.funcionario_id
     ORDER BY p.id DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pedidos</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Pedidos Gráficos</h2>
    <a href="<?= h(voltarPainel()) ?>">Voltar</a>
</div>

<div class="container">
    <?php if ($erro): ?>
        <p class="aviso"><?= h($erro) ?></p>
    <?php endif; ?>
    <?php if ($mensagem): ?>
        <p class="aviso"><?= h($mensagem) ?></p>
    <?php endif; ?>

    <h2>Cadastrar pedido</h2>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <label>Descrição do serviço</label>
        <textarea name="descricao" required
                  placeholder="Ex.: 100 cópias coloridas A4"></textarea>

        <button>Cadastrar pedido</button>
    </form>

    <h2>Pedidos registrados</h2>

    <div class="tabela-container">
        <table class="tabela">
            <tr>
                <th>ID</th>
                <th>Descrição</th>
                <th>Responsável</th>
                <th>Data</th>
                <th>Status</th>
            </tr>

            <?php foreach ($pedidos as $p): ?>
            <tr>
                <td><?= $p["id"] ?></td>
                <td><?= h($p["descricao"]) ?></td>
                <td><?= h($p["funcionario"]) ?></td>
                <td><?= h($p["data_registro"]) ?></td>
                <td><?= h(str_replace(
                    "_", " ", $p["status"]
                )) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
