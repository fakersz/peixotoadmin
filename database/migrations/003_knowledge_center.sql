-- =============================================================
-- iFixAdmin — Centro de Conhecimento
-- Reaproveita schematics e adiciona artigos, reparos, modelos e busca.
-- =============================================================

BEGIN;

ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'admin'
    CHECK (role IN ('admin','tecnico','usuario'));

CREATE TABLE IF NOT EXISTS knowledge_manufacturers (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(80) NOT NULL UNIQUE,
    slug       VARCHAR(90) NOT NULL UNIQUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO knowledge_manufacturers (name, slug)
SELECT name, slug
FROM (VALUES
    ('Apple', 'apple'), ('Dell', 'dell'), ('Lenovo', 'lenovo'), ('Acer', 'acer'),
    ('Samsung', 'samsung'), ('Asus', 'asus'), ('HP', 'hp'), ('Positivo', 'positivo'),
    ('Vaio', 'vaio'), ('Microsoft', 'microsoft'), ('LG', 'lg'), ('Intel', 'intel'),
    ('AMD', 'amd'), ('Outros', 'outros')
) AS v(name, slug)
ON CONFLICT (name) DO NOTHING;

CREATE TABLE IF NOT EXISTS knowledge_models (
    id              SERIAL PRIMARY KEY,
    manufacturer_id INTEGER NOT NULL REFERENCES knowledge_manufacturers(id) ON DELETE RESTRICT,
    parent_id       INTEGER REFERENCES knowledge_models(id) ON DELETE SET NULL,
    name            VARCHAR(140) NOT NULL,
    code            VARCHAR(60),
    description     TEXT,
    frequent_issues TEXT,
    symptoms        TEXT,
    diagnosis       TEXT,
    procedures      TEXT,
    comments        TEXT,
    observations    TEXT,
    tags            VARCHAR(255),
    search_vector   TSVECTOR,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE (manufacturer_id, code)
);
CREATE INDEX IF NOT EXISTS idx_knowledge_models_manufacturer ON knowledge_models (manufacturer_id, name);
CREATE INDEX IF NOT EXISTS idx_knowledge_models_code ON knowledge_models (UPPER(code));
CREATE INDEX IF NOT EXISTS idx_knowledge_models_search ON knowledge_models USING GIN (search_vector);

CREATE TABLE IF NOT EXISTS knowledge_articles (
    id              SERIAL PRIMARY KEY,
    manufacturer_id INTEGER REFERENCES knowledge_manufacturers(id) ON DELETE SET NULL,
    model_id        INTEGER REFERENCES knowledge_models(id) ON DELETE SET NULL,
    title           VARCHAR(180) NOT NULL,
    type            VARCHAR(40) NOT NULL DEFAULT 'artigo'
                    CHECK (type IN ('artigo','procedimento','diagnostico','datasheet','firmware','link','arquivo')),
    status          VARCHAR(20) NOT NULL DEFAULT 'rascunho'
                    CHECK (status IN ('rascunho','publicado','arquivado')),
    summary         TEXT,
    content         TEXT,
    tags            VARCHAR(255),
    favorite        BOOLEAN NOT NULL DEFAULT FALSE,
    views_count     INTEGER NOT NULL DEFAULT 0,
    searches_count  INTEGER NOT NULL DEFAULT 0,
    author_user_id  INTEGER REFERENCES users(id) ON DELETE SET NULL,
    source_type     VARCHAR(40),
    source_id       INTEGER,
    published_at    TIMESTAMPTZ,
    search_vector   TSVECTOR,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_knowledge_articles_model ON knowledge_articles (model_id, status);
CREATE INDEX IF NOT EXISTS idx_knowledge_articles_type ON knowledge_articles (type, status);
CREATE INDEX IF NOT EXISTS idx_knowledge_articles_search ON knowledge_articles USING GIN (search_vector);

CREATE TABLE IF NOT EXISTS knowledge_repairs (
    id               SERIAL PRIMARY KEY,
    manufacturer_id  INTEGER REFERENCES knowledge_manufacturers(id) ON DELETE SET NULL,
    model_id         INTEGER REFERENCES knowledge_models(id) ON DELETE SET NULL,
    service_order_id INTEGER REFERENCES service_orders(id) ON DELETE SET NULL,
    client_id        INTEGER REFERENCES clients(id) ON DELETE SET NULL,
    device_id        INTEGER REFERENCES devices(id) ON DELETE SET NULL,
    title            VARCHAR(180) NOT NULL,
    problem          TEXT,
    diagnosis        TEXT,
    solution         TEXT,
    parts            TEXT,
    time_minutes     INTEGER,
    observations     TEXT,
    tags             VARCHAR(255),
    status           VARCHAR(20) NOT NULL DEFAULT 'rascunho'
                     CHECK (status IN ('rascunho','publicado','arquivado')),
    author_user_id   INTEGER REFERENCES users(id) ON DELETE SET NULL,
    search_vector    TSVECTOR,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_knowledge_repairs_model ON knowledge_repairs (model_id, status);
CREATE INDEX IF NOT EXISTS idx_knowledge_repairs_os ON knowledge_repairs (service_order_id);
CREATE INDEX IF NOT EXISTS idx_knowledge_repairs_search ON knowledge_repairs USING GIN (search_vector);

CREATE TABLE IF NOT EXISTS knowledge_assets (
    id          SERIAL PRIMARY KEY,
    model_id    INTEGER REFERENCES knowledge_models(id) ON DELETE SET NULL,
    article_id  INTEGER REFERENCES knowledge_articles(id) ON DELETE CASCADE,
    repair_id   INTEGER REFERENCES knowledge_repairs(id) ON DELETE CASCADE,
    schematic_id INTEGER REFERENCES schematics(id) ON DELETE SET NULL,
    category    VARCHAR(30) NOT NULL
                CHECK (category IN ('arquivo','foto','video','pdf','boardview','link','datasheet','firmware')),
    title       VARCHAR(180) NOT NULL,
    file_path   VARCHAR(255),
    external_link VARCHAR(600),
    mime_type   VARCHAR(120),
    size_bytes  INTEGER,
    tags        VARCHAR(255),
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_knowledge_assets_model ON knowledge_assets (model_id, category);
CREATE INDEX IF NOT EXISTS idx_knowledge_assets_article ON knowledge_assets (article_id);
CREATE INDEX IF NOT EXISTS idx_knowledge_assets_repair ON knowledge_assets (repair_id);

ALTER TABLE schematics ADD COLUMN IF NOT EXISTS manufacturer_id INTEGER REFERENCES knowledge_manufacturers(id) ON DELETE SET NULL;
ALTER TABLE schematics ADD COLUMN IF NOT EXISTS knowledge_model_id INTEGER REFERENCES knowledge_models(id) ON DELETE SET NULL;
ALTER TABLE schematics ADD COLUMN IF NOT EXISTS tags VARCHAR(255);
ALTER TABLE schematics ADD COLUMN IF NOT EXISTS favorite BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE schematics ADD COLUMN IF NOT EXISTS views_count INTEGER NOT NULL DEFAULT 0;
ALTER TABLE schematics ADD COLUMN IF NOT EXISTS searches_count INTEGER NOT NULL DEFAULT 0;
ALTER TABLE schematics ADD COLUMN IF NOT EXISTS search_vector TSVECTOR;
CREATE INDEX IF NOT EXISTS idx_schematics_knowledge_model ON schematics (knowledge_model_id);
CREATE INDEX IF NOT EXISTS idx_schematics_search ON schematics USING GIN (search_vector);

CREATE OR REPLACE FUNCTION knowledge_models_search_update() RETURNS trigger AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('simple', COALESCE(NEW.name, '') || ' ' || COALESCE(NEW.code, '')), 'A') ||
        setweight(to_tsvector('simple', COALESCE(NEW.description, '') || ' ' || COALESCE(NEW.frequent_issues, '') || ' ' || COALESCE(NEW.symptoms, '')), 'B') ||
        setweight(to_tsvector('simple', COALESCE(NEW.diagnosis, '') || ' ' || COALESCE(NEW.procedures, '') || ' ' || COALESCE(NEW.tags, '')), 'C');
    NEW.updated_at := NOW();
    RETURN NEW;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION knowledge_articles_search_update() RETURNS trigger AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('simple', COALESCE(NEW.title, '') || ' ' || COALESCE(NEW.tags, '')), 'A') ||
        setweight(to_tsvector('simple', COALESCE(NEW.summary, '')), 'B') ||
        setweight(to_tsvector('simple', COALESCE(NEW.content, '')), 'C');
    NEW.updated_at := NOW();
    RETURN NEW;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION knowledge_repairs_search_update() RETURNS trigger AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('simple', COALESCE(NEW.title, '') || ' ' || COALESCE(NEW.tags, '')), 'A') ||
        setweight(to_tsvector('simple', COALESCE(NEW.problem, '') || ' ' || COALESCE(NEW.diagnosis, '')), 'B') ||
        setweight(to_tsvector('simple', COALESCE(NEW.solution, '') || ' ' || COALESCE(NEW.parts, '') || ' ' || COALESCE(NEW.observations, '')), 'C');
    NEW.updated_at := NOW();
    RETURN NEW;
END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION schematics_search_update() RETURNS trigger AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('simple', COALESCE(NEW.title, '') || ' ' || COALESCE(NEW.model_code, '') || ' ' || COALESCE(NEW.board_code, '')), 'A') ||
        setweight(to_tsvector('simple', COALESCE(NEW.device_category, '') || ' ' || COALESCE(NEW.tags, '')), 'B') ||
        setweight(to_tsvector('simple', COALESCE(NEW.notes, '') || ' ' || COALESCE(NEW.external_link, '')), 'C');
    RETURN NEW;
