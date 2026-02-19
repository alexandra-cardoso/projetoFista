<?php
class BDErasmus {
    var $conn;
    function ligarBD() {
        $this->conn = mysqli_connect("mariadb", "root","maria","ERASMUS");
        if(!$this->conn) return -1;
    }
    function executarSQL($sql_command) {
        $resultado = mysqli_query($this->conn, $sql_command);
        return $resultado;
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
        $this -> db_erasmus = new BDErasmus;
        $this->db_erasmus->ligarBD();
    }

    function novaFaculdade($codEscola, $nome, $pais, $url) {
        $sql = "INSERT INTO Faculdade (CodFaculdade, Nome, Pais, URL) VALUES ($codEscola, '$nome', $pais, '$url')";
        $this->db_erasmus->executarSQL($sql);
    }
    
    function novaEquivalencia($disc_origem, $disc_destino, $ano) {
        $sql = "INSERT INTO Equivalencia(id, Cadeira_Origem, Cadeira_Destino, Ano_Aprovacao) VALUES(NULL, '$disc_origem', '$disc_destino', '$ano')";
        $this->db_erasmus->executarSQL($sql);
    }

    function obterURLFaculdade($nome) {
        $sql = "SELECT URL FROM Faculdade WHERE Nome = '$nome' LIMIT 1";
        $rs = $this->db_erasmus->executarSQL($sql);
        
        if($rs && mysqli_num_rows($rs) > 0) {
            $linha = mysqli_fetch_assoc($rs);
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
            while($row = mysqli_fetch_assoc($result)) {
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
            while($row = mysqli_fetch_assoc($result)) {
                $lista[] = $row;
            }
        }
        return $lista;
    }
    function obterCursos() {
        $lista = [];
        $result = $this->db_erasmus->executarSQL("SELECT Nome FROM Curso");
        if($result) {
            while($row = mysqli_fetch_assoc($result)) {
                $lista[] = $row;
            }
        }
    }
    
    function procurarNoSQL($origem, $destino) {
        $ano_atual = date("Y");
		$result_set = $this->db_erasmus->executarSQL("SELECT e.*, f1.Nome, f2.Nome
		FROM Equivalencia e
        INNER JOIN Faculdade f1 ON e.Faculdade_Origem = f1.CodFaculdade
        INNER JOIN Faculdade f2 ON e.Faculdade_Destino = f2.CodFaculdade
		WHERE f1.Nome = '$origem' AND f2.Nome = '$destino' AND e.Ano_Aprovacao <= $ano_atual");
        
        $lista_results = [];
        if($result_set && mysqli_num_rows($result_set) > 0) {
            while($row = mysqli_fetch_assoc($result_set)) {
                $lista_results[] = $row;
            }
            return $lista_results;
        } else {
            return null;
        }
    }

    function IA($destino) {
        $sql_url = "SELECT URL FROM Faculdade WHERE Nome = '$destino'";
        $resultado = $this->db_erasmus->executarSQL($sql_url);
        if($resultado) {
            $url_site = $resultado[0]['URL'];
            return $this->pesquisar($url_site);
        } else {
            return "Erro: não tenho site desta faculdade disponível";
        }
    }

    function pesquisar($url) {
        $opcoes = ["http" => ["header" => "User-Agent: Mozilla/5.0 (compatible; ErasmusBot/1.0)", "timeout"=>10 ]];
        $contexto = stream_context_create($opcoes);
        $html = @file_get_contents($url, false, $contexto);

        if ($html === false) return ['Cadeira_Destino' => 'Erro: Não consegui ler o site '.$url]; // Se falhar, devolve vazio

        $textoLimpo = strip_tags($html);
        $textoLimitado = substr($textoLimpo, 0, 10000); 

        $apiKey = 'AIzaSyBtdKjLNQHMYIGDFCZehzeIO_4DsJWKTMQ'; //CHAVE DO GEMINI DA XANA
        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

        $prompt = "Age como um extrator de dados JSON. Analisa este texto de um site universitário e extrai as disciplinas e ECTS. \n" . //para mudar ainda, quero que ele receba uma lista das disciplinas que a pessoa iria ter e faça possíveis equivalências
        "Regras obrigatórias:\n".
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