-- =============================================================================
-- Migrate image URLs in DB from HostPapa to Supabase Storage.
--
-- Patterns being rewritten:
--   1) https://apikamgusv2.kamgus.com/storage/<path>  → Supabase public URL
--   2) http://www.api.kamgus.com/<path>                → Supabase public URL
--
-- Patterns being LEFT ALONE (handled in phase 2):
--   - http://kamgus.com/dashboard/...  (files only on HostPapa, not in our snapshot)
--   - Bare filenames like "1654092522foo.jpg" (legacy local paths, files not snapshotted)
--   - External absolute URLs (e.g. ve.all.biz) — those aren't ours
--
-- Run inside a single transaction so we can ROLLBACK if anything looks wrong.
-- =============================================================================

BEGIN;

-- Helpful sentinels so we can verify the rewrite worked
\set SUPABASE_PUB 'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/'

-- ----------------------------------------------------------------------------
-- 1. apikamgusv2.images.url  (2,236 URLs — the biggest one)
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.images
SET url = REPLACE(url,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url LIKE 'https://apikamgusv2.kamgus.com/storage/%';

UPDATE apikamgusv2.images
SET url = REPLACE(url,
    'http://www.api.kamgus.com/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url LIKE 'http://www.api.kamgus.com/%';

-- ----------------------------------------------------------------------------
-- 2. apikamgusv2.articles.url_imagen  (79 URLs — all modern)
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.articles
SET url_imagen = REPLACE(url_imagen,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_imagen LIKE 'https://apikamgusv2.kamgus.com/storage/%';

-- ----------------------------------------------------------------------------
-- 3. apikamgusv2.drivers.url_foto_perfil  (43 URLs)
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.drivers
SET url_foto_perfil = REPLACE(url_foto_perfil,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_foto_perfil LIKE 'https://apikamgusv2.kamgus.com/storage/%';

UPDATE apikamgusv2.drivers
SET url_foto_perfil = REPLACE(url_foto_perfil,
    'http://www.api.kamgus.com/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_foto_perfil LIKE 'http://www.api.kamgus.com/%';

-- ----------------------------------------------------------------------------
-- 4. apikamgusv2.customers.url_foto_perfil  (3 URLs, most are NULL)
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.customers
SET url_foto_perfil = REPLACE(url_foto_perfil,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_foto_perfil LIKE 'https://apikamgusv2.kamgus.com/storage/%';

UPDATE apikamgusv2.customers
SET url_foto_perfil = REPLACE(url_foto_perfil,
    'http://www.api.kamgus.com/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_foto_perfil LIKE 'http://www.api.kamgus.com/%';

-- ----------------------------------------------------------------------------
-- 5. apikamgusv2.documents.url_foto  (108 URLs)
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.documents
SET url_foto = REPLACE(url_foto,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_foto LIKE 'https://apikamgusv2.kamgus.com/storage/%';

UPDATE apikamgusv2.documents
SET url_foto = REPLACE(url_foto,
    'http://www.api.kamgus.com/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_foto LIKE 'http://www.api.kamgus.com/%';

-- ----------------------------------------------------------------------------
-- 6. apikamgusv2.companies.url_foto_perfil
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.companies
SET url_foto_perfil = REPLACE(url_foto_perfil,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_foto_perfil LIKE 'https://apikamgusv2.kamgus.com/storage/%';

UPDATE apikamgusv2.companies
SET url_foto_perfil = REPLACE(url_foto_perfil,
    'http://www.api.kamgus.com/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_foto_perfil LIKE 'http://www.api.kamgus.com/%';

-- ----------------------------------------------------------------------------
-- 7. apikamgusv2.types_transports (url_foto + foto)
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.types_transports
SET url_foto = REPLACE(url_foto,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_foto LIKE 'https://apikamgusv2.kamgus.com/storage/%';

UPDATE apikamgusv2.types_transports
SET foto = REPLACE(foto,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE foto LIKE 'https://apikamgusv2.kamgus.com/storage/%';

-- ----------------------------------------------------------------------------
-- 8. apikamgusv2.categories.url_imagen + sub_categories + custom_articles
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.categories
SET url_imagen = REPLACE(url_imagen,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_imagen LIKE 'https://apikamgusv2.kamgus.com/storage/%';

UPDATE apikamgusv2.sub_categories
SET url_imagen = REPLACE(url_imagen,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_imagen LIKE 'https://apikamgusv2.kamgus.com/storage/%';

UPDATE apikamgusv2.custom_articles
SET url_imagen = REPLACE(url_imagen,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE url_imagen LIKE 'https://apikamgusv2.kamgus.com/storage/%';

-- ----------------------------------------------------------------------------
-- Verification
-- ----------------------------------------------------------------------------
SELECT 'images.url' AS col,
       count(*) FILTER (WHERE url LIKE 'https://xobgiiyizjtvezmshyvf.supabase.co/%') AS supabase,
       count(*) FILTER (WHERE url LIKE 'https://apikamgusv2.kamgus.com/%' OR url LIKE 'http://www.api.kamgus.com/%') AS remaining_old,
       count(*) AS total
FROM apikamgusv2.images;

-- IMPORTANT: review the verification numbers BEFORE committing.
-- If they look right, run:  COMMIT;
-- If anything's wrong, run: ROLLBACK;
COMMIT;
