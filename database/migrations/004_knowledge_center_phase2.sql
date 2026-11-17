-- =============================================================
-- iFixAdmin — Centro de Conhecimento Fase 2
-- Campos estruturados, favoritos por usuário, histórico, uploads e logs.
-- Idempotente e sem remoção de dados.
-- =============================================================

BEGIN;

ALTER TABLE knowledge_manufacturers ADD COLUMN IF NOT EXISTS normalized_name VARCHAR(100);
ALTER TABLE knowledge_models ADD COLUMN IF NOT EXISTS normalized_name VARCHAR(160);
ALTER TABLE knowledge_models ADD COLUMN IF NOT EXISTS normalized_code VARCHAR(80);

UPDATE knowledge_manufacturers
SET normalized_name = lower(regexp_replace(translate(name, 'ÁÀÂÃÄáàâãäÉÈÊËéèêëÍÌÎÏíìîïÓÒÔÕÖóòôõöÚÙÛÜúùûüÇçÑñ', 'AAAAAaaaaaEEEEeeeeIIIIiiiiOOOOOoooooUUUUuuuuCcNn'), '[^a-zA-Z0-9]+', '', 'g'))
WHERE normalized_name IS NULL;

UPDATE knowledge_models
SET normalized_name = lower(regexp_replace(translate(name, 'ÁÀÂÃÄáàâãäÉÈÊËéèêëÍÌÎÏíìîïÓÒÔÕÖóòôõöÚÙÛÜúùûüÇçÑñ', 'AAAAAaaaaaEEEEeeeeIIIIiiiiOOOOOoooooUUUUuuuuCcNn'), '[^a-zA-Z0-9]+', '', 'g')),
    normalized_code = lower(regexp_replace(translate(COALESCE(code, ''), 'ÁÀÂÃÄáàâãäÉÈÊËéèêëÍÌÎÏíìîïÓÒÔÕÖóòôõöÚÙÛÜúùûüÇçÑñ', 'AAAAAaaaaaEEEEeeeeIIIIiiiiOOOOOoooooUUUUuuuuCcNn'), '[^a-zA-Z0-9]+', '', 'g'))
WHERE normalized_name IS NULL OR normalized_code IS NULL;

CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_manufacturers_normalized ON knowledge_manufacturers (normalized_name);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_models_normalized_name ON knowledge_models (manufacturer_id, normalized_name);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_models_normalized_code ON knowledge_models (manufacturer_id, normalized_code) WHERE normalized_code <> '';

ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS slug VARCHAR(220);
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS category VARCHAR(80);
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS symptoms TEXT;
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS diagnosis TEXT;
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS procedure TEXT;
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS solution TEXT;
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS tools TEXT;
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS parts_used TEXT;
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS avg_time_minutes INTEGER;
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS difficulty VARCHAR(20) DEFAULT 'media';
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS notes TEXT;
ALTER TABLE knowledge_articles ADD COLUMN IF NOT EXISTS archived_at TIMESTAMPTZ;

UPDATE knowledge_articles
SET slug = lower(trim(both '-' from regexp_replace(translate(title, 'ÁÀÂÃÄáàâãäÉÈÊËéèêëÍÌÎÏíìîïÓÒÔÕÖóòôõöÚÙÛÜúùûüÇçÑñ', 'AAAAAaaaaaEEEEeeeeIIIIiiiiOOOOOoooooUUUUuuuuCcNn'), '[^a-zA-Z0-9]+', '-', 'g'))) || '-' || id
WHERE slug IS NULL OR trim(slug) = '';

CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_articles_slug ON knowledge_articles (slug);
CREATE INDEX IF NOT EXISTS idx_knowledge_articles_favorite ON knowledge_articles (favorite);
CREATE INDEX IF NOT EXISTS idx_knowledge_articles_category ON knowledge_articles (category);

