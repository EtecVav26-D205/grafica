
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conexao.php";

function h($texto) {
    return htmlspecialchars(
        (string)($texto ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function exigirLogin() {
    global $conexao;

    if (empty($_SESSION["funcionario_id"])) {
        header("Location: /grafica/login.php");
        exit;
    }

    $stmt = $conexao->prepare(
        "SELECT id, nome, cargo, ativo
         FROM funcionarios WHERE id = ?"
    );
    $stmt->execute([$_SESSION["funcionario_id"]]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !$usuario["ativo"]) {
        session_unset();
        session_destroy();
        header("Location: /grafica/login.php");
        exit;
    }

    $_SESSION["nome"] = $usuario["nome"];
    $_SESSION["cargo"] = $usuario["cargo"];
}

function exigirCargo($cargos) {
    exigirLogin();

    if (!in_array(
        $_SESSION["cargo"],
        (array)$cargos,
        true
    )) {
        http_response_code(403);
        exit("Acesso negado.");
    }
}

function csrf() {
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

function verificarCsrf() {
    if (
        !isset($_POST["csrf"]) ||
        !hash_equals(csrf(), $_POST["csrf"])
    ) {
        http_response_code(403);
        exit("Formulário inválido. Atualize a página.");
    }
}

function dinheiro($valor) {
    return "R$ " . number_format(
        (float)$valor, 2, ",", "."
    );
}

function voltar($url) {
    header("Location: " . $url);
    exit;
}
function voltarPainel() {
    $cargo = $_SESSION["cargo"] ?? "";

    switch ($cargo) {
        case "administrador":
            return "/grafica/admin/painel.php";

        case "gerente":
            return "/grafica/gerente/painel.php";

        case "funcionario":
            return "/grafica/funcionario/painel.php";

        default:
            return "/grafica/login.php";
    }
}
?>
