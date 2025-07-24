# Tesla Chatbot API - Guia de Execução

## Pré-requisitos

### Para instalação com Docker
- Docker e Docker Compose instalados
- Git (para clonar o repositório)
- Postman ou outra ferramenta para testar APIs REST

### Para instalação sem Docker
- PHP 8.2 ou superior
- Composer
- Git (para clonar o repositório)
- Postman ou outra ferramenta para testar APIs REST

## Passo a Passo para Execução

### 1. Clone o repositório (caso ainda não tenha feito)

```bash
git clone [URL_DO_REPOSITORIO]
cd take-home/tesla-chatbot
```

### 2. Configure as variáveis de ambiente

Crie um arquivo `.env` na raiz do projeto copiando o arquivo `.env.example` (se existir) ou criando um novo com as variáveis abaixo.

⚠️ IMPORTANTE: As chaves de API e outras credenciais são sensíveis e não devem ser compartilhadas ou commitadas no repositório. 
Siga estas etapas para configurar seu ambiente:

1. Obtenha suas próprias chaves de API:
   - Para OPENAI_API_KEY: Acesse https://platform.openai.com/api-keys
   - Para VECTOR_DB_KEY e VECTOR_DB_URL: Configure seu próprio banco de dados vetorial

2. Crie o arquivo `.env` e adicione as seguintes variáveis (substituindo os valores pelos seus próprios):

```
APP_NAME=TeslaChatbot
APP_ENV=local
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Importante: Substitua os valores abaixo pelos seus próprios
OPENAI_API_KEY=your_openai_api_key_here
VECTOR_DB_KEY=your_vector_db_key_here
VECTOR_DB_URL=your_vector_db_url_here
```

### 3. Instalação e Execução

#### 3.1 Usando Docker

```bash
docker-compose build
docker-compose up
```

Ou em um único comando:

```bash
docker-compose up --build
```

Aguarde até que a aplicação esteja rodando. Você verá uma mensagem indicando que o servidor está disponível em http://0.0.0.0:8000.

#### 3.2 Instalação Local (sem Docker)

1. Instale as dependências do projeto:
```bash
composer install
```

2. Gere a chave da aplicação:
```bash
php artisan key:generate
```

3. Inicie o servidor local:
```bash
php artisan serve
```

O servidor estará disponível em http://127.0.0.1:8000.

### 3.3 Executando os Testes

O projeto inclui testes unitários e de integração. Para executar os testes:

```bash
# Executa todos os testes
php artisan test

# Executa testes com cobertura (requer Xdebug instalado)
php artisan test --coverage

# Executa testes específicos
php artisan test --filter=TestClassName

# Executa testes em paralelo para maior velocidade
php artisan test --parallel
```

Os testes incluem:
- Testes unitários para o serviço de conversação
- Testes de integração para as chamadas da API
- Testes de casos de borda e validação de entrada

### 4. Testando a API

A API estará disponível em `http://localhost:8000`.

#### Usando o Postman

1. Abra o Postman
2. Crie uma nova requisição POST
3. Defina a URL para `http://localhost:8000/api/conversations/completions`
4. Na aba "Headers", adicione:
   - `Content-Type: application/json`
5. Na aba "Body", selecione "raw" e escolha o formato JSON, então adicione:

```json
{
    "helpdeskId": 123456,
    "projectName": "tesla_motors",
    "messages": [
        {
            "role": "USER",
            "content": "Hello! How long does a Tesla battery last before it needs to be replaced?"
        }
    ]
}
```

6. Clique em "Send" e você deverá receber uma resposta semelhante a esta:

```json
{
    "messages": [
        {
            "role": "USER",
            "content": "Hello! How long does a Tesla battery last before it needs to be replaced?"
        },
        {
            "role": "AGENT",
            "content": "Hello! How can I assist you today? I'm Claudia, your Tesla support assistant 😊\nTesla batteries are designed to last many years; the vehicle will notify you if maintenance is needed! Let me know if you have more questions! 🚗⚡"
        }
    ],
    "handoverToHumanNeeded": false,
    "sectionsRetrieved": [
        { "score": 0.6085123, "content": "How do I know if my Tesla battery needs replacement? Tesla batteries are designed to last many years; the vehicle will notify you if maintenance is needed." },
        { "score": 0.5785547, "content": "What is Tesla's battery warranty? Tesla's battery warranty typically lasts for 8 years or about 150,000 miles, depending on the model." }
    ]    
}
```

#### Usando curl

Você também pode testar usando curl:

```bash
curl -X POST http://localhost:8000/api/conversations/completions \
  -H "Content-Type: application/json" \
  -d '{
    "helpdeskId": 123456,
    "projectName": "tesla_motors",
    "messages": [
        {
            "role": "USER",
            "content": "Hello! How long does a Tesla battery last before it needs to be replaced?"
        }
    ]
}'
```

### 5. Testando diferentes cenários

#### Testando a clarificação
Envie uma mensagem vaga ou sem contexto suficiente:

```json
{
    "helpdeskId": 123456,
    "projectName": "tesla_motors",
    "messages": [
        {
            "role": "USER",
            "content": "How does it work?"
        }
    ]
}
```

#### Testando o handover para especialista humano
Para testar o handover para um especialista humano, você pode tentar perguntas complexas ou técnicas que podem acionar conteúdo do tipo N2 no banco de dados vetorial.

### 6. Encerrando a aplicação

Quando terminar os testes, você pode encerrar a aplicação pressionando `Ctrl+C` no terminal onde está rodando o Docker Compose, ou executando:

```bash
docker-compose down
```

## Solução de problemas

### A API não está respondendo

#### Para instalação com Docker
1. Verifique se o contêiner Docker está rodando:
   ```bash
   docker ps
   ```

2. Verifique os logs do contêiner:
   ```bash
   docker-compose logs app
   ```

#### Para instalação local
1. Verifique se o servidor Laravel está rodando:
   ```bash
   ps aux | grep artisan
   ```

2. Verifique os logs da aplicação:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Certifique-se que todas as dependências estão instaladas:
   ```bash
   composer install --no-dev
   ```

3. Certifique-se de que está usando o prefixo correto da API:
   - Todas as rotas da API devem ser acessadas com o prefixo `/api`
   - Exemplo: `http://localhost:8000/api/conversations/completions`

### Erros nas chamadas da API

1. Verifique se as chaves da API da OpenAI e do Vector DB estão corretas no arquivo `.env`
2. Verifique se o formato da sua requisição está correto
3. Verifique os logs da aplicação para mais detalhes sobre possíveis erros
