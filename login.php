
<?php
require_once "config.php";

if (!empty($_SESSION["funcionario_id"])) {
    voltar("index.php");
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarCsrf();

    $usuario = trim($_POST["usuario"] ?? "");
    $senha = $_POST["senha"] ?? "";

    $stmt = $conexao->prepare(
        "SELECT * FROM funcionarios
         WHERE usuario = ? AND ativo = 1"
    );
    $stmt->execute([$usuario]);
    $funcionario = $stmt->fetch();

    if (
        $funcionario &&
        password_verify($senha, $funcionario["senha"])
    ) {
        session_regenerate_id(true);

        $_SESSION["funcionario_id"] = $funcionario["id"];
        $_SESSION["nome"] = $funcionario["nome"];
        $_SESSION["cargo"] = $funcionario["cargo"];

        voltar("index.php");
    }

    $erro = "Usuário ou senha incorretos.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Gráfica</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-box">
    <h1>Sistema da Gráfica</h1>
    <p>Acesso exclusivo para funcionários.</p>

    <?php if ($erro): ?>
        <p class="erro"><?= h($erro) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf"
               value="<?= csrf() ?>">

        <label>Usuário</label>
        <input name="usuario" required>

        <label>Senha</label>
        <input type="password" name="senha" required>

        <button type="submit">Entrar</button>
    </form>
</div>
</body>
</html>
