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
./test.sh
```

Equivalente a `docker compose run --rm server sh -c "composer install --no-interaction --quiet && vendor/bin/phpunit"`.

## Como correr o comando de verificação

Lê um ficheiro no formato da resposta da Open-Meteo e imprime os alertas que
as regras geram. Recebe o nome do ficheiro como parâmetro (por omissão,
`exemplo.json`):

```bash
./exemplo.sh               # usa exemplo.json
./exemplo.sh outro.json    # usa outro ficheiro na raiz do repositório
```

Equivalente a `docker compose run --rm server sh -c "composer install --no-interaction --quiet && php bin/alertas.php /workspace/<ficheiro>"`.
`/workspace` é a raiz do repositório montada dentro do container do
servidor, só para leitura, precisamente para este comando conseguir aceder
ao ficheiro.

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
docker-compose, o cliente React e este README. Revi o resultado e corrigi
o rumo em vários pontos em vez de aceitar tudo às cegas — dois exemplos
concretos:

- A stack inicial ficou a usar versões já desatualizadas por omissão (PHP
  8.3, Node 22, React 18.3, Vite 5.4, PHPUnit 10.5), apesar de tudo
  funcionar sem erros. Pedi para verificar as versões realmente atuais e
  atualizar tudo para as mais recentes estáveis — PHP 8.5 (não a 8.6, que
  tinha saído há só 7 dias), Node 26, React 19.3, Vite 8.3, PHPUnit 13.4 —
  e confirmar que continuava tudo a funcionar.
- Os comandos para correr os testes e o exemplo eram pedidos longos
  `docker compose run --rm server sh -c "..."`. Pedi para criar dois
  scripts (`test.sh`, `exemplo.sh`) só para encurtar esses comandos, sem
  mudar o que fazem.

## O que faria a seguir com mais tempo

- Guardar locais favoritos numa base de dados (o extra opcional do
  enunciado).
- Testes end-to-end (ex. Playwright) cobrindo o fluxo completo, incluindo a
  recusa de geolocalização.
- Retries com backoff antes de desistir da Open-Meteo.
- Logging estruturado das falhas da origem de dados.
- Testes automáticos para o cliente React (atualmente só verificado
  manualmente).
- UI mais rica: animações/transições subtis, estados vazios mais
  trabalhados (ex. ícone/ilustração quando não há alertas), gráfico simples
  da evolução da temperatura e precipitação ao longo dos dias.
- Infraestrutura para produção a maior escala:
  - CI/CD (ex. GitHub Actions) a correr os testes em cada push/PR.
  - CloudFront à frente do cliente, com cache e limites de pedidos por
    origem (rate limiting).
  - ALB a distribuir os pedidos entre várias instâncias do servidor.
  - Fila SQS para conter picos de pedidos e evitar flood da Open-Meteo.
  - SNS para notificar quem pedisse alertas sobre os seus locais
    favoritos (liga à funcionalidade de favoritos acima).

## Limitações conhecidas (de uma revisão de código)

Pedi uma revisão geral do projeto à procura de bugs, vulnerabilidades e
melhorias. A maior parte das correções já está aplicada (ver histórico de
commits); ficou por fazer o que tinha escopo maior do que cabia nesta
entrega:

- **Cache em ficheiro sem limite de crescimento**: nada expira nem é
  removido; um varrimento de coordenadas esgotaria depressa a quota diária
  gratuita da Open-Meteo e encheria o disco. Precisa de uma política de
  expiração/limpeza — diferente do "CloudFront com limites" acima, que é
  sobre taxa de pedidos, não armazenamento.
- Limite de idade para dados *stale* e mostrar há quanto tempo são
  (`getStale()` não tem hoje um limite máximo).
- Devolver códigos HTTP reais (502/503) quando a Open-Meteo falha e não há
  cache nenhuma, em vez de sempre 200 com `error` preenchido.
- Instalar as dependências (`composer install`/`npm install`) no build da
  imagem Docker, em vez de no arranque do container.
- Correr os containers como utilizador não-root.
- `localStorage` pode lançar exceção nalguns modos de privacidade — falta
  tratar isso no `useTheme`.
- O tema "pisca" claro por uma fração de segundo antes de aplicar o escuro
  (o `data-theme` só é definido depois do primeiro render).
- Acessibilidade: o botão de tema substitui o texto visível pelo
  `aria-label` (falha WCAG 2.5.3 — rótulo deve incluir o texto visível); os
  estados "a carregar"/"a obter localização" não são anunciados a leitores
  de ecrã (falta `role="status"`).
- `test.sh` e `exemplo.sh` assumem que são corridos a partir da raiz do
  repositório.
