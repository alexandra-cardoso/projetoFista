<?php
class BDErasmus {
    var $conn;
    function ligarBD() {
        $this->conn = mysqli_connect("mariadb", "root","maria","ERASMUS");
        if($this->conn->connect_error) die("Falha na ligação: " . $this->conn->connect_error);
    }
    function executarSQL($sql_command) {
        return $this->conn->query($sql_command);
    }
    function numeroTuplos($tabela) {
        $rs = $this->executarSQL("SELECT * FROM $tabela");
        return mysqli_num_rows($rs);
    }
    function fecharBD() {
        mysqli_close($this->conn);
    }
}
class Pesquisa extends BDErasmus {
    var $db_erasmus;
    
    function Pesquisa() {
        $this -> db_erasmus = new BDErasmus();
        $this->db_erasmus->ligarBD();
    }

    function novaFaculdade($codEscola, $nome, $pais, $url) {
        $sql = $this->db_erasmus->conn->prepare("INSERT INTO Faculdade (CodFaculdade, Nome, Pais, URL) VALUES (?, ?, ?, ?)");
        $sql->bind_param("ssss", $codEscola, '$nome', $pais, '$url');
        $sql->execute();$sql->close();
    }
    
    function novaEquivalencia($disc_origem, $disc_destino, $ano) {
        $sql =$this->db_erasmus->conn->prepare("INSERT INTO Equivalencia (Disciplina_Origem, Disciplina_Destino, Ano_Aprovacao) VALUES (?, ?, ?)");
        $sql->bind_param("ssi", $disc_origem, $disc_destino,$ano);
        $sql->execute();$sql->close();
    }

    function obterURLFaculdade($nome) {
        $sql =$this->db_erasmus->conn->prepare("SELECT URL FROM Faculdade WHERE Nome = ? LIMIT 1");
        $sql->bind_param("s", $nome);
        $sql->execute();
        $rs =$sql->get_result();
        
        if($rs && $rs->num_rows > 0) {
            $linha = $rs->fetch_assoc();
            return $linha['URL'];
        }
        return null;
    }

    function obterDados($origem, $destino){
        $dados=$this->procurarNoSQL($origem, $destino);
        if(empty($dados)) {
            $url=$this->obterURLFaculdade($destino);
            if($url) {
                $dados=$this->pesquisar($url);
            } else {
                return [['Cadeira_Destino' => 'Erro: URL não encontrado para '.$destino]];
            }
        }
        return $dados;
    }

    function obterFaculdades() {
        $lista = [];
        $sql="SELECT CodFaculdade, Nome FROM Faculdade ORDER BY Nome ASC";
        $result = $this->db_erasmus->executarSQL($sql);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $lista[] = $row;
            }
        }
        return $lista;
    }
    function obterAnos() {
        $lista = [];
        $sql="SELECT Ano_Aprovacao FROM Equivalencia ORDER BY Ano_Aprovacao ASC";
        $result = $this->db_erasmus->executarSQL($sql);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $lista[] = $row;
            }
        }
        return $lista;
    }
    function obterCursos() {
        $lista = [];
        $result = $this->db_erasmus->executarSQL("SELECT Nome FROM Curso");
        if($result) {
            while($row = $result->fetch_assoc()) {
                $lista[] = $row;
            }
        }
        return $lista;
    }
    
    function procurarNoSQL($origem, $destino) {
        $ano_atual = date("Y");
		$sql = "SELECT e.*, f1.Nome, f2.Nome 
		FROM Equivalencia e
        INNER JOIN Faculdade f1 ON e.Faculdade_Origem = f1.CodFaculdade
        INNER JOIN Faculdade f2 ON e.Faculdade_Destino = f2.CodFaculdade
		WHERE f1.Nome = ? AND f2.Nome = ? AND e.Ano_Aprovacao <= ?";
        $stmt = $this->db_erasmus->conn->prepare($sql);
        $stmt->bind_param("ssi", $origem, $destino, $ano_atual);
        $stmt->execute();$result_set = $stmt->get_result();
        $lista_results = [];
        if($result_set && $result_set->num_rows > 0) {
            while($row = $result_set->fetch_assoc()) {
                $lista_results[] = $row;
            }
            return $lista_results;
        }
        return null;
    }

    function IA($destino) {
        $url = $this->obterURLFaculdade($destino);
        if($url) {
            return $this->pesquisar($url);
        } else {
            return "Erro: não tenho site desta faculdade disponível.";
        }
    }

    function pesquisar($url) {
        $opcoes = ["http" => ["header" => "User-Agent: Mozilla/5.0", "timeout"=>10 ]];
        $contexto = stream_context_create($opcoes);
        $html = @file_get_contents($url, false, $contexto);

        if ($html === false) return ['Cadeira_Destino' => 'Erro: Não consegui ler o site '.$url]; // Se falhar, devolve vazio

        $textoLimpo = strip_tags($html);
        $textoLimitado = substr($textoLimpo, 0, 10000); 

        $apiKey = getenv('GEM_API_KEY');
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

        $prompt = "Age como um extrator de dados JSON. Analisa este texto de um site universitário e extrai as disciplinas e ECTS. \n" . //para mudar ainda, quero que ele receba uma lista das disciplinas que a pessoa iria ter e faça possíveis equivalências
        "Regras obrigatórias:\n" .
        "1. Devolve APENAS um JSON válido.\n" .
        "2. O formato deve ser: [{\"cadeira\": \"Nome\", \"ects\": \"6\"}, ...]\n" .
        "3. Não uses Markdown, não uses ```json, apenas o texto puro.\n\n" .
        "Texto do site:\n" . $textoLimitado;

        $dados_post = [
            "contents" => [ [ "parts" => [ [ "text" => $prompt ]]]]
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados_post));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $resposta_api = curl_exec($ch);
        
        if (curl_errno($ch)) {
            return [['Cadeira_Destino' => 'Erro de conexão à API Google']];
        }
        curl_close($ch);

        $dados_ia = json_decode($resposta_api, true);
        $lista_disciplinas=null;
        if (isset($dados_ia['candidates'][0]['content']['parts'][0]['text'])) {
            $texto_bruto = $dados_ia['candidates'][0]['content']['parts'][0]['text'];
            $texto_limpo_json = str_replace(['```json', '```'], '', $texto_bruto);
            $lista_disciplinas = json_decode($texto_limpo_json, true);
        } 
        $resultado_final = [];
        if (is_array($lista_disciplinas)) {
            foreach($lista_disciplinas as $disciplina) {
                $nome=$disciplina['cadeira'] ?? 'Desconhecido';
                $ects = $disciplina['ects'] ?? '?';

                $resultado_final[]= [
                    'Nome_Destino' => 'Sugestão AI (Via Website)',
                    'Cadeira_Origem'=>'---',
                    'Cadeira_Destino'=>$nome.'('.$ects.' ECTS)',
                    'Ano_Aprovacao'=>date("Y"),
                    'Nome_Origem' => 'Análise Automática'
                ];
            }
        } else {
            $resultado_final[] = ['Cadeira_Destino' => 'A IA leu o site mas não encontrou disciplinas claras'];
        }
        return $resultado_final;
    }

    function fecharBDErasmus() {
        $this->db_erasmus->fecharBD();
    }
}
?>