-- =============================================================
-- Peixoto Admin — Schema PostgreSQL
-- Assistência Técnica Apple — Peixoto Tech / Jean Peixoto
-- =============================================================

BEGIN;

-- ---------- Usuários ----------
CREATE TABLE IF NOT EXISTS users (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(120)  NOT NULL,
    email         VARCHAR(160)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

-- ---------- Clientes ----------
CREATE TABLE IF NOT EXISTS clients (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(160) NOT NULL,
    phone      VARCHAR(30),
    email      VARCHAR(160),
    document   VARCHAR(30),
    address    TEXT,
    notes      TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_clients_name ON clients (LOWER(name));

-- ---------- Equipamentos ----------
CREATE TABLE IF NOT EXISTS devices (
    id              SERIAL PRIMARY KEY,
    client_id       INTEGER NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    category        VARCHAR(40)  NOT NULL,           -- MacBook, iMac, Mac mini, iPad, iPhone, Apple Watch, Outro
    brand           VARCHAR(60)  NOT NULL DEFAULT 'Apple',
    model           VARCHAR(120),                    -- ex: MacBook Pro 13" 2015
    model_code      VARCHAR(20),                     -- ex: A1502, A1466, A1708, A1989
    serial_number   VARCHAR(60),
    year            SMALLINT,
    processor       VARCHAR(80),
    memory          VARCHAR(40),
    storage         VARCHAR(40),
    condition_notes TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_devices_client     ON devices (client_id);
CREATE INDEX IF NOT EXISTS idx_devices_model_code ON devices (UPPER(model_code));

-- ---------- Ordens de Serviço ----------
CREATE TABLE IF NOT EXISTS service_orders (
    id             SERIAL PRIMARY KEY,
    client_id      INTEGER REFERENCES clients(id) ON DELETE RESTRICT,
    device_id      INTEGER REFERENCES devices(id) ON DELETE RESTRICT,
    title          VARCHAR(160) NOT NULL,
    reported_issue TEXT,
    diagnosis      TEXT,
    solution       TEXT,
    status         VARCHAR(30) NOT NULL DEFAULT 'aberta'
                   CHECK (status IN ('aberta','diagnostico','aguardando_aprovacao',
                                     'aguardando_peca','em_reparo','pronto','entregue','cancelada')),
    priority       VARCHAR(10) NOT NULL DEFAULT 'normal'
                   CHECK (priority IN ('baixa','normal','alta','urgente')),
    labor_price    NUMERIC(10,2) NOT NULL DEFAULT 0,
    parts_price    NUMERIC(10,2) NOT NULL DEFAULT 0,
    total_price    NUMERIC(10,2) NOT NULL DEFAULT 0,
    paid_amount    NUMERIC(10,2) NOT NULL DEFAULT 0,
    payment_status VARCHAR(10) NOT NULL DEFAULT 'pendente'
                   CHECK (payment_status IN ('pendente','parcial','pago')),
    opened_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    closed_at      TIMESTAMPTZ,
    is_fast_order  BOOLEAN NOT NULL DEFAULT FALSE,
    quick_customer_name VARCHAR(160),
    quick_customer_phone VARCHAR(30),
    quick_device_name VARCHAR(160),
    quick_brand VARCHAR(80),
    quick_model VARCHAR(120),
    quick_service_description TEXT,
    quick_accessories TEXT,
    quick_password TEXT,
    quick_notes TEXT,
    quick_due_date DATE,
    fast_order_created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    fast_order_converted_at TIMESTAMPTZ,
    fast_order_converted_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_os_status ON service_orders (status);
CREATE INDEX IF NOT EXISTS idx_os_client ON service_orders (client_id);
CREATE INDEX IF NOT EXISTS idx_os_device ON service_orders (device_id);
CREATE INDEX IF NOT EXISTS idx_os_fast_order ON service_orders (is_fast_order, opened_at DESC);
CREATE INDEX IF NOT EXISTS idx_os_quick_customer ON service_orders (LOWER(quick_customer_name));
CREATE INDEX IF NOT EXISTS idx_os_quick_phone ON service_orders (quick_customer_phone);
CREATE INDEX IF NOT EXISTS idx_os_quick_device ON service_orders (LOWER(quick_device_name));

CREATE TABLE IF NOT EXISTS service_order_portal_tokens (
    id               SERIAL PRIMARY KEY,
    service_order_id INTEGER NOT NULL REFERENCES service_orders(id) ON DELETE CASCADE,
    token_hash       VARCHAR(64) NOT NULL UNIQUE,
    label            VARCHAR(120),
    expires_at       TIMESTAMPTZ,
    revoked_at       TIMESTAMPTZ,
    created_by       INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_service_order_portal_tokens_order ON service_order_portal_tokens (service_order_id, created_at DESC);

-- ---------- Estoque ----------
CREATE TABLE IF NOT EXISTS inventory (
    id                SERIAL PRIMARY KEY,
    name              VARCHAR(160) NOT NULL,
    category          VARCHAR(60),                    -- Bateria, Tela, SSD, Flex, Parafuso, Consumível...
    compatible_models VARCHAR(255),                   -- ex: A1502, A1466
    quantity          INTEGER NOT NULL DEFAULT 0,
    min_quantity      INTEGER NOT NULL DEFAULT 1,
    cost_price        NUMERIC(10,2) NOT NULL DEFAULT 0,
    sale_price        NUMERIC(10,2) NOT NULL DEFAULT 0,
    supplier          VARCHAR(160),
    notes             TEXT,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_inventory_name ON inventory (LOWER(name));

-- ---------- Financeiro ----------
CREATE TABLE IF NOT EXISTS financial_entries (
    id             SERIAL PRIMARY KEY,
    type           VARCHAR(10) NOT NULL CHECK (type IN ('entrada','saida')),
    description    VARCHAR(255) NOT NULL,
    amount         NUMERIC(10,2) NOT NULL CHECK (amount >= 0),
    category       VARCHAR(60),                       -- Serviço, Peça, Aluguel, Ferramenta...
    reference_type VARCHAR(30),                       -- ex: service_order
    reference_id   INTEGER,
    entry_date     DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_fin_date ON financial_entries (entry_date);
CREATE INDEX IF NOT EXISTS idx_fin_ref  ON financial_entries (reference_type, reference_id);

-- ---------- Esquemas Elétricos ----------
CREATE TABLE IF NOT EXISTS schematics (
    id              SERIAL PRIMARY KEY,
    title           VARCHAR(160) NOT NULL,
    device_category VARCHAR(40),
    model_code      VARCHAR(20),                      -- ex: A1502
    board_code      VARCHAR(30),                      -- ex: 820-3536
    year            SMALLINT,
    file_path       VARCHAR(255),                     -- caminho relativo em storage/uploads
    external_link   VARCHAR(500),
    notes           TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_schematics_model ON schematics (UPPER(model_code));

-- ---------- Trello (quadro kanban) ----------
CREATE TABLE IF NOT EXISTS trello_columns (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(80) NOT NULL,
    position   INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS trello_cards (
    id         SERIAL PRIMARY KEY,
    column_id  INTEGER NOT NULL REFERENCES trello_columns(id) ON DELETE CASCADE,
    title      VARCHAR(200) NOT NULL,
    body       TEXT,                                 -- conteúdo em markdown
    tags       VARCHAR(255),                         -- etiquetas separadas por vírgula
    position   INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_trello_cards_col ON trello_cards (column_id, position);

CREATE TABLE IF NOT EXISTS trello_comments (
    id         SERIAL PRIMARY KEY,
    card_id    INTEGER NOT NULL REFERENCES trello_cards(id) ON DELETE CASCADE,
    body       TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_trello_comments_card ON trello_comments (card_id);

-- ---------- Serviços Digitais ----------
CREATE TABLE IF NOT EXISTS digital_services (
    id                    SERIAL PRIMARY KEY,
    client_id             INTEGER REFERENCES clients(id) ON DELETE SET NULL,
    temporary_client_name VARCHAR(160),
    project_name          VARCHAR(180) NOT NULL,
    service_type          VARCHAR(60) NOT NULL,
    description           TEXT,
    status                VARCHAR(40) NOT NULL DEFAULT 'lead',
    priority              VARCHAR(20) NOT NULL DEFAULT 'normal',
    progress              INTEGER NOT NULL DEFAULT 0 CHECK (progress >= 0 AND progress <= 100),
    progress_mode         VARCHAR(20) NOT NULL DEFAULT 'manual' CHECK (progress_mode IN ('manual','checklist')),
    assigned_user_id      INTEGER REFERENCES users(id) ON DELETE SET NULL,
    start_date            DATE,
    due_date              DATE,
    delivered_at          DATE,
    next_review_at        DATE,
    total_value           NUMERIC(12,2),
    down_payment          NUMERIC(12,2),
    amount_received       NUMERIC(12,2),
    estimated_cost        NUMERIC(12,2),
    payment_method        VARCHAR(80),
    installments          INTEGER,
    recurring_value       NUMERIC(12,2),
    recurring_interval    VARCHAR(30),
    stack                 TEXT,
    language              TEXT,
    database_name         TEXT,
    framework             TEXT,
    server_name           TEXT,
    hosting               TEXT,
    domain                TEXT,
    ssl_status            VARCHAR(40),
    repository_url        VARCHAR(500),
    environment           VARCHAR(80),
    version               VARCHAR(80),
    site_url              VARCHAR(500),
    admin_url             VARCHAR(500),
    staging_url           VARCHAR(500),
    github_url            VARCHAR(500),
    figma_url             VARCHAR(500),
    documentation_url     VARCHAR(500),
    domain_url            VARCHAR(500),
    hosting_url           VARCHAR(500),
    internal_notes        TEXT,
    client_notes          TEXT,
    pending_items         TEXT,
    blockers              TEXT,
    important_decisions   TEXT,
    kanban_card_id        INTEGER REFERENCES trello_cards(id) ON DELETE SET NULL,
    is_archived           BOOLEAN NOT NULL DEFAULT FALSE,
    created_by            INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at            TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at            TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at            TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS idx_digital_services_client ON digital_services (client_id);
CREATE INDEX IF NOT EXISTS idx_digital_services_status ON digital_services (status);
CREATE INDEX IF NOT EXISTS idx_digital_services_priority ON digital_services (priority);
CREATE INDEX IF NOT EXISTS idx_digital_services_due_date ON digital_services (due_date);
CREATE INDEX IF NOT EXISTS idx_digital_services_assigned ON digital_services (assigned_user_id);
CREATE INDEX IF NOT EXISTS idx_digital_services_archived ON digital_services (is_archived);
CREATE INDEX IF NOT EXISTS idx_digital_services_created ON digital_services (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_digital_services_kanban_card ON digital_services (kanban_card_id);

CREATE TABLE IF NOT EXISTS digital_service_tasks (
    id                 SERIAL PRIMARY KEY,
    digital_service_id INTEGER NOT NULL REFERENCES digital_services(id) ON DELETE CASCADE,
    title              VARCHAR(220) NOT NULL,
    description        TEXT,
    status             VARCHAR(20) NOT NULL DEFAULT 'pendente' CHECK (status IN ('pendente','concluida')),
    order_index        INTEGER NOT NULL DEFAULT 0,
    assigned_user_id   INTEGER REFERENCES users(id) ON DELETE SET NULL,
    due_date           DATE,
    completed_at       TIMESTAMPTZ,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_digital_service_tasks_service ON digital_service_tasks (digital_service_id, order_index, id);

CREATE TABLE IF NOT EXISTS digital_service_links (
    id                 SERIAL PRIMARY KEY,
    digital_service_id INTEGER NOT NULL REFERENCES digital_services(id) ON DELETE CASCADE,
    label              VARCHAR(120) NOT NULL,
    url                VARCHAR(600) NOT NULL,
    type               VARCHAR(40),
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_digital_service_links_service ON digital_service_links (digital_service_id);

CREATE TABLE IF NOT EXISTS digital_service_files (
    id                 SERIAL PRIMARY KEY,
    digital_service_id INTEGER NOT NULL REFERENCES digital_services(id) ON DELETE CASCADE,
    original_name      VARCHAR(255) NOT NULL,
    stored_name        VARCHAR(255) NOT NULL,
    path               VARCHAR(500) NOT NULL,
    mime_type          VARCHAR(160),
    size_bytes         BIGINT NOT NULL DEFAULT 0,
    file_hash          VARCHAR(64),
    uploaded_by        INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_digital_service_files_service ON digital_service_files (digital_service_id);
CREATE INDEX IF NOT EXISTS idx_digital_service_files_hash ON digital_service_files (file_hash);

CREATE TABLE IF NOT EXISTS digital_service_notes (
    id                 SERIAL PRIMARY KEY,
    digital_service_id INTEGER NOT NULL REFERENCES digital_services(id) ON DELETE CASCADE,
    user_id            INTEGER REFERENCES users(id) ON DELETE SET NULL,
    note               TEXT NOT NULL,
    is_client_visible  BOOLEAN NOT NULL DEFAULT FALSE,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_digital_service_notes_service ON digital_service_notes (digital_service_id, created_at DESC);

CREATE TABLE IF NOT EXISTS digital_service_history (
    id                 SERIAL PRIMARY KEY,
    digital_service_id INTEGER NOT NULL REFERENCES digital_services(id) ON DELETE CASCADE,
    user_id            INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action             VARCHAR(80) NOT NULL,
    old_data           JSONB,
    new_data           JSONB,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_digital_service_history_service ON digital_service_history (digital_service_id, created_at DESC);

CREATE TABLE IF NOT EXISTS digital_service_checklist_templates (
    id           SERIAL PRIMARY KEY,
    name         VARCHAR(140) NOT NULL,
    service_type VARCHAR(60),
    description  TEXT,
    created_by   INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS digital_service_checklist_template_items (
    id           SERIAL PRIMARY KEY,
    template_id  INTEGER NOT NULL REFERENCES digital_service_checklist_templates(id) ON DELETE CASCADE,
    title        VARCHAR(220) NOT NULL,
    description  TEXT,
    order_index  INTEGER NOT NULL DEFAULT 0,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS digital_service_documents (
    id                 SERIAL PRIMARY KEY,
    digital_service_id INTEGER NOT NULL REFERENCES digital_services(id) ON DELETE CASCADE,
    type               VARCHAR(30) NOT NULL CHECK (type IN ('proposta','contrato')),
    title              VARCHAR(180) NOT NULL,
    content            TEXT NOT NULL,
    status             VARCHAR(30) NOT NULL DEFAULT 'rascunho',
    created_by         INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS digital_service_billing (
    id                 SERIAL PRIMARY KEY,
    digital_service_id INTEGER NOT NULL REFERENCES digital_services(id) ON DELETE CASCADE,
    financial_entry_id INTEGER REFERENCES financial_entries(id) ON DELETE SET NULL,
    description        VARCHAR(255) NOT NULL,
    amount             NUMERIC(12,2) NOT NULL CHECK (amount >= 0),
    due_date           DATE,
    status             VARCHAR(30) NOT NULL DEFAULT 'pendente' CHECK (status IN ('pendente','lancado','pago','cancelado')),
    created_by         INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS digital_service_portal_tokens (
    id                 SERIAL PRIMARY KEY,
    digital_service_id INTEGER NOT NULL REFERENCES digital_services(id) ON DELETE CASCADE,
    token_hash         VARCHAR(64) NOT NULL UNIQUE,
    label              VARCHAR(120),
    expires_at         TIMESTAMPTZ,
    revoked_at         TIMESTAMPTZ,
    created_by         INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS digital_service_automation_logs (
    id                 SERIAL PRIMARY KEY,
    digital_service_id INTEGER NOT NULL REFERENCES digital_services(id) ON DELETE CASCADE,
    action             VARCHAR(80) NOT NULL,
    status             VARCHAR(30) NOT NULL DEFAULT 'registrado',
    message            TEXT,
    payload_metadata   JSONB,
    created_by         INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO trello_columns (name, position)
SELECT * FROM (VALUES ('A Fazer', 0), ('Em Andamento', 1), ('Concluído', 2)) AS v(name, position)
WHERE NOT EXISTS (SELECT 1 FROM trello_columns);

COMMIT;