END
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_knowledge_models_search ON knowledge_models;
CREATE TRIGGER trg_knowledge_models_search
BEFORE INSERT OR UPDATE ON knowledge_models
FOR EACH ROW EXECUTE FUNCTION knowledge_models_search_update();

DROP TRIGGER IF EXISTS trg_knowledge_articles_search ON knowledge_articles;
CREATE TRIGGER trg_knowledge_articles_search
BEFORE INSERT OR UPDATE ON knowledge_articles
FOR EACH ROW EXECUTE FUNCTION knowledge_articles_search_update();

DROP TRIGGER IF EXISTS trg_knowledge_repairs_search ON knowledge_repairs;
CREATE TRIGGER trg_knowledge_repairs_search
BEFORE INSERT OR UPDATE ON knowledge_repairs
FOR EACH ROW EXECUTE FUNCTION knowledge_repairs_search_update();

DROP TRIGGER IF EXISTS trg_schematics_search ON schematics;
CREATE TRIGGER trg_schematics_search
BEFORE INSERT OR UPDATE ON schematics
FOR EACH ROW EXECUTE FUNCTION schematics_search_update();

WITH apple AS (
    SELECT id FROM knowledge_manufacturers WHERE name = 'Apple'
), existing AS (
    SELECT DISTINCT model_code
    FROM schematics
    WHERE model_code IS NOT NULL AND trim(model_code) <> ''
)
INSERT INTO knowledge_models (manufacturer_id, name, code, description, tags)
SELECT apple.id, model_code, model_code, 'Modelo importado automaticamente a partir de Esquemas Elétricos.', 'importado, esquema'
FROM apple, existing
ON CONFLICT (manufacturer_id, code) DO NOTHING;

UPDATE schematics s
SET manufacturer_id = m.manufacturer_id,
    knowledge_model_id = m.id
FROM knowledge_models m
JOIN knowledge_manufacturers mf ON mf.id = m.manufacturer_id
WHERE mf.name = 'Apple'
  AND s.model_code IS NOT NULL
  AND UPPER(m.code) = UPPER(s.model_code)
  AND (s.knowledge_model_id IS NULL OR s.manufacturer_id IS NULL);

UPDATE knowledge_models SET name = name;
UPDATE knowledge_articles SET title = title;
UPDATE knowledge_repairs SET title = title;
UPDATE schematics SET title = title;

COMMIT;
