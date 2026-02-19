<?php
if(isset($_POST['pesquisa'])) {
    $origem = trim($_POST['origem_nome']);
    $destino = trim($_POST['destino_nome']);
    $curso = trim($_POST['curso']);
    $ano = trim($_POST['ano']);
    $semestre = trim($_POST['semestre']);
} else {
    header("Location: index.html");
    exit();
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title> Resultados da Pesquisa</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <h1> Equivalências para <?php echo $destino, " / ", $ano, " ano, ", $semestre, " semestre"?></h1>
        <?php require('bdERASMUS.php');
        $pesquisa = new Pesquisa;
        $pesquisa->Pesquisa();
        $pesquisa->obterDados($origem, $destino);
        $pesquisa->fecharBDErasmus(); ?>
    </body>
</html>