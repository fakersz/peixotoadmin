-- iFixAdmin - Servicos Digitais fase 2
-- Templates proprios, financeiro, documentos, cobrancas, portal e logs de automacao local.

BEGIN;

CREATE TABLE IF NOT EXISTS digital_service_checklist_templates (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(140) NOT NULL,
    service_type VARCHAR(60),
    description TEXT,
    created_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_digital_service_templates_type ON digital_service_checklist_templates (service_type);

CREATE TABLE IF NOT EXISTS digital_service_checklist_template_items (
    id          SERIAL PRIMARY KEY,
    template_id INTEGER NOT NULL REFERENCES digital_service_checklist_templates(id) ON DELETE CASCADE,
    title       VARCHAR(220) NOT NULL,
    description TEXT,
    order_index INTEGER NOT NULL DEFAULT 0,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_digital_service_template_items_template ON digital_service_checklist_template_items (template_id, order_index, id);

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
CREATE INDEX IF NOT EXISTS idx_digital_service_documents_service ON digital_service_documents (digital_service_id, type, created_at DESC);

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
CREATE INDEX IF NOT EXISTS idx_digital_service_billing_service ON digital_service_billing (digital_service_id, status, due_date);

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
CREATE INDEX IF NOT EXISTS idx_digital_service_portal_tokens_service ON digital_service_portal_tokens (digital_service_id, created_at DESC);

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
CREATE INDEX IF NOT EXISTS idx_digital_service_automation_logs_service ON digital_service_automation_logs (digital_service_id, created_at DESC);

COMMIT;