ALTER TABLE knowledge_repairs ADD COLUMN IF NOT EXISTS symptoms TEXT;
ALTER TABLE knowledge_repairs ADD COLUMN IF NOT EXISTS tests_performed TEXT;
ALTER TABLE knowledge_repairs ADD COLUMN IF NOT EXISTS tools TEXT;
ALTER TABLE knowledge_repairs ADD COLUMN IF NOT EXISTS cost NUMERIC(10,2) NOT NULL DEFAULT 0;
ALTER TABLE knowledge_repairs ADD COLUMN IF NOT EXISTS result VARCHAR(40) DEFAULT 'resolvido';
ALTER TABLE knowledge_repairs ADD COLUMN IF NOT EXISTS repair_date DATE DEFAULT CURRENT_DATE;
ALTER TABLE knowledge_repairs ADD COLUMN IF NOT EXISTS archived_at TIMESTAMPTZ;

ALTER TABLE knowledge_assets ADD COLUMN IF NOT EXISTS manufacturer_id INTEGER REFERENCES knowledge_manufacturers(id) ON DELETE SET NULL;
ALTER TABLE knowledge_assets ADD COLUMN IF NOT EXISTS original_name VARCHAR(255);
ALTER TABLE knowledge_assets ADD COLUMN IF NOT EXISTS stored_name VARCHAR(255);
ALTER TABLE knowledge_assets ADD COLUMN IF NOT EXISTS file_hash VARCHAR(64);
ALTER TABLE knowledge_assets ADD COLUMN IF NOT EXISTS storage_path VARCHAR(255);
ALTER TABLE knowledge_assets ADD COLUMN IF NOT EXISTS user_id INTEGER REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE knowledge_assets ADD COLUMN IF NOT EXISTS description TEXT;
ALTER TABLE knowledge_assets ADD COLUMN IF NOT EXISTS uploaded_at TIMESTAMPTZ DEFAULT NOW();

UPDATE knowledge_assets
SET original_name = COALESCE(original_name, title),
    stored_name = COALESCE(stored_name, file_path),
    storage_path = COALESCE(storage_path, file_path),
    uploaded_at = COALESCE(uploaded_at, created_at)
WHERE original_name IS NULL OR uploaded_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_knowledge_assets_manufacturer ON knowledge_assets (manufacturer_id, category);
CREATE INDEX IF NOT EXISTS idx_knowledge_assets_hash ON knowledge_assets (file_hash);
CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_assets_hash_unique ON knowledge_assets (file_hash) WHERE file_hash IS NOT NULL;

ALTER TABLE knowledge_assets DROP CONSTRAINT IF EXISTS knowledge_assets_category_check;
ALTER TABLE knowledge_assets ADD CONSTRAINT knowledge_assets_category_check
CHECK (category IN ('arquivo','foto','video','pdf','boardview','link','datasheet','firmware','esquema','zip','documento'));

CREATE TABLE IF NOT EXISTS knowledge_favorites (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    target_type VARCHAR(30) NOT NULL CHECK (target_type IN ('article','repair','asset','schematic','model','manufacturer')),
    target_id INTEGER NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, target_type, target_id)
);
CREATE INDEX IF NOT EXISTS idx_knowledge_favorites_user ON knowledge_favorites (user_id, created_at DESC);

