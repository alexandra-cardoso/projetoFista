<?php
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : 1;
$semestre = isset($_GET['semestre']) ? (int)$_GET['semestre'] : 1;
$curso = isset($_GET['curso']) ? trim($_GET['curso']) : '';
$ano = isset($_GET['ano']) ? trim($_GET['ano']) : '';

if(empty($destino) || empty($curso)) {
    echo "<p style='color: #ff6b6b; width: 90%; margin: 0 auto;'>Dados inválidos para consulta da IA.</p>";
    exit();
}

$comando = "PYTHONIOENCONDING=utf-8 python3 hunter.py " . escapeshellarg($destino) . " " . escapeshellarg($ano) . " " . escapeshellarg($semestre) . " " . escapeshellarg($curso) . " 2>&1";
exec($comando, $output_array);
$result_python = implode("\n", $output_array);

if(empty($result_python)) $result_python = "[]";
$sug = json_decode($result_python, true);

if(is_array($sug) && count($sug) > 0) {
    echo "<table border='1' style='margin: 0 auto 30px auto; border-collapse: collapse; width: 90%; text-align: left; background-color: rgba(255,255,255,0.1);'>";
    echo "<tr style='background-color: rgba(79, 158, 254, 0.3); color: white;'>
            <th style='padding: 10px;'>Código</th>
            <th style='padding: 10px;'>Nome da Cadeira</th>
            <th style='padding: 10px;'>ECTS</th>
            <th style='padding: 10px;'>Motivo da Sugestão</th>
          </tr>";
    foreach($sug as $cadeira) {
        echo "<tr>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($cadeira['codigo']) . "</td>";
        echo "<td style='padding: 10px; font-weight: bold;'>" . htmlspecialchars($cadeira['nome']) . "</td>";
        echo "<td style='padding: 10px;'>" . htmlspecialchars($cadeira['ects']) . "</td>";
        echo "<td style='padding: 10px; font-style: italic;'>" . htmlspecialchars($cadeira['motivo']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: #FFD700; width: 90%; margin: 0 auto;'>A IA não encontrou equivalências compatíveis com as regras para este curso.</p><br>";
}
?>