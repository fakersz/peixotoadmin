-- iFixAdmin - Servicos Digitais
-- Migration idempotente: cria estruturas locais sem integrar financeiro automaticamente.

BEGIN;

CREATE TABLE IF NOT EXISTS digital_services (
    id                  SERIAL PRIMARY KEY,
    client_id           INTEGER REFERENCES clients(id) ON DELETE SET NULL,
    temporary_client_name VARCHAR(160),
    project_name        VARCHAR(180) NOT NULL,
    service_type        VARCHAR(60) NOT NULL,
    description         TEXT,
    status              VARCHAR(40) NOT NULL DEFAULT 'lead',
    priority            VARCHAR(20) NOT NULL DEFAULT 'normal',
    progress            INTEGER NOT NULL DEFAULT 0 CHECK (progress >= 0 AND progress <= 100),
    progress_mode       VARCHAR(20) NOT NULL DEFAULT 'manual' CHECK (progress_mode IN ('manual','checklist')),
    assigned_user_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
    start_date          DATE,
    due_date            DATE,
    delivered_at        DATE,
    next_review_at      DATE,
    total_value         NUMERIC(12,2),
    down_payment        NUMERIC(12,2),
    amount_received     NUMERIC(12,2),
    estimated_cost      NUMERIC(12,2),
    payment_method      VARCHAR(80),
    installments        INTEGER,
    recurring_value     NUMERIC(12,2),
    recurring_interval  VARCHAR(30),
    stack               TEXT,
    language            TEXT,
    database_name       TEXT,
    framework           TEXT,
    server_name         TEXT,
    hosting             TEXT,
    domain              TEXT,
    ssl_status          VARCHAR(40),
    repository_url      VARCHAR(500),
    environment         VARCHAR(80),
    version             VARCHAR(80),
    site_url            VARCHAR(500),
    admin_url           VARCHAR(500),
    staging_url         VARCHAR(500),
    github_url          VARCHAR(500),
    figma_url           VARCHAR(500),
    documentation_url   VARCHAR(500),
    domain_url          VARCHAR(500),
    hosting_url         VARCHAR(500),
    internal_notes      TEXT,
    client_notes        TEXT,
    pending_items       TEXT,
    blockers            TEXT,
    important_decisions TEXT,
    is_archived         BOOLEAN NOT NULL DEFAULT FALSE,
    created_by          INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_digital_services_client ON digital_services (client_id);
CREATE INDEX IF NOT EXISTS idx_digital_services_status ON digital_services (status);
CREATE INDEX IF NOT EXISTS idx_digital_services_priority ON digital_services (priority);
CREATE INDEX IF NOT EXISTS idx_digital_services_due_date ON digital_services (due_date);
CREATE INDEX IF NOT EXISTS idx_digital_services_assigned ON digital_services (assigned_user_id);
CREATE INDEX IF NOT EXISTS idx_digital_services_archived ON digital_services (is_archived);
CREATE INDEX IF NOT EXISTS idx_digital_services_created ON digital_services (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_digital_services_deleted ON digital_services (deleted_at);

ALTER TABLE digital_services ADD COLUMN IF NOT EXISTS kanban_card_id INTEGER REFERENCES trello_cards(id) ON DELETE SET NULL;
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

COMMIT;
