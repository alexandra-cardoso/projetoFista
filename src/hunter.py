import sys
from ddgs import DDGS
import requests
from pypdf import PdfReader
import io
import time
import os
import json
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
        
        tentativas_maximas = 3
        for tentativa in range(tentativas_maximas):
            try:
                with DDGS() as ddgs:
                    results = list(ddgs.text(query, max_results=5))

                if results:
                    palavras_prob = ["palermo", "bilateral", "agreement", "signed", "cz_brno"]
                    for result in results:
                        link = result['href']
                        if ".pdf" in link.lower() and not any(palavra in link.lower() for palavra in palavras_prob):
                            return link
                    print("⚠️ O robô encontrou PDFs, mas eram acordos bilaterais inválidos.")
                return None
            except Exception as e:
                print(f"⚠️ Soluço da DuckDuckGo (Tentativa {tentativa + 1})...")
                if tentativa < tentativas_maximas - 1:
                    time.sleep(3)
                else:
                    return None
        
    def read_link(self, url):
        try:
            response = requests.get(url, headers=self.headers, timeout=20)
            response.raise_for_status()

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
        
    def extract_data(self, initial_text, ano_aluno, semestre_aluno, curso_aluno):
        print("🧠 A enviar texto para a Inteligência Artificial...")
        prompt = f"""
        Tu és um conselheiro académico especialista em mobilidade Erasmus.
        O aluno que te pede ajuda quer fazer Erasmus no {ano_aluno}º ano, {sem_aluno}º semestre.

        Analisa o catálogo de disciplinas estrangeiras extraído deste PDF e sugere uma lista de cadeiras adequadas.

        Regras muito estritas:
        1. Devolve APENAS um array JSON válido, sem formatação ```json.
        2. Filtra e sugere APENAS disciplinas que correspondam ou se relacionem ao curso pedido (Curso {curso_aluno}). Se fizer sentido para o percurso, podes incluir cadeiras de anos maiores que o ano pedido, mas NUNCA disciplinas de anos anteriores (Ano {ano_aluno}).
        3. Regras de Tradução de Tempo Europeu:
           - "Winter" (Inverno) ou "Autumn" (Outono) = SEMPRE 1º Semestre.
           - "Summer" (Verão) ou "Spring" (Primavera) = SEMPRE 2º Semestre.
           - Semestres contínuos: 1 e 2 = 1º ano; 3 e 4 = 2º ano; 5 e 6 = 3º ano.
        4. Cada objeto no array DEVE ter estas chaves exatas:
           - "codigo": string (O código da cadeira. Se não existir, cria uma sigla de 5 letras).
           - "nome": string (O nome da disciplina).
           - "ects": inteiro (O número de créditos ECTS).
           - "motivo": string (Uma frase curta a explicar porque esta cadeira é uma boa opção para este semestre).
        5. Se não encontrares disciplinas compatíveis, devolve: []

        Texto da Universidade:
        {initial_text}
        """

        url_api = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=" + API_KEY
        dados_post = {"contents": [{"parts": [{"text": prompt}]}]}

        tentativas_maximas = 3
        for tentativa in range(tentativas_maximas):
            try: 
                response = requests.post(url_api, headers={'Content-Type': 'application/json'}, json=dados_post)
                if response.status_code in [500, 503]:
                    print(f"⚠️ A Google está sobrecarregada. Tentativa {tentativa + 1} de {tentativas_maximas}...")
                    if tentativa < tentativas_maximas - 1:
                        time.sleep(5)
                        continue
                    else:
                        return None
                elif response.status_code != 200:
                    print(f"\n🚨 O GOOGLE REJEITOU O PEDIDO (Erro {response.status_code})")
                    return None
                
                dados = response.json()
                if 'candidates' in dados and len(dados['candidates']) > 0:
                    return dados['candidates'][0]['content']['parts'][0]['text']
                else:
                    return "[]"
            except Exception as e:
                print(f"⚠️ Erro de rede ao falar com a IA: {e}")
                if tentativa < tentativas_maximas - 1:
                    time.sleep(5)
                else:
                    return None
        return None

if __name__ == "__main__":
    print("Iniciando pesquisa")
    try:
        ano_escolhido = int(sys.argv[1])
        sem_escolhido = int(sys.argv[2])
        curso_escolhido = sys.argv[3]
    except IndexError:
        ano_escolhido=1
        sem_escolhido=1
        curso_escolhido="Engenharia Informática"
    
    ai = AIHunter()
    dominio_teste = "unipi.it"

    link = ai.search_link(dominio_teste)

    if link:
        print(f"link encontrado: {link}")
        time.sleep(1)
        text = ai.read_link(link)
        
        if text and len(text.strip()) > 0:
            result_json = ai.extract_data(text, ano_escolhido, sem_escolhido, curso_escolhido)
            if result_json and result_json != "[]":
                print(result_json)
            else:
                print("[]")
        else:
            print("falha. não consigo extrair o texto")
    else:
        print("falha. o ddg n devolveu nenhum link")