CREATE TABLE IF NOT EXISTS knowledge_article_history (
    id SERIAL PRIMARY KEY,
    article_id INTEGER NOT NULL REFERENCES knowledge_articles(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    change_type VARCHAR(40) NOT NULL,
    previous_version JSONB,
    new_version JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_knowledge_article_history_article ON knowledge_article_history (article_id, created_at DESC);

CREATE TABLE IF NOT EXISTS knowledge_search_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    query TEXT NOT NULL,
    filters JSONB,
    results_count INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_knowledge_search_logs_query ON knowledge_search_logs (lower(query));
CREATE INDEX IF NOT EXISTS idx_knowledge_search_logs_created ON knowledge_search_logs (created_at DESC);

CREATE TABLE IF NOT EXISTS knowledge_order_links (
    id SERIAL PRIMARY KEY,
    service_order_id INTEGER NOT NULL REFERENCES service_orders(id) ON DELETE CASCADE,
    target_type VARCHAR(30) NOT NULL CHECK (target_type IN ('article','repair','asset','schematic')),
    target_id INTEGER NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (service_order_id, target_type, target_id)
);

CREATE OR REPLACE FUNCTION knowledge_articles_search_update() RETURNS trigger AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('simple', COALESCE(NEW.title, '') || ' ' || COALESCE(NEW.slug, '') || ' ' || COALESCE(NEW.tags, '') || ' ' || COALESCE(NEW.category, '')), 'A') ||
        setweight(to_tsvector('simple', COALESCE(NEW.summary, '') || ' ' || COALESCE(NEW.symptoms, '') || ' ' || COALESCE(NEW.diagnosis, '') || ' ' || COALESCE(NEW.solution, '')), 'B') ||
        setweight(to_tsvector('simple', COALESCE(NEW.procedure, '') || ' ' || COALESCE(NEW.tools, '') || ' ' || COALESCE(NEW.parts_used, '') || ' ' || COALESCE(NEW.content, '') || ' ' || COALESCE(NEW.notes, '')), 'C');
    NEW.updated_at := NOW();
    RETURN NEW;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION knowledge_repairs_search_update() RETURNS trigger AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('simple', COALESCE(NEW.title, '') || ' ' || COALESCE(NEW.tags, '') || ' ' || COALESCE(NEW.result, '')), 'A') ||
        setweight(to_tsvector('simple', COALESCE(NEW.problem, '') || ' ' || COALESCE(NEW.symptoms, '') || ' ' || COALESCE(NEW.diagnosis, '') || ' ' || COALESCE(NEW.solution, '')), 'B') ||
        setweight(to_tsvector('simple', COALESCE(NEW.tests_performed, '') || ' ' || COALESCE(NEW.parts, '') || ' ' || COALESCE(NEW.tools, '') || ' ' || COALESCE(NEW.observations, '')), 'C');
    NEW.updated_at := NOW();
    RETURN NEW;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION knowledge_models_search_update() RETURNS trigger AS $$
BEGIN
    NEW.normalized_name := lower(regexp_replace(translate(NEW.name, 'ÁÀÂÃÄáàâãäÉÈÊËéèêëÍÌÎÏíìîïÓÒÔÕÖóòôõöÚÙÛÜúùûüÇçÑñ', 'AAAAAaaaaaEEEEeeeeIIIIiiiiOOOOOoooooUUUUuuuuCcNn'), '[^a-zA-Z0-9]+', '', 'g'));
    NEW.normalized_code := lower(regexp_replace(translate(COALESCE(NEW.code, ''), 'ÁÀÂÃÄáàâãäÉÈÊËéèêëÍÌÎÏíìîïÓÒÔÕÖóòôõöÚÙÛÜúùûüÇçÑñ', 'AAAAAaaaaaEEEEeeeeIIIIiiiiOOOOOoooooUUUUuuuuCcNn'), '[^a-zA-Z0-9]+', '', 'g'));
    NEW.search_vector :=
        setweight(to_tsvector('simple', COALESCE(NEW.name, '') || ' ' || COALESCE(NEW.code, '') || ' ' || COALESCE(NEW.tags, '')), 'A') ||
        setweight(to_tsvector('simple', COALESCE(NEW.description, '') || ' ' || COALESCE(NEW.frequent_issues, '') || ' ' || COALESCE(NEW.symptoms, '')), 'B') ||
        setweight(to_tsvector('simple', COALESCE(NEW.diagnosis, '') || ' ' || COALESCE(NEW.procedures, '') || ' ' || COALESCE(NEW.comments, '') || ' ' || COALESCE(NEW.observations, '')), 'C');
    NEW.updated_at := NOW();
    RETURN NEW;
END
$$ LANGUAGE plpgsql;

UPDATE knowledge_models SET name = name;
UPDATE knowledge_articles SET title = title;
UPDATE knowledge_repairs SET title = title;

COMMIT;
