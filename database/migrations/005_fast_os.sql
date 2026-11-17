-- =============================================================
-- iFixAdmin — Fast OS
-- Permite abrir OS sem cadastro prévio de cliente/equipamento.
-- Idempotente e sem remoção de dados.
-- =============================================================

BEGIN;

ALTER TABLE service_orders ALTER COLUMN client_id DROP NOT NULL;
ALTER TABLE service_orders ALTER COLUMN device_id DROP NOT NULL;

ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS is_fast_order BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS quick_customer_name VARCHAR(160);
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS quick_customer_phone VARCHAR(30);
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS quick_device_name VARCHAR(160);
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS quick_brand VARCHAR(80);
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS quick_model VARCHAR(120);
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS quick_service_description TEXT;
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS quick_accessories TEXT;
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS quick_password TEXT;
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS quick_notes TEXT;
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS quick_due_date DATE;
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS fast_order_created_by INTEGER REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS fast_order_converted_at TIMESTAMPTZ;
ALTER TABLE service_orders ADD COLUMN IF NOT EXISTS fast_order_converted_by INTEGER REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_os_fast_order ON service_orders (is_fast_order, opened_at DESC);
CREATE INDEX IF NOT EXISTS idx_os_quick_customer ON service_orders (LOWER(quick_customer_name));
CREATE INDEX IF NOT EXISTS idx_os_quick_phone ON service_orders (quick_customer_phone);
CREATE INDEX IF NOT EXISTS idx_os_quick_device ON service_orders (LOWER(quick_device_name));

COMMIT;
