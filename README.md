# 🎯 Caçador de Ofertas Bot

Um bot autônomo (Daemon) desenvolvido em PHP puro para rastrear, filtrar e notificar em tempo real jogos gratuitos e promoções relâmpago.

O sistema realiza *web scraping* em múltiplas fontes e utiliza o Telegram como interface de notificação, garantindo que um resgate por tempo limitado nunca seja perdido.

## 🚀 Funcionalidades

* **Scraping Multicanal:** Varredura contínua e assíncrona nas lojas (Steam) e em comunidades de curadoria (r/FreeGameFindings no Reddit).
* **Notificações Push:** Integração direta com a API do Telegram para alertas em tempo real.
* **Filtro Inteligente (Blacklist):** Motor de regras para descartar automaticamente posts indesejados, palavras-chave bloqueadas ou links de domínios indesejados.
* **Prevenção de Duplicatas (Race-Condition Safe):** Delegação da trava de concorrência diretamente para o motor do banco de dados utilizando constraints `UNIQUE` e `INSERT ... ON DUPLICATE KEY UPDATE` para preservar I/O.

## 🛡️ Resiliência e Bypass de WAF

Para garantir estabilidade máxima e contornar bloqueios de Web Application Firewalls (WAF) — como o Cloudflare do Reddit, que barra requisições JSON provenientes de IPs de Data Centers comerciais (Erro 403) — este projeto implementa um fallback arquitetural para leitura via **RSS/XML**. Isso assegura o consumo legítimo de dados públicos sem depender de chaves OAuth de terceiros ou longos processos de aprovação.

## 🛠️ Arquitetura e Tecnologias

Este projeto foi construído focado em leveza e baixo consumo de recursos, ideal para rodar em instâncias de nuvem (Always Free Tier).

* **Linguagem:** PHP 8.2 (CLI)
* **Banco de Dados:** MySQL 8.0 (InnoDB)
* **Comunicação:** cURL (JSON e XPath Parsing)
* **Infraestrutura:** Docker & Docker Compose
* **Deploy:** Oracle Cloud Infrastructure (Linux/Ubuntu)

## ⚙️ Como rodar localmente

Este projeto está 100% conteinerizado. Você não precisa instalar o PHP ou o MySQL na sua máquina.

1. Clone o repositório:
   ```bash
   git clone [https://github.com/SEU_USUARIO/cacador_ofertas.git](https://github.com/SEU_USUARIO/cacador_ofertas.git)
   ```
2. Configure as credenciais:
   Edite os arquivos dentro da pasta config/(telegram.php, database.php) com as suas chaves.
3. Suba a infraestrutura com o Docker:
   ```bash
   docker-compose up -d --build
   ```
O banco de dados e o script PHP iniciarão automaticamente e o seu bot já estará rodando em background!