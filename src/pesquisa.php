<?php
if(isset($_POST['pesquisa'])) {
    $origem = trim($_POST['origem_nome']);
    $destino = trim($_POST['destino_nome']);
    $curso = trim($_POST['curso']);
    $ano = isset($_POST['ano_curso']) ? trim($_POST['ano_curso']) : '';
    $semestre = trim($_POST['semestre']);
} else {
    header("Location: index.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Resultados da Pesquisa</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header>
            <h1>Erasmus Buddie</h1>
            <p>Resultados da compatibilidade</p>
        </header>
        <main style="padding: 20px; text-align: center;">
            <h2> Equivalências para <?php echo htmlspecialchars($destino); ?></h2>
            <p> Ano do Curso: <?php echo htmlspecialchars($ano); ?>º | Semestre: <?php echo htmlspecialchars($semestre); ?>º</p>
            <br>

            <?php require_once('bdERASMUS.php');
            $pesquisa = new Pesquisa();
            $resultados = $pesquisa->obterDados($origem, $destino);
            
            if (!empty($resultados)) {
                echo "<table border='1' style='margin: 0 auto; border-collapse: collapse; width: 80%; text-align: left;'>";
                echo "<tr style='background-color: #f2f2f2;'>
                        <th style='padding: 10px;'>Cadeira (Destino)</th>
                        <th style='padding: 10px;'>Cadeira (Origem)</th>
                        <th style='padding: 10px;'>Ano</th>
                        <th style='padding: 10px;'>Fonte dos Dados</th>
                      </tr>";

                foreach ($resultados as $linha) {
                    echo "<tr>";
                    
                    // Protegemos cada dado a ser impresso
                    $cadeira_destino = $linha['Disciplina_Destino'] ?? ($linha['Cadeira_Destino'] ?? '---');
                    $cadeira_origem = $linha['Disciplina_Origem'] ?? ($linha['Cadeira_Origem'] ?? '---');
                    $ano_aprov = $linha['Ano_Aprovacao'] ?? '---';
                    $fonte = isset($linha['Nome_Origem']) ? 'Sugestão Inteligente (IA)' : 'Aprovado (Base de Dados)';

                    echo "<td style='padding: 10px;'>" . htmlspecialchars($cadeira_destino) . "</td>";
                    echo "<td style='padding: 10px;'>" . htmlspecialchars($cadeira_origem) . "</td>";
                    echo "<td style='padding: 10px;'>" . htmlspecialchars($ano_aprov) . "</td>";
                    echo "<td style='padding: 10px;'>" . htmlspecialchars($fonte) . "</td>";
                    
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>Não foram encontradas equivalências nem dados no site.</p>";
            }
            $pesquisa->fecharBDErasmus();
            ?>
            <br><br>
            <a href="index.php"><button>Fazer Nova Pesquisa</button></a>
        </main>
    </body>
</html>