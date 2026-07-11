<?php
require_once 'bdERASMUS.php';
$sistema = new Pesquisa();
$listaFac = $sistema->obterFaculdades();
$anos = $sistema->obterAnos();
$cursos = $sistema->obterCursos();
?>

<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erasmus Buddie</title>
        <link rel="stylesheet" href= "style.css">
    </head>

    <body>
        <header>
            <h1>Erasmus Buddie</h1>
            <p>Decide o teu destino com apenas alguns cliques!</p>
        </header>
        <main>
            <form method = "post" action = "pesquisa.php" class="search-boxes">
                <div class="text-and-box">
                    <label>Faculdade de Origem: </label>
                    <select name="origem_nome" required class="select-box">
                        <option value =""> Selectionar...</option>
                        <?php
                            foreach($listaFac as $faculdade) {
                                echo '<option value="'.$faculdade['Nome'].'">'.$faculdade['Nome'].'</option>';
                            }
                        ?>
                    </select>
                </div>

                <div class="text-and-box">
                    <label>Curso: </label>
                    <select name="curso" required class="select-box">
                        <option value =""> Selectionar...</option>
                        <?php
                            foreach($cursos as $curso) {
                                echo '<option value="'.$curso['Nome'].'">'.$curso['Nome'].'</option>';
                            }
                        ?>
                    </select>
                </div>

                <div class="text-and-box">
                    <label>Faculdade de Destino: </label>
                    <select name="destino_nome" required class="select-box">
                        <option value =""> Selectionar...</option>
                        <?php
                            foreach($listaFac as $faculdade) {
                                echo '<option value="'.$faculdade['Nome'].'">'.$faculdade['Nome'].'</option>';
                            }
                        ?>
                    </select>
                </div>

                <div class="text-and-box">
                    <label>Ano do Curso: </label>
                    <select name="ano_curso" required class="select-box">
                        <option value =""> Selectionar... </option>
                        <option value =""> 1º </option>
                        <option value =""> 2º </option>
                        <option value =""> 3º </option>
                    </select>
                </div>

                <div class="text-and-box">
                    <label>Ano Letivo: </label>
                    <select name="ano_letivo" required class="select-box">
                        <option value =""> Selectionar...</option>
                        <?php
                            foreach($anos as $ano) {
                                $anoAprov = htmlspecialchars($ano['Ano_Aprovacao']);
                                echo '<option value="'.$anoAprov.'">'.$anoAprov.'</option>';
                            }
                        ?>
                    </select>
                </div>

                <div class="text-and-box">
                    <label>Semestre do Curso: </label>
                    <select name="semestre" required class="select-box">
                        <option value =""> Selectionar... </option>
                        <option value =""> 1º </option>
                        <option value =""> 2º </option>
                    </select>
                </div>
                <button type="submit" name="pesquisa">Pesquisar</button><br>
            </form>
        </main>
    </body>
</html>