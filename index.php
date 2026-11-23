<?php

require_once "config.php";

exigirLogin();

if ($_SESSION["cargo"] == "administrador") {

    header("Location: admin/painel.php");

} elseif ($_SESSION["cargo"] == "gerente") {

    header("Location: gerente/painel.php");

} elseif ($_SESSION["cargo"] == "funcionario") {

    header("Location: funcionario/painel.php");

} else {

    die("Cargo inválido!");

}

exit;
?>
