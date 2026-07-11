<?php
require_once 'bdERASMUS.php';
$sistema = new Pesquisa();
$listaFac = $sistema->obterFaculdades();
$mensagem = "";

if(isset($_POST['gravar-eq'])) {
    $fac_origem = trim($_POST['fac_origem']);
    $fac_destino = trim($_POST['fac_destino']);
    $disc_origem = trim($_POST['disc_origem']);
    $disc_destino = trim($_POST['disc_destino']);
    $ano_aprov = trim($_POST['ano_aprovacao']);

    if (!empty($fac_origem) && !empty($fac_destino) && !empty($disc_origem) && !empty($disc_destino) && $ano_aprov > 2000) {
        $stmt = $sistema->db_erasmus->conn->prepare("INSERT INTO Equivalencia (Disciplina_Origem, Disciplina_Destino, Ano_Aprovacao, Faculdade_Origem, Faculdade_Destino) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssiss", $disc_origem, $disc_destino, $ano_aprov, $fac_origem, $fac_destino);

        if($stmt->execute()) {
            $mensagem = "<p style='color: lightgreen; font-weight: bold;'>Equivalência gravada com sucesso! Obrigada por ajudares a comunidade.</p>";
        } else {
            $mensagem = "<p style='color: red;'>Erro ao gravar: ". $stmt->error . " (Verifica se os códigos das disciplinas já existem).</p>";
        }
        $stmt->close();
    } else {
        $mensagem = "<p style='color: orange;'> Por favor, preenche todos os campos corretamente.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Partilhar Experiência ERASMUS</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <nav class="top-menu">
            <div class ="logo-texto">Erasmus Buddie</div>
            <div class="links">
                <a href="index.php">Pesquisar</a>
                <a href="submeter.php" class="ativo">Partilhar Equivalências</a>
            </div>
        </nav>
        <main style="padding:20px;">
            <div class="bloco-forms">
                <h2>Nova Equivalência</h2>
                <?php echo $mensagem; ?>
                <form method="post" action="submeter.php" class="form-sub">
                    <div>
                        <label>Faculdade de Origem:</label><br>
                        <select name="fac_origem" required>
                            <option value="">Selecionar...</option>
                            <?php foreach($listaFac as $f) { echo "<option value='".$f['CodFaculdade']."'>".$f['Nome']."</option>"; } ?>
                        </select>
                    </div>

                    <div>
                        <label>Código da Disciplina de Origem (Ex: 03712):</label><br>
                        <input type="text" name="disc_origem" required placeholder="Ex:03712">
                    </div>

                    <div>
                        <label>Faculdade de Destino:</label><br>
                        <select name="fac_destino" required>
                            <option value="">Selecionar...</option>
                            <?php foreach($listaFac as $f) { echo "<option value='".$f['CodFaculdade']."'>".$f['Nome']."</option>"; } ?>
                        </select>
                    </div>

                    <div>
                        <label>Código da Disciplina de Destino (Ex: AE1PM):</label><br>
                        <input type="text" name="disc_destino" required placeholder="Ex:AE1PM">
                    </div>

                    <div>
                        <label>Ano de Aprovação (Ano Letivo):</label><br>
                        <input type="number" name="ano_aprovacao" value="<?php echo date('Y'); ?>" min="2010" max="2030" required>
                    </div>

                    <button type="submit" name="gravar-eq">Gravar Equivalência</button>
                </form>
            </div>
        </main>
    </body>
</html>