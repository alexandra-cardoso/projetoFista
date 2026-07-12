from ddgs import DDGS
import requests
from pypdf import PdfReader
import io
import time
import os
import json
import mysql.connector #ligar a mariadb
from dotenv import load_dotenv

load_dotenv()
API_KEY = os.getenv("GEM_API_KEY")
API_KEY = API_KEY.strip() if API_KEY else ""

class AIHunter:
    def __init__(self):
        self.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
        }

    def search_link(self, dominio):
        query = f'site:{dominio} ext:pdf "course" "ects" "erasmus" "incoming" -"bilateral" -"agreement"'
        max_tries = 3
        for tentativa in range(max_tries):
            try:
                with DDGS() as ddgs:
                    results = list(ddgs.text(query, max_results=5))

                if results:
                    palavras_prob=["palermo", "bilateral", "agreement", "signed"]
                    for result in results:
                        link=result['href']
                        if ".pdf" in link.lower() and not any(palavra in link.lower() for palavra in palavras_prob):
                            return link
                return None
            except Exception as e:
                print(f"Erro na pesquisa DuckDuckGo: {e}")
                return None
        
    def read_link(self, url):
        try:
            response = requests.get(url, headers=self.headers, timeout=20)
            response.raise_for_status() # vê se deu erro

            pdf_file = io.BytesIO(response.content)
            reader = PdfReader(pdf_file)

            final_text = ""
            for i, page in enumerate(reader.pages[:15]):
                page_text = page.extract_text()
                if page_text:
                    final_text += f"\n--- PÁGINA {i+1} ---\n" + page_text
            
            return final_text[:20000]
            
        except Exception as e:
            print(f"Erro ao tentar ler o PDF: {e}")
            return None
        
    def extract_data(self, initial_text):
        print("A enviar texto para Gemini")
        prompt = f"""
        Tu és um assistente especialista em Engenharia de Dados e processamento de documentos universitários. 
        O texto abaixo foi extraído diretamente de um documento PDF oficial de Erasmus (Course Catalogue).
        A tua missão é encontrar as disciplinas disponíveis e formatá-las num JSON rigoroso.

        Regras muito estritas:
        1. Devolve APENAS um array JSON válido, sem texto antes ou depois, sem formatação ```json.
        2. Cada objeto no array DEVE ter estas chaves exatas:
           - "codigo": string (O código/ID da cadeira. Se não existir, cria uma sigla de 5 a 8 letras baseada no nome).
           - "nome": string (O nome da disciplina).
           - "ano": inteiro (O ano letivo da cadeira. Se não souberes, mete null).
           - "semestre": inteiro (1 ou 2. Se não souberes, mete 1 por defeito).
           - "ects": inteiro (O número de créditos).
           - "curso": string (O nome do curso a que pertence. Se não souberes, escreve "Geral Erasmus").
        3. Se não encontrares disciplinas claras, devolve: []

        Texto da Universidade:
        {initial_text}
        """

        url_api = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=" + API_KEY
        dados_post = {
            "contents": [{"parts": [{"text": prompt}]}]
        }

        try: 
            response = requests.post(url_api, headers={'Content-Type': 'application/json'}, json=dados_post)
            
            if response.status_code != 200:
                print(f"\n🚨 O GOOGLE REJEITOU O PEDIDO (Erro {response.status_code})")
                return None
            
            dados=response.json()

            if 'candidates' in dados and len(dados['candidates']) > 0:
                return dados['candidates'][0]['content']['parts'][0]['text']
            else:
                return "[]"
        except Exception as e:
            print(f"Erro ao falar com a IA: {e}")
            return None
        
    def save_to_memory(self, json_string, cod_faculdade):
        try:
            info = json.loads(json_string)
            if not info:
                print("JSON vazio. Nada para gravar.")
                return
            
            conn = mysql.connector.connect(
                host=os.getenv("DB_HOST", "localhost"),
                port=6033,
                user=os.getenv("DB_USER", "root"),
                password = os.getenv("DB_PASSWORD", "maria"),
                database = os.getenv("DB_NAME", "ERASMUS")
            )
            cursor = conn.cursor()

            contador = 0
            for disc in info:
                nome_curso = disc.get("curso", "Geral Erasmus")
                cursor.execute("SELECT CursoID FROM Curso WHERE Nome =  %s AND CodFaculdade = %s", (nome_curso, cod_faculdade))
                result_curso = cursor.fetchone()

                if result_curso: #se o curso já existe
                    curso_id = result_curso[0]
                else: #se nao, cria-se
                    cursor.execute("INSERT INTO Curso (Nome, CodFaculdade) VALUES (%s,%s)", (nome_curso, cod_faculdade))
                    curso_id = cursor.lastrowid
                
                cod_disc = str(disc.get("codigo", ""))[:15]
                sql_disc= """
                    INSERT IGNORE INTO Disciplina (DisciplinaID, Nome, Ano, Semestre, ECTS, CursoID)
                    VALUES (%s, %s, %s, %s, %s, %s)
                """
                values_disc = (
                    cod_disc,
                    disc.get("nome", "Desconhecido"),
                    disc.get("ano"),
                    disc.get("semestre", 1),
                    disc.get("ects", 0),
                    curso_id
                )
                cursor.execute(sql_disc, values_disc)
                if cursor.rowcount > 0:
                    contador += 1
            
            conn.commit()
            cursor.close()
            conn.close()
            print(f"Sucesso. Inseridas {contador} novas disc para a faculdade {cod_faculdade}.")
        
        except json.JSONDecodeError:
            print("Erro: não temos um json válido.")
        except mysql.connector.Error as err:
            print(f"Erro de BD: {err}")


if __name__ == "__main__":
    print("Iniciando pesquisa")
    ai = AIHunter()

    dominio_teste = "unipi.it"
    cod_faculdade_bd= "I PISA01"

    link = ai.search_link(dominio_teste)

    if link:
        print(f"link encontrado: {link}")
        time.sleep(1)
        text = ai.read_link(link)
        
        if text and len(text.strip()) > 0:
            result_json = ai.extract_data(text)
            print("🎓 RESULTADO FINAL EXTRAÍDO PELA IA:")
            print(result_json)
            
        else:
            print("falha. não consigo extrair o texto")
    else:
        print("falha. o ddg n devolveu nenhum link")