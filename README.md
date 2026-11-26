# Alertas Meteorológicos

App de alertas meteorológicos (desafio técnico Egocultum). O browser obtém a
localização do utilizador (ou aceita coordenadas escritas à mão), mostra a
previsão dos próximos dias e os alertas de geada, chuva forte e seca.

Dados meteorológicos: [Open-Meteo.com](https://open-meteo.com).

## Como pôr a app a correr

Só precisas de Docker e Docker Compose. Na raiz do repositório:

```bash
docker compose up
```

- Cliente (React): http://localhost:5173
- Servidor (PHP): http://localhost:8000

## Como correr os testes

```bash
docker compose run --rm server sh -c "composer install --no-interaction --quiet && vendor/bin/phpunit"
```

(Se já tiveres corrido `docker compose up` antes, as dependências já estão
instaladas e basta `docker compose run --rm server vendor/bin/phpunit`.)

## Como correr o comando de verificação

Lê `exemplo.json` (dados fictícios, no formato da resposta da Open-Meteo) e
imprime os alertas que as regras geram:

```bash
docker compose run --rm server sh -c "composer install --no-interaction --quiet && php bin/alertas.php /workspace/exemplo.json"
```

(`/workspace` é a raiz do repositório montada dentro do container do
servidor, só para leitura, precisamente para este comando conseguir aceder
ao `exemplo.json`.)

## Decisões principais

- **"Mesma localização" para efeitos de cache**: coordenadas arredondadas a
  2 casas decimais (~1,1 km de lado). É fino o suficiente para distinguir
  parcelas ou localidades próximas (relevante num contexto agrícola, onde
  microclimas variam em poucos km) e grosseiro o suficiente para absorver o
  jitter normal do GPS do browser entre pedidos à mesma localização.
- **Cache em ficheiro, sem base de dados**: cada localização (pela chave
  acima) gera um ficheiro JSON com o valor e a hora em que foi guardado;
  um pedido só repete a chamada à Open-Meteo se o ficheiro tiver mais de 1
  hora. Suficiente para o volume e o escopo deste desafio — uma base de
  dados seria complexidade sem benefício aqui.
- **Falha da Open-Meteo**: se a origem falhar (rede, timeout, HTTP != 200,
  resposta inválida), o servidor tenta servir a última previsão conhecida
  para essa localização, mesmo que o seu TTL já tenha expirado, assinalando
  `stale: true` e uma mensagem explicativa. Só devolve erro sem dados
  nenhuns quando nunca houve cache para essa localização.
- **Servidor em PHP sem framework**: para este escopo, um framework como
  Symfony ou Laravel custaria tempo de arranque sem benefício real; o
  servidor embutido do PHP (`php -S`) e um pequeno front controller são
  suficientes.
- **Sem a extensão curl**: o cliente HTTP (`StreamHttpClient`) usa
  `file_get_contents` com um stream context, o que evita instalar e
  configurar a extensão curl na imagem Docker só para um pedido GET simples.
- **Desenho orientado a testes e a SOLID**: as regras de alerta
  (`FrostAlertRule`, `HeavyRainAlertRule`, `DroughtAlertRule`) implementam
  `AlertRuleInterface` e são agregadas pelo `AlertEvaluator` — acrescentar
  uma regra nova não obriga a alterar as existentes nem o avaliador (Open/
  Closed). O `ForecastService` depende só de `WeatherProviderInterface` e
  `CacheInterface` (Dependency Inversion), o que permite testar toda a
  orquestração (cache, falhas, fallback) sem rede nem sistema de ficheiros.
  Todo o backend foi escrito em TDD: testes e implementação ficaram em
  commits separados ao longo do histórico.
- **React sem bibliotecas extra**: só hooks (`useState`/`useEffect`), sem
  router nem gestor de estado — não se justificam para uma única página.

## Nota sobre uso de IA

Usei o Claude Code ao longo de todo o desenvolvimento: desenho da
arquitetura, escrita dos testes e da implementação em TDD, Dockerfiles,
docker-compose, o cliente React e este README. Revi e corrigi o resultado
em vários pontos em vez de aceitar tudo às cegas — dois exemplos concretos:

- Escrevi um teste (`OpenMeteoClientTest`) com uma fixture que codificava
  `-1.0` em JSON e comparava o resultado com `-1.0`. O teste falhava porque
  `json_encode(-1.0)` em PHP produz `"-1"` (sem parte decimal), que o
  `json_decode` lê como `int`, não `float`. Corrigi a fixture para usar
  valores com parte fracionária (`-1.2`) em vez de aceitar uma asserção
  frágil ou alterar o código de produção para contornar um problema que só
  existia no teste.
- Num dos testes de seca (`DroughtAlertRuleTest`), a fixture inicial só
  tinha 4 dias secos mas a asserção esperava um alerta — a regra exige 5.
  Corrigi a fixture (não a regra, que estava certa) antes de implementar.

## O que faria a seguir com mais tempo

- Guardar locais favoritos numa base de dados (o extra opcional do
  enunciado).
- Testes end-to-end (ex. Playwright) cobrindo o fluxo completo, incluindo a
  recusa de geolocalização.
- Retries com backoff antes de desistir da Open-Meteo.
- Logging estruturado das falhas da origem de dados.
- Testes automáticos para o cliente React (atualmente só verificado
  manualmente).
