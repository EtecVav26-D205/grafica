
<?php
require_once "../config.php";
exigirCargo(["administrador", "gerente"]);

$mensagem = "";
$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarCsrf();

    $id = (int)($_POST["id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if (!in_array($status, [
        "pendente", "em_preparo", "finalizado"
    ], true)) {
        $erro = "Status inválido.";
    } else {
        $stmt = $conexao->prepare(
            "UPDATE pedidos SET status=? WHERE id=?"
        );
        $stmt->execute([$status, $id]);

        $mensagem = "Pedido atualizado.";
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
    <title>Gerenciar Pedidos</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Gerenciamento de Pedidos</h2>
    <a href="<?= h(voltarPainel()) ?>">Voltar</a>
</div>

<div class="container">
    <?php if ($mensagem): ?>
        <p class="aviso"><?= h($mensagem) ?></p>
    <?php endif; ?>
    <?php if ($erro): ?>
        <p class="aviso"><?= h($erro) ?></p>
    <?php endif; ?>

    <h2>Pedidos da gráfica</h2>

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
                <td>
                    <form method="POST">
                        <input type="hidden" name="csrf"
                               value="<?= csrf() ?>">
                        <input type="hidden" name="id"
                               value="<?= $p["id"] ?>">

                        <select name="status">
                            <?php foreach ([
                                "pendente" => "Pendente",
                                "em_preparo" => "Em preparo",
                                "finalizado" => "Finalizado"
                            ] as $valor => $titulo): ?>
                                <option value="<?= $valor ?>"
                                    <?= $p["status"] === $valor
                                        ? "selected" : "" ?>>
                                    <?= $titulo ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button>Atualizar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
