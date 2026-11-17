-- =============================================================
-- Migration 001 — Single-user cleanup
-- Remove a camada multi-empresa e mantém apenas o usuário/dados atuais.
-- =============================================================

BEGIN;

DO $$
DECLARE
    keep_user_id INTEGER;
    keep_empresa_id INTEGER;
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'users'
          AND column_name = 'empresa_id'
    ) THEN
        RETURN;
    END IF;

    SELECT id INTO keep_user_id
    FROM users
    WHERE id = 10;

    IF keep_user_id IS NULL THEN
        SELECT id INTO keep_user_id
        FROM users
        ORDER BY id
        LIMIT 1;
    END IF;

    IF keep_user_id IS NULL THEN
        RETURN;
    END IF;

    EXECUTE 'SELECT empresa_id FROM users WHERE id = $1'
    INTO keep_empresa_id
    USING keep_user_id;

    IF keep_empresa_id IS NOT NULL THEN
        DELETE FROM trello_comments tc
        USING trello_cards c
        WHERE tc.card_id = c.id
          AND c.empresa_id IS DISTINCT FROM keep_empresa_id;

        DELETE FROM trello_cards       WHERE empresa_id IS DISTINCT FROM keep_empresa_id;
        DELETE FROM trello_columns     WHERE empresa_id IS DISTINCT FROM keep_empresa_id;
        DELETE FROM financial_entries  WHERE empresa_id IS DISTINCT FROM keep_empresa_id;
        DELETE FROM service_orders     WHERE empresa_id IS DISTINCT FROM keep_empresa_id;
        DELETE FROM devices            WHERE empresa_id IS DISTINCT FROM keep_empresa_id;
        DELETE FROM clients            WHERE empresa_id IS DISTINCT FROM keep_empresa_id;
        DELETE FROM inventory          WHERE empresa_id IS DISTINCT FROM keep_empresa_id;
        DELETE FROM schematics         WHERE empresa_id IS DISTINCT FROM keep_empresa_id;
    END IF;

    DELETE FROM users WHERE id <> keep_user_id;
END $$;

ALTER TABLE users             DROP COLUMN IF EXISTS role;
ALTER TABLE users             DROP COLUMN IF EXISTS empresa_id;
ALTER TABLE clients           DROP COLUMN IF EXISTS empresa_id;
ALTER TABLE devices           DROP COLUMN IF EXISTS empresa_id;
ALTER TABLE service_orders    DROP COLUMN IF EXISTS empresa_id;
ALTER TABLE inventory         DROP COLUMN IF EXISTS empresa_id;
ALTER TABLE financial_entries DROP COLUMN IF EXISTS empresa_id;
ALTER TABLE schematics        DROP COLUMN IF EXISTS empresa_id;
ALTER TABLE trello_columns    DROP COLUMN IF EXISTS empresa_id;
ALTER TABLE trello_cards      DROP COLUMN IF EXISTS empresa_id;

DROP TABLE IF EXISTS empresas;

INSERT INTO trello_columns (name, position)
SELECT *
FROM (VALUES ('A Fazer', 0), ('Em Andamento', 1), ('Concluído', 2)) AS v(name, position)
WHERE NOT EXISTS (SELECT 1 FROM trello_columns);

COMMIT;
