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
        
    def extract_data(self, initial_text, ano_aluno, sem_aluno, curso_aluno):
        #print("A enviar texto para Gemini")
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
        dados_post = {
            "contents": [{"parts": [{"text": prompt}]}]
        }

        max_tries = 3
        for tentativa in range(max_tries):
            try: 
                response = requests.post(url_api, headers={'Content-Type': 'application/json'}, json=dados_post)
                
                if response.status_code in [500, 503]:
                    if tentativa < max_tries - 1:
                        time.sleep(5)
                        continue
                    else:
                        return None
                          
                elif response.status_code != 200:
                    #print(f"\n🚨 O GOOGLE REJEITOU O PEDIDO (Erro {response.status_code})")
                    #print(response.text)
                    return None
                
                dados=response.json()

                if 'candidates' in dados and len(dados['candidates']) > 0:
                    text_response = dados['candidates'][0]['content']['parts'][0]['text']
                    return text_response.replace("```json", "").replace("```", "").strip()
                else:
                    return "[]"
            except Exception as e:
                #print(f"Erro ao falar com a IA: {e}")
                return None
        return None


if __name__ == "__main__":
    try:
        dominio_teste = sys.argv[1]
        ano_escolhido = int(sys.argv[2])
        semestre_escolhido = int(sys.argv[3])
        curso_escolhido = sys.argv[4]
    except IndexError:
        dominio_teste = ""
        ano_escolhido = 1
        semestre_escolhido = 1
        curso_escolhido = ""
    
    ai = AIHunter()
    link = ai.search_link(dominio_teste)

    if link:
        text = ai.read_link(link)
        
        if text and len(text.strip()) > 0:
            result_json = ai.extract_data(text, ano_escolhido, semestre_escolhido, curso_escolhido)
            if result_json and result_json != "[]":
                print(result_json)
            else:
                print("[]")
        else:
            print("[]")
    else:
        print("[]")
