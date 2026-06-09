# Magento 2 — Integração 99Entrega

> Brazilian-only delivery service integration. Documentation in Portuguese.
> Integration with 99Entrega (DiDi) for Adobe Commerce / Magento Open Source.

## Sobre

Módulo de integração com a API da [99Entrega](https://entrega.99app.com/) — serviço de entregas rápidas (motoboy/carro) da 99/DiDi. Este é um módulo de terceiros (não desenvolvido pela 99/DiDi), que consome a API pública documentada. Permite cotar frete no checkout, criar entregas a partir do admin, receber atualizações de status em tempo real via webhook e sincronizar cancelamentos.

**Recursos:**
- Cotação automática no carrinho a partir do CEP (com geocodificação)
- Criação de entrega via botão no admin do pedido
- Webhook com validação HMAC-SHA256 e idempotência por `event_id`
- Sincronização: cancelar pedido no Magento → cancela na 99
- Cache OAuth (2h) e cache de geocoder (30 dias)
- Suporte a múltiplos endereços de coleta por loja (waypoints)
- Logs dedicados em `var/log/entrega99.log` com toggle de debug

---

## Requisitos

- Magento 2.4.x (testado em 2.4.7)
- PHP 8.1 ou superior
- Conta ativa no portal 99Entrega ([page.didiglobal.com](https://page.didiglobal.com/)) com credenciais de API (`Client ID` + `Segredo do Cliente`)
- Opcional: chave da Google Maps Geocoding API (geocoder mais preciso que o Nominatim grátis)

---

## Instalação

1. Copie a pasta `MageShop/Entrega99` para `app/code/MageShop/Entrega99`
2. Habilite e atualize:
   ```bash
   bin/magento module:enable MageShop_Entrega99
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento cache:flush
   ```

Após o `setup:upgrade`, 4 tabelas são criadas:
- `mageshop_entrega99_waypoint` — endereços de coleta
- `mageshop_entrega99_token` — cache do token OAuth
- `mageshop_entrega99_order_shipment` — mapeamento pedido Magento ↔ entrega 99
- `mageshop_entrega99_webhook_event` — log de eventos recebidos (dedup)

---

## Configuração

### 1. Configuração geral

`Stores → Configuration → Sales → Shipping Methods → 99Entrega`

#### Geral

| Campo | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| **Ativo** | yes/no | Sim | Liga/desliga o método no checkout |
| **Título** | texto | Sim | Nome exibido no agrupador do método. Padrão: `99Entrega` |
| **Descrição** | texto | Sim | Nome do método visível ao cliente (ex: `Entrega via 99`) |
| **Ordem** | número | Não | Posição relativa aos outros métodos no checkout |
| **Ambiente** | select | Sim | `Sandbox / Teste` ou `Produção`. Define qual conjunto de credenciais o portal espera |

#### Credenciais da API

| Campo | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| **Client ID** | obscured | Sim | UUID fornecido pelo portal 99 (ex: `4fc5e4c1-...`). Criptografado no banco |
| **Segredo do Cliente** | obscured | Sim | Hex de 32 caracteres. Criptografado no banco |
| **URL Base da API** | URL | Sim | Padrão: `https://entrega.99app.com/entrega-openplatform` |
| **OAuth Scope** | texto | Sim | Padrão: `entrega.order` |

> **Importante**: as credenciais do portal vêm em duas abas (Ambiente de teste / Ambiente de produção). Use o conjunto correspondente ao `Ambiente` selecionado.

#### Webhook

| Campo | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| **URL do Webhook** | label (read-only) | — | URL pública a ser colada no portal da 99 (`https://seudominio/rest/V1/entrega99/webhook`) |
| **Chave de Assinatura** | obscured | Sim (pra webhook funcionar) | Mesma chave gerada no portal. Usada pra validar `X-Webhook-Signature` (HMAC-SHA256) |

#### Opções de Entrega

| Campo | Tipo | Padrão | Descrição |
|---|---|---|---|
| **Tipo de Veículo** | select | `entrega_moto` | `Moto` ou `Carro`. Afeta cobertura e preço |
| **Exigir Código de Coleta** | yes/no | Sim | Motoboy precisa do código pra retirar |
| **Exigir Código de Entrega** | yes/no | Sim | Motoboy precisa do código pra entregar |
| **Método de Devolução** | select | Nenhum | Em caso de devolução: Nenhum / Foto / Código |
| **Criar Entrega Automaticamente** | yes/no | Não | Se `Sim`, chama `/order/create` automaticamente quando o pedido é finalizado. Se `Não`, requer clique manual no admin |
| **Respeitar Frete Grátis** | yes/no | Não | Quando o carrinho qualifica pra free shipping, exibe valor zero |
| **Tempo de Preparação (min)** | número | 20 | Minutos adicionados ao tempo estimado de entrega exibido no checkout |

#### Geocodificador

A 99 exige `latitude` e `longitude` no payload. Como o Magento não captura coordenadas no endereço, usamos um geocoder pra converter.

| Campo | Tipo | Padrão | Descrição |
|---|---|---|---|
| **Provedor** | select | `Nominatim (OSM)` | `Nominatim` (grátis, rate-limit ~1 req/s) ou `Google Maps` (pago, ~10k/mês grátis) |
| **Chave da API Google Maps** | obscured | — | Obrigatória se provedor = Google Maps |
| **User-Agent do Nominatim** | texto | `MageShop-Entrega99/1.0` | Política de uso do Nominatim exige um user-agent identificável (inclua um email de contato) |

Resultados são cacheados por 30 dias (chave = hash do endereço normalizado).

#### Avançado

| Campo | Tipo | Padrão | Descrição |
|---|---|---|---|
| **Log de Depuração** | yes/no | Não | Quando ligado, grava request/response completos da API + lookups do geocoder em `var/log/entrega99.log` |

---

### 2. Cadastro de endereço de coleta (Waypoint)

`Stores → Settings → Locais de Coleta (99Entrega) → Adicionar`

O waypoint representa o endereço de origem da entrega. Você precisa pelo menos **um** ativo pra cotação funcionar.

#### Geral

| Campo | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| **Nome** | texto | Sim | Identificador interno (ex: `Matriz Curitiba`) |
| **Ativo** | toggle | Sim | Marque pra usar nas cotações |
| **Visão da Loja** | select | Não | Limita o waypoint a uma store view específica. `Todas as Lojas` = vale pra qualquer |
| **Código da Fonte MSI** | texto | Não | Vincula a uma fonte do Magento Inventory (se usar multi-source) |
| **Nome do Contato** | texto | Não | Quem entrega o pacote ao motoboy |
| **Telefone** | texto | Não | Aceita qualquer formato — normalizado pra `+55XXXXXXXXXXX` no envio à 99 |
| **E-mail** | texto | Não | Contato auxiliar |

#### Endereço

| Campo | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| **Rua** | texto | Sim | Logradouro |
| **Número** | texto | Não | |
| **Complemento** | texto | Não | |
| **Bairro** | texto | Não | |
| **Cidade** | texto | Sim | |
| **Estado (UF)** | texto | Não | Sigla (PR, SP, etc.) |
| **CEP** | texto | Sim | Com ou sem traço |
| **País** | ISO | Sim | Padrão: `BR` |
| **Latitude** | decimal | **Sim** | Negativa no Brasil. Obtenha no Google Maps (botão direito no ponto exato → clica nas coordenadas pra copiar) |
| **Longitude** | decimal | **Sim** | Idem |
| **Instruções de Coleta** | texto | Não | Mensagem exibida ao motoboy (ex: "Tocar campainha 2x") |

#### Horário de Funcionamento

14 campos (abre/fecha de segunda a domingo), cada um com select de horários em incrementos de 30min ou `— (fechado)`. Hoje os horários são apenas armazenados; uma versão futura pode bloquear cotações fora do horário.

---

### 3. Configuração do Webhook no portal da 99

1. No admin Magento, copie a `URL do Webhook` (em `Stores → Configuration → 99Entrega → Webhook`)
2. Acesse `Configurações → Modo de desenvolvedor → Webhook` no portal da 99
3. Cole a URL no campo correspondente
4. O portal exibe (ou pede que você defina) uma `Chave de Assinatura` — copie
5. Volte no admin Magento e cole a chave no campo `Chave de Assinatura`
6. Save Config + `bin/magento cache:flush`

A partir daí toda transição de status na 99 (motoboy aceitou, chegou, entregou, etc.) chega no Magento e atualiza o pedido.

---

## Fluxo end-to-end

```
1. Cliente no checkout digita o CEP
   └─→ Carrier::collectRates chama /v2/order/estimate
       └─→ Cotação aparece como método de entrega

2. Cliente finaliza o pedido
   └─→ Observer SalesOrderSaveAfter cria registro placeholder
       (status: pending)
       └─→ Se "Criar Automaticamente" = Sim, chama /v2/order/create já

3. Admin clica "Criar Entrega 99Entrega" na página do pedido
   └─→ Re-cota /v2/order/estimate (com endereço completo)
   └─→ Chama /v2/order/create
       └─→ Status: created
       └─→ Comentário no histórico do pedido com order_id e link de rastreamento

4. 99 envia webhooks conforme o ciclo
   ├─→ DriverAccepted   → status: driver_accepted
   ├─→ DriverArrived    → status: driver_arrived
   ├─→ DriverBeginCharge → status: delivering
   ├─→ OrderCompleted   → status: completed
   ├─→ OrderClosed      → status: closed
   ├─→ DriverCanceled   → status: canceled
   └─→ BroadcastTimeout → status: failed

5. Admin cancela o pedido no Magento
   └─→ Observer detecta transição → CancelShipment
       └─→ /v2/order/cancel
           └─→ Status local: canceled
```

---

## Status locais (`OrderShipment.status`)

| Status | Significado |
|---|---|
| `pending` | Placeholder criado, ainda não enviado à 99 |
| `created` | `/order/create` aceito pela 99 |
| `finding` | 99 procurando motoboy |
| `driver_accepted` | Motoboy aceitou |
| `driver_arrived` | Motoboy chegou pra coleta |
| `delivering` | Em rota de entrega |
| `completed` | Entregue (terminal) |
| `canceled` | Cancelado (terminal) |
| `closed` | Fechado pela 99 (terminal) |
| `sendback` | Em devolução |
| `sendback_completed` | Devolução concluída (terminal) |
| `failed` | Falha (sem motoboy encontrado, etc.) |

---

## Logs

Arquivo: `var/log/entrega99.log` (channel Monolog dedicado).

Níveis:
- **INFO** (sempre escreve): entrada em `collectRates`, criação/cancel de entrega, evento de webhook processado
- **DEBUG** (só com `Debug Log = Yes`): request/response completos da API, lookups do geocoder, OAuth issuing
- **ERROR / EXCEPTION** (sempre escreve): qualquer falha

Exemplo em produção (debug desligado):
```
INFO: collectRates: entry {dest_country, dest_postcode, ...}
INFO: Geocoder: result {provider, lat, lng}
INFO: collectRates: SUCCESS — rate appended {fee_cents, estimate_id}
INFO: 99Entrega delivery created {order, entrega99_id}
INFO: 99Entrega webhook received {event, event_id}
INFO: 99Entrega webhook processed {event, previous_status, new_status}
```

---

## Mensagens de erro amigáveis

O módulo traduz os códigos de erro da 99 (`errno`) em mensagens amigáveis exibidas no checkout. Customizável via `i18n/pt_BR.csv`.

| Errno da 99 | Mensagem exibida |
|---|---|
| 3005, 3102, 6101, 6104, 6202 | Endereço fora da área de cobertura 99Entrega |
| 3004, 6103 | Tipo de veículo 99Entrega não disponível |
| 3009, 3010, 6203, 6105 | 99Entrega indisponível no momento |
| 6004, 6005, 6204 | Frete 99Entrega temporariamente indisponível |
| 4002 | Cotação expirou. Atualize a página |
| 1001, 3006, 3007, 6002 | Não foi possível cotar com os dados informados |
| qualquer outro | Frete 99Entrega temporariamente indisponível |

A mensagem técnica completa (com `errno`, `trace_id` e payload) fica no log pra debug.

---

## Estrutura do código

```
app/code/MageShop/Entrega99/
├── Api/                  Interfaces (Repository, Webhook, Geocoder)
├── Block/Adminhtml/      Painel do pedido + buttons + WebhookEndpoint
├── Controller/Adminhtml/ CRUD waypoint + ações de shipment (create/cancel)
├── Exception/            ApiException
├── Helper/Data.php       Leitor de config + facade de log
├── Logger/               Logger dedicado (Monolog channel)
├── Model/
│   ├── Api/              Auth (OAuth), Client (HTTP), Webhook (handler)
│   ├── Carrier/          Entrega99 (collectRates)
│   ├── Config/Source/    Source models pros selects
│   ├── Geocoder/         Pool + Nominatim + GoogleMaps + Result
│   ├── ResourceModel/    Resources + Collections
│   ├── *Repository.php   Repositórios
│   ├── *.php             Entidades (Token, Waypoint, OrderShipment, WebhookEvent)
│   ├── CreateShipment    Lógica de criação
│   ├── CancelShipment    Lógica de cancelamento
│   ├── ApiErrorTranslator Mapeia errno → mensagem amigável
│   └── PhoneFormatter    Normaliza telefones BR
├── Observer/             SalesOrderPlaceBefore + SalesOrderSaveAfter
├── Ui/Component/         ActionsColumn do grid
├── etc/
│   ├── adminhtml/        system.xml, menu.xml, routes.xml
│   ├── config.xml        Defaults do carrier
│   ├── db_schema.xml     Tabelas
│   ├── di.xml            Preferences, geocoder pool, grid collection
│   ├── events.xml        Observers
│   ├── webapi.xml        Rota REST do webhook
│   └── module.xml
├── i18n/pt_BR.csv        Traduções
└── view/
    ├── adminhtml/        Layouts + UI components + templates
    └── frontend/         Layouts + JS validator do CEP
```

---

## Eventos disparados (para extensão)

O módulo emite eventos Magento que você pode plugar em customizações próprias sem alterar este código:

| Evento | Quando | Argumentos |
|---|---|---|
| `mageshop_entrega99_shipment_create_after` | Após criar entrega na 99 | `order`, `shipment`, `response` |
| `mageshop_entrega99_shipment_cancel_after` | Após cancelar entrega na 99 | `order_id`, `shipment`, `response` |
| `mageshop_entrega99_webhook_received` | Após processar webhook | `event`, `event_id`, `message`, `shipment` |

---

## Licença

Proprietário. Uso interno permitido. Distribuição requer autorização.

---

## Desenvolvedora

**MageShop**

<sub>Plataforma e-commerce para quem leva vendas a sério.</sub>

[https://mageshop.com.br](https://mageshop.com.br/)
