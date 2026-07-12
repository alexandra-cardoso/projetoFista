from ddgs import DDGS
import requests
from bs4 import BeautifulSoup
import time
import os
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
        query = f'site:{dominio}/en "course catalogue" OR "list of courses" erasmus'
        try:
            with DDGS() as ddgs:
                results=list(ddgs.text(query, max_results=5))

            if results:
                for result in results:
                    link=result['href']
                    real_dominio=link.split('/')[2]
                    if real_dominio == dominio or real_dominio.endswith('.' + dominio):
                        return link #retorna melhor link. do top 3 q ele encontra, escolhe o que acha mais correspondente- deve ter a informação necessária
                return results[0]['href']
            return None
        except Exception as e:
            print(f"Erro na pesquisa DuckDuckGo: {e}")
            return None
        
    def read_link(self, url):
        try:
            response = requests.get(url, headers=self.headers, timeout=15)
            response.raise_for_status() # vê se deu erro

            soup = BeautifulSoup(response.text, 'html.parser')

            for a_tag in soup.find_all('a', href=True):
                text = a_tag.get_text(strip=True).lower()
                if "course" in text or "catalogue" in text or "subject" in text:
                    sec_link = a_tag['href']
                    if sec_link.startswith('/'):
                        from urllib.parse import urljoin
                        sec_link = urljoin(url, sec_link)
                    print(f"robô encontrou atalho: {sec_link}")
                    new_response = requests.get(sec_link, headers=self.headers, timeout=15)
                    new_soup = BeautifulSoup(new_response.text, 'html.parser')

                    text_final = new_soup.get_text(separator=' ', strip=True)
                    return text_final[:15000]
            #se n encontrar mais links
            text = soup.get_text(separator=' ', strip=True)
            final_text = text[:15000]

            return final_text
        except Exception as e:
            print(f"Erro ao tentar ler o site: {e}")
            return None
        
    def extract_data(self, initial_text):
        print("A enviar texto para Gemini")
        prompt = f"""
        Tu és um assistente de extração de dados. O texto abaixo foi extraído do site de uma universidade para alunos de Erasmus.
        A tua missão é encontrar todas as disciplinas/cadeiras disponíveis e os seus respetivos créditos ECTS.

        Regras muito estritas:
        1. Devolve APENAS um array JSON válido.
        2. Cada objeto no array deve ter exatamente duas chaves: "disciplina" (string) e "ects" (número inteiro).
        3. Não inclua texto antes nem depois do JSON. Não uses a formatação de markdown ```json.
        4. Se não encontrares disciplinas, devolve um array vazio: []

        Texto da Universidade:
        {initial_text}
        """

        url_api = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=" + API_KEY
        dados_post = {
            "contents": [{"parts": [{"text": prompt}]}]
        }

        try: 
            response = requests.post(url_api, headers={'Content-Type': 'application/json'}, json=dados_post)
            #response.raise_for_status()
            if response.status_code != 200:
                print(f"\n🚨 O GOOGLE REJEITOU O PEDIDO (Erro {response.status_code})")
                print("A justificação oficial do Google foi:")
                print(response.text) # Isto vai mostrar-nos se a chave é inválida ou se o modelo não existe!
                return None
            
            dados=response.json()

            if 'candidates' in dados and len(dados['candidates']) > 0:
                text = dados['candidates'][0]['content']['parts'][0]['text']
                return text
            else:
                return "IA não devolveu candidatos validos"
        except Exception as e:
            print(f"Erro ao falar com a IA: {e}")
            return None


if __name__ == "__main__":
    print("Iniciando pesquisa")
    ai = AIHunter()
    dominio_teste = "vut.cz"
    print(f"A procurar links para: {dominio_teste}")
    link = ai.search_link(dominio_teste)
    if link:
        print(f"link encontrado: {link}")
        time.sleep(2)
        print("A extrair texto da página...")
        text = ai.read_link(link)
        
        if text:
            print("\n--- AMOSTRA DO TEXTO EXTRAÍDO ---")
            result_json = ai.extract_data(text)
            print("🎓 RESULTADO FINAL EXTRAÍDO PELA IA:")
            print(result_json)
            
        else:
            print("falha. não consigo extrair o texto")
    else:
        print("falha. o ddg n devolveu nenhum link")