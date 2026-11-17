-- iFixAdmin - Portal do cliente para Ordens de Servico
-- Mesma estrutura do portal de Servicos Digitais, aplicada a service_orders.

BEGIN;

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

COMMIT;
