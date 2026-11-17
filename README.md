# iFixAdmin

Sistema administrativo interno da **Peixoto Tech** (Jean Peixoto) — assistência técnica
especializada em Apple (MacBook, iMac, Mac mini, iPad; futuramente iPhone e Apple Watch).


## Stack

- PHP 8.3 (PHP-FPM) — sem frameworks
- PostgreSQL 16 (PDO + prepared statements)
- HTML + CSS + JavaScript puro
- Nginx

## Estrutura

```
peixotoadmin/
├── public/              # raiz web (única pasta exposta pelo Nginx)
│   ├── index.php        # front controller / roteador
│   ├── .user.ini        # limites de upload (PDF até 40 MB)
│   └── assets/          # css/ e js/
├── app/
│   ├── config/          # config.php (env + sessão) e database.php (PDO)
│   ├── controllers/     # um arquivo por módulo
│   ├── models/          # funções de acesso ao banco
│   ├── views/           # templates PHP (layout + páginas)
│   ├── helpers/         # escape, CSRF, flash, formatação
│   └── middleware/      # auth.php (require_login)
├── database/schema.sql  # migration do banco
├── storage/uploads/     # PDFs de esquemas (fora da raiz web, servidos com login)
├── .env                 # credenciais do banco (não versionado)
└── README.md
```

## Módulos

| Rota | Função |
|---|---|
| `/dashboard` | Cards: OS abertas, aguardando peça, finalizadas, receita, entradas, saídas, lucro, estoque baixo |
| `/clients` | CRUD de clientes + histórico (equipamentos e OS) |
| `/devices` | Equipamentos vinculados ao cliente, com código Apple (A1502, A1466…) |
| `/orders` | Ordens de serviço com status, prioridade, total automático e pagamentos |
| `/inventory` | Estoque com alerta de mínimo e ajuste rápido de quantidade (+/−) |
| `/financial` | Entradas/saídas, resumo mensal, vínculo com OS |
| `/schematics` | Catálogo de placas Apple: board number, defeitos comuns, medições de bancada, PDF do esquema |
| `/trello` | Quadro kanban: colunas, cards em **Markdown**, comentários e etiquetas, com drag-and-drop |

## Instalação / migração

```bash
# criar banco (uma vez)
sudo -u postgres psql -c "CREATE ROLE peixotoadmin LOGIN PASSWORD 'SENHA';"
sudo -u postgres psql -c "CREATE DATABASE peixotoadmin OWNER peixotoadmin;"

# instalação nova
psql -h 127.0.0.1 -U peixotoadmin -d peixotoadmin -f database/schema.sql

# somente ao migrar uma base antiga multi-empresa para uso pessoal
psql -h 127.0.0.1 -U peixotoadmin -d peixotoadmin -f database/migrations/001_single_user_cleanup.sql

# configurar credenciais
cp .env.example .env   # e editar DB_PASS

# popular o catálogo de placas Apple 2010–2026 (100 registros:
# MacBook Pro/Air/12", iMac, Mac mini e Mac Pro)
# idempotente: rodar de novo atualiza sem duplicar e preserva PDFs anexados
php database/seeders/AppleSchematicsSeeder.php
```

## Uso pessoal

O sistema é single-user: não há cadastro público, painel comercial, papéis,
usuários adicionais ou isolamento por empresa. Todas as rotas internas exigem login.

## Usuário inicial

Para criar o administrador inicial, defina no `.env` as variáveis `SEED_ADMIN_EMAIL`,
`SEED_ADMIN_PASSWORD` (e opcionalmente `SEED_ADMIN_NAME`) antes da primeira visita à tela
de login. Se o banco estiver sem usuários, o sistema cria o administrador com esses dados.
Sem essas variáveis, nenhum usuário é criado. Nunca use senhas fracas ou padrão.

## Segurança

- Sessões com cookie `HttpOnly`, `Secure` e `SameSite=Lax`; ID regenerado no login
- Token CSRF obrigatório em todo POST
- PDO com prepared statements em 100% das queries
- Saída sempre escapada com `htmlspecialchars`
- Uploads validados por MIME real (finfo) e servidos apenas a usuários logados
- `.env`, `app/` e `storage/` ficam fora da raiz web
