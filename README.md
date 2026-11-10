# Phobos PHP

Sistema web de **precificação e ordens de serviço para pequenos produtores artesanais**, desenvolvido em PHP orientado a objetos com PDO + PostgreSQL.
Projeto Final de **Programação II** — UNOESC, Campus de Videira (Ciência da Computação).

> Versão em PHP do Phobos (Next.js + Drizzle ORM): mesmo domínio, reimplementado para evidenciar POO, relacionamentos entre classes, interfaces/abstração, traits e persistência segura via PDO.

## Integrantes

| Nome | Responsabilidades |
| --- | --- |
| _(preencher)_ | |
| _(preencher)_ | |
| _(preencher)_ | |

## Problema e objetivo

Pequenos produtores (crochê, costura, papelaria etc.) calculam o preço “no olhômetro”: não consideram o custo real dos insumos comprados em embalagem, o tempo de trabalho nem a margem desejada, e perdem o controle das ordens de serviço.

**Objetivo geral:** permitir cadastrar insumos, compor produtos com custo e preço de venda calculados automaticamente e registrar ordens de serviço com lucro apurado.

**Funcionalidades**

- **Autenticação**: login com sessão e **cadastro público** (`/register`). A 1ª conta do sistema vira administradora; as demais entram como `member`.
- **Meu perfil** (`/profile`): editar nome/e-mail e trocar a senha (exige a senha atual).
- **Usuários e permissões** (`/settings/users`, só admin): criar, promover/rebaixar e excluir usuários. Ninguém altera o próprio perfil nem exclui a própria conta, então sempre sobra um administrador.
- CRUD de **Clientes** (pessoa física com CPF / jurídica com CNPJ, ambos validados).
- CRUD de **Materiais**: custo unitário = preço da embalagem ÷ quantidade; arquivamento; imagem.
- CRUD de **Produtos**: composição de insumos + mão de obra + margem (markup, margem sobre a venda ou valor fixo); recálculo quando o preço de um insumo muda; imagem.
- **Ordens de serviço**: itens com preço/custo congelados, desconto, frete, lucro, fluxo de status (rascunho → confirmada → em produção → concluída / cancelada) e controle de pagamento.
- Painel com faturamento, lucro e ordens em aberto.

## Perfis e permissões

| Ação | Administrador (`admin`) | Membro (`member`) |
| --- | :---: | :---: |
| Entrar, ver o próprio perfil, trocar nome/e-mail/senha | ✅ | ✅ |
| Consultar painel, clientes, materiais, produtos e ordens | ✅ | ✅ |
| Criar, editar, arquivar e excluir clientes, materiais e produtos | ✅ | ❌ |
| Recalcular preço de produto | ✅ | ❌ |
| Criar/editar ordens, avançar status, registrar pagamento, excluir | ✅ | ❌ |
| Gerenciar usuários (`/settings/users`) | ✅ | ❌ |

- O cadastro público cria sempre um `member` (exceto a 1ª conta do sistema, que vira `admin`). Um administrador pode promovê-lo em **Usuários**.
- A restrição é aplicada **no servidor** (`Controller::requireAdmin()` devolve **403**); os botões de escrita também são escondidos da interface para quem só consulta.

## Tecnologias

PHP 8.2+ (testado em 8.5), PDO (`pdo_pgsql`), PostgreSQL 14+, HTML/CSS/JS puros (sem frameworks nem Composer).

## Como executar

Pré-requisitos: PHP 8.2+ com a extensão `pdo_pgsql` habilitada e um PostgreSQL acessível.

```bash
# 1. configuração (edite host/porta/usuário/senha do seu PostgreSQL)
cp .env.example .env

# 2. cria o banco phobos_php e aplica database/schema.sql
php bin/migrate.php

# 3. (opcional) usuário admin + dados fictícios de demonstração
php bin/seed.php

# 4. servidor de desenvolvimento
php -S 127.0.0.1:8000 -t public public/router.php
```

Acesse http://127.0.0.1:8000. Duas formas de entrar como administrador:

- usar o e-mail/senha de `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD` do `.env` (criados pelo `bin/seed.php`); ou
- **pular o seed** e criar a conta em `/register`: com o banco vazio, a primeira conta já nasce administradora.

Contas criadas depois pelo `/register` são `member` (somente consulta) até um administrador promovê-las.

Testes unitários (sem banco): `php tests/run.php`.

> O `.env` real **não** é versionado. Não coloque senhas ou tokens reais no repositório.

## Estrutura

```
public/        front controller (index.php), router.php, assets, uploads
src/Core/      Env, Database (PDO), Router, View, Session (flash + CSRF), Auth, Validator
src/Contracts/ interfaces (RepositoryInterface, PricingStrategyInterface)
src/Enums/     Unit, MarginType, PersonType, OrderStatus (máquina de estados), PaymentStatus
src/Models/    entidades de domínio (Entity, Client, IndividualClient, CompanyClient, Material, Product, ...)
src/Pricing/   estratégias de preço (Markup, Margin, Fixed) + fábrica
src/Repositories/ acesso a dados via PDO (AbstractRepository + um por agregado)
src/Traits/    HasTimestamps, Archivable
src/Controllers/ casos de uso HTTP
views/         templates PHP
database/      schema.sql
bin/           migrate.php, seed.php
tests/         run.php
docs/          ARQUITETURA.md (UML, decisões, mapeamento aos requisitos)
```

Documentação técnica completa, diagrama de classes e o mapeamento requisito → código em [docs/ARQUITETURA.md](docs/ARQUITETURA.md).

## Executar com Docker (app + PostgreSQL)

```bash
cp .env.example .env     # defina DB_PASSWORD e SEED_ADMIN_PASSWORD; use SEED=true na 1ª subida para dados de exemplo
docker compose up -d --build
# abre em http://localhost:8080
```

Volumes `pgdata` (banco) e `uploads` (imagens) persistem entre reinícios. O container aplica `database/schema.sql` ao subir.

## Publicação

- **VPS barata** (recomendado): instale Docker, clone o repositório, `docker compose up -d --build`; HTTPS via Caddy/Cloudflare na frente.
- **Plataforma de containers + Postgres gerenciado** (ex.: Railway + Neon): aponte `DB_*` para o banco (host direto, sem `-pooler`) e use `DB_SSLMODE=require`; monte volume em `/var/www/html/public/uploads` ou as imagens somem a cada deploy. O container respeita a variável `$PORT` da plataforma.
- **Primeiro acesso em produção**: com `SEED=true` o container cria um admin (`SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD`) e dados de exemplo. Para começar limpo, deixe `SEED=false` e crie a conta em `/register`: a primeira vira administradora. Nunca deixe senha de exemplo em ambiente público.
