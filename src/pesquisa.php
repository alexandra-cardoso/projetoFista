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
        <nav class="top-menu">
            <div class ="logo-texto">Erasmus Buddie</div>
            <div class="links">
                <a href="index.php">Pesquisar</a>
                <a href="submeter.php">Partilhar Equivalências</a>
            </div>
        </nav>
        <main style="padding: 20px; text-align: center; color: white; width: 85%;">
            <h2> Equivalências para <?php echo htmlspecialchars($destino); ?></h2>
            <p> Ano do Curso: <?php echo htmlspecialchars($ano); ?>º | Semestre: <?php echo htmlspecialchars($semestre); ?>º</p>
            <br>

            <?php require_once('bdERASMUS.php');
            $pesquisa = new Pesquisa();
            $resultados = $pesquisa->obterDados($origem, $destino);
            
            $diretos=[];
            $secundarios=[];

            if (!empty($resultados)) {
                foreach ($resultados as $linha) {
                    $ano_org = $linha['AnoDiscOrigem'] ?? null;
                    $sem_org = $linha['SemestreDiscOrigem'] ?? null;
                    $ano_dest = $linha['AnoDiscDestino'] ?? null;
                    $sem_dest = $linha['SemestreDiscDestino'] ?? null;

                    $ano_disc = $ano_origem ?? $ano_dest;
                    $sem_disc = $sem_org ?? $sem_dest;

                    if (($ano_org == $ano && $sem_org == $semestre) || ($ano_dest == $ano && $sem_dest == $semestre)) {
                        $diretos[] = $linha;
                    } else {
                        if($ano_disc > $ano || ($ano_disc == $ano && $sem_disc > $semestre)) {
                            $secundarios[] = $linha;
                        }
                    }
                }
            }

            function desenharTabela($lista) {
                echo "<table border='1' style='margin: 0 auto 30px auto; border-collapse: collapse; width: 90%; text-align: left; background-color: rgba(255,255,255,0.1);'>";
                echo "<tr style='background-color: rgba(0,0,0,0.3); color: white;'>
                        <th style='padding: 10px;'>Cadeira (Destino)</th>
                        <th style='padding: 10px;'>Cadeira (Origem)</th>
                        <th style='padding: 10px;'>Ano</th>
                        <th style='padding: 10px;'>Fonte dos Dados</th>
                      </tr>";

                foreach ($lista as $linha) {
                    echo "<tr>";
                    
                    // Protegemos cada dado a ser impresso
                    $cadeira_destino = !empty($linha['NomeDiscDestino']) ? $linha['NomeDiscDestino'] : ($linha['Disciplina_Destino'] ?? ($linha['Cadeira_Destino'] ?? '---'));
                    $cadeira_origem = !empty($linha['NomeDiscOrigem']) ? $linha['NomeDiscOrigem'] : ($linha['Disciplina_Origem'] ?? ($linha['Cadeira_Origem'] ?? '---'));
                    $ano_disc = $linha['AnoDiscOrigem'] ?? ($linha['AnoDiscDestino'] ?? '?');
                    $sem_disc = $linha['SemestreDiscOrigem'] ?? ($linha['SemestreDiscDestino'] ?? '?');
                    $info_tempo = ($ano_disc !== '?' && $sem_disc !== '?') ? "{$ano_disc}º Ano / {$sem_disc}º Semestre" : "---";
                    
                    $fonte = isset($linha['Nome_Origem']) ? 'Sugestão Inteligente (IA)' : 'Aprovado (Base de Dados)';

                    echo "<td style='padding: 10px;'>" . htmlspecialchars($cadeira_destino) . "</td>";
                    echo "<td style='padding: 10px;'>" . htmlspecialchars($cadeira_origem) . "</td>";
                    echo "<td style='padding: 10px;'>" . htmlspecialchars($info_tempo) . "</td>";
                    echo "<td style='padding: 10px;'>" . htmlspecialchars($fonte) . "</td>";
                    
                    echo "</tr>";
                }
                echo "</table>";
            }

            if (!empty($diretos)) {
                echo "<h3 style='color: #lightgreen; text-align: left; width: 90%; margin: 0 auto 10px auto;'>Equivalências Ideais para o teu Semestre:</h3>";
                desenharTabela($diretos);
            } else {
                echo "<p style='color: #FFD700;'>Não foram encontradas equivalências diretas registadas para este ano/semestre.</p><br>";
            }

            //cadeiras noutro semestre/ano
            if (!empty($outros)) {
                echo "<hr style='width: 90%; border: 0; border-top: 1px solid rgba(255,255,255,0.2); margin: 20px auto;'>";
                echo "<h3 style='color: #FFD700; text-align: left; width: 90%; margin: 20px auto 10px auto;'>Outras equivalências para se estiveres a pensar em ir noutra altura...</h3>";
                desenharTabela($outros);
            }

            $pesquisa->fecharBDErasmus();
            ?>
            <br><br>
            <a href="index.php"><button>Fazer Nova Pesquisa</button></a>
        </main>
    </body>
</html>