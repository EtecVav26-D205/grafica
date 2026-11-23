
<?php
require_once "../config.php";
exigirCargo("administrador");

$erro = "";
$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarCsrf();

    $acao = $_POST["acao"] ?? "";
    $id = (int)($_POST["id"] ?? 0);

    try {
        if ($acao === "salvar") {
            $nome = trim($_POST["nome"] ?? "");
            $usuario = trim($_POST["usuario"] ?? "");
            $senha = $_POST["senha"] ?? "";
            $cargo = $_POST["cargo"] ?? "";

            if ($nome === "" || $usuario === "") {
                throw new Exception("Preencha nome e usuário.");
            }

            if (!in_array($cargo, [
                "administrador", "gerente", "funcionario"
            ], true)) {
                throw new Exception("Cargo inválido.");
            }

            if ($id === 0) {
                if (strlen($senha) < 8) {
                    throw new Exception(
                        "A senha precisa ter 8 caracteres."
                    );
                }

                $stmt = $conexao->prepare(
                    "INSERT INTO funcionarios
                     (nome, usuario, senha, cargo, ativo)
                     VALUES (?, ?, ?, ?, 1)"
                );

                $stmt->execute([
                    $nome,
                    $usuario,
                    password_hash($senha, PASSWORD_DEFAULT),
                    $cargo
                ]);
            } else {
                $stmt = $conexao->prepare(
                    "SELECT cargo, ativo FROM funcionarios
                     WHERE id = ?"
                );
                $stmt->execute([$id]);
                $atual = $stmt->fetch();

                if (!$atual) {
                    throw new Exception("Funcionário não encontrado.");
                }

                if (
                    $id === (int)$_SESSION["funcionario_id"] &&
                    $cargo !== "administrador"
                ) {
                    throw new Exception(
                        "Você não pode remover seu próprio cargo."
                    );
                }

                if (
                    $atual["cargo"] === "administrador" &&
                    $atual["ativo"] &&
                    $cargo !== "administrador"
                ) {
                    $total = $conexao->query(
                        "SELECT COUNT(*) FROM funcionarios
                         WHERE cargo='administrador' AND ativo=1"
                    )->fetchColumn();

                    if ($total <= 1) {
                        throw new Exception(
                            "É necessário manter um administrador ativo."
                        );
                    }
                }

                $sql = "UPDATE funcionarios
                        SET nome=?, usuario=?, cargo=?";
                $dados = [$nome, $usuario, $cargo];

                if ($senha !== "") {
                    if (strlen($senha) < 8) {
                        throw new Exception("Senha muito curta.");
                    }

                    $sql .= ", senha=?";
                    $dados[] = password_hash(
                        $senha, PASSWORD_DEFAULT
                    );
                }

                $sql .= " WHERE id=?";
                $dados[] = $id;

                $conexao->prepare($sql)->execute($dados);
            }

            $mensagem = "Funcionário salvo com sucesso.";
        }

        if ($acao === "alternar") {
            if ($id === (int)$_SESSION["funcionario_id"]) {
                throw new Exception(
                    "Você não pode desativar sua própria conta."
                );
            }

            $stmt = $conexao->prepare(
                "SELECT cargo, ativo FROM funcionarios WHERE id=?"
            );
            $stmt->execute([$id]);
            $alvo = $stmt->fetch();

            if (!$alvo) {
                throw new Exception("Funcionário não encontrado.");
            }

            if (
                $alvo["cargo"] === "administrador" &&
                $alvo["ativo"]
            ) {
                $total = $conexao->query(
                    "SELECT COUNT(*) FROM funcionarios
                     WHERE cargo='administrador' AND ativo=1"
                )->fetchColumn();

                if ($total <= 1) {
                    throw new Exception(
                        "Não é possível desativar o último administrador."
                    );
                }
            }

            $conexao->prepare(
                "UPDATE funcionarios SET ativo = 1 - ativo
                 WHERE id=?"
            )->execute([$id]);

            $mensagem = "Situação atualizada.";
        }
    } catch (PDOException $e) {
        $erro = "Não foi possível salvar. Verifique se o usuário já existe.";
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

$editar = null;

if (isset($_GET["editar"])) {
    $stmt = $conexao->prepare(
        "SELECT id, nome, usuario, cargo
         FROM funcionarios WHERE id=?"
    );
    $stmt->execute([(int)$_GET["editar"]]);
    $editar = $stmt->fetch();
}

$funcionarios = $conexao->query(
    "SELECT id, nome, usuario, cargo, ativo
     FROM funcionarios ORDER BY id DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Funcionários</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topo">
    <h2>Gerenciar Funcionários</h2>
    <a href="<?= h(voltarPainel()) ?>">Voltar ao painel</a>
</div>

<div class="container">
    <?php if ($erro): ?>
        <p class="aviso"><?= h($erro) ?></p>
    <?php endif; ?>

    <?php if ($mensagem): ?>
        <p class="aviso"><?= h($mensagem) ?></p>
    <?php endif; ?>

    <h2><?= $editar ? "Editar" : "Cadastrar" ?> funcionário</h2>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id"
               value="<?= h($editar["id"] ?? 0) ?>">

        <label>Nome</label>
        <input name="nome" required
               value="<?= h($editar["nome"] ?? "") ?>">

        <label>Usuário</label>
        <input name="usuario" required
               value="<?= h($editar["usuario"] ?? "") ?>">

        <label>Senha</label>
        <input type="password" name="senha"
               <?= $editar ? "" : "required" ?>>
        <small>
            <?= $editar
                ? "Deixe em branco para manter a senha."
                : "Mínimo de 8 caracteres." ?>
        </small>

        <label>Cargo</label>
        <select name="cargo">
            <?php foreach ([
                "administrador", "gerente", "funcionario"
            ] as $cargo): ?>
                <option value="<?= $cargo ?>"
                    <?= ($editar["cargo"] ?? "") === $cargo
                        ? "selected" : "" ?>>
                    <?= ucfirst($cargo) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button>Salvar funcionário</button>
        <a class="botao" href="funcionarios.php">Limpar</a>
    </form>

    <h2>Funcionários cadastrados</h2>
    <div class="tabela-container">
        <table class="tabela">
            <tr>
                <th>Nome</th>
                <th>Usuário</th>
                <th>Cargo</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>

            <?php foreach ($funcionarios as $f): ?>
            <tr>
                <td><?= h($f["nome"]) ?></td>
                <td><?= h($f["usuario"]) ?></td>
                <td><?= h($f["cargo"]) ?></td>
                <td><?= $f["ativo"] ? "Ativo" : "Inativo" ?></td>
                <td>
                    <div class="acoes">
                        <a class="botao"
                           href="?editar=<?= $f["id"] ?>">
                            Editar
                        </a>

                        <form method="POST">
                            <input type="hidden" name="csrf"
                                   value="<?= csrf() ?>">
                            <input type="hidden" name="acao"
                                   value="alternar">
                            <input type="hidden" name="id"
                                   value="<?= $f["id"] ?>">
                            <button class="perigo">
                                <?= $f["ativo"]
                                    ? "Desativar" : "Ativar" ?>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
