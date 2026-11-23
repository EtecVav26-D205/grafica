<?php

require_once "conexao.php";

$nome = "Administrador";
$usuario = "admin";
$senha = password_hash("admin123", PASSWORD_DEFAULT);
$cargo = "administrador";

$sql = "INSERT INTO funcionarios
(nome, usuario, senha, cargo, ativo)
VALUES (?, ?, ?, ?, 1)";

$stmt = $conexao->prepare($sql);
$stmt->execute([$nome, $usuario, $senha, $cargo]);

echo "Administrador cadastrado com sucesso!";

?>