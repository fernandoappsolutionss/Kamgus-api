-- =============================================================================
-- Phase 1b URL cleanup — catches columns missed in update_image_urls.sql
--
-- 1. apikamgusv2.types_transports.app_icon          → Supabase public URL
-- 2. apikamgusv2.types_transports.app_icon_selected → Supabase public URL
-- 3. apikamgusv2.types_transports.foto              → Supabase public URL
--    (files migrated separately from _hosting_snapshot/invitados_angular/
--     to kamgus-public/app-usuario/vehicles/, see Bash log above.)
--
-- Moto's foto (20200821170818_photo.png) wasn't in the invitados snapshot,
-- so we point it at the existing url_foto for that row (it's estado=0 anyway).
-- =============================================================================

BEGIN;

-- ----------------------------------------------------------------------------
-- app_icon / app_icon_selected: files already at kamgus-public/app-usuario/iconos/
-- (uploaded in the original storage migration), just rewrite the URL prefix.
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.types_transports
SET app_icon = REPLACE(app_icon,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE app_icon LIKE 'https://apikamgusv2.kamgus.com/storage/%';

UPDATE apikamgusv2.types_transports
SET app_icon_selected = REPLACE(app_icon_selected,
    'https://apikamgusv2.kamgus.com/storage/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/')
WHERE app_icon_selected LIKE 'https://apikamgusv2.kamgus.com/storage/%';

-- ----------------------------------------------------------------------------
-- foto: invitados.kamgus.com/assets/images/vehicles/* → kamgus-public/app-usuario/vehicles/*
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.types_transports
SET foto = REPLACE(foto,
    'https://invitados.kamgus.com/assets/images/vehicles/',
    'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/app-usuario/vehicles/')
WHERE foto LIKE 'https://invitados.kamgus.com/assets/images/vehicles/%';

-- Moto (id=5): the snapshot was missing 20200821170818_photo.png. Reuse url_foto
-- so the row still has a usable image.
UPDATE apikamgusv2.types_transports
SET foto = url_foto
WHERE id = 5
  AND foto LIKE 'https://xobgiiyizjtvezmshyvf.supabase.co/storage/v1/object/public/kamgus-public/app-usuario/vehicles/20200821170818_photo.png';

-- ----------------------------------------------------------------------------
-- Panel (id=1): url_foto pointed to ve.all.biz (external, 403). Point it at the
-- newly-migrated foto so no row has a broken image.
-- ----------------------------------------------------------------------------
UPDATE apikamgusv2.types_transports
SET url_foto = foto
WHERE url_foto LIKE 'https://ve.all.biz/%';

-- ----------------------------------------------------------------------------
-- Verification
-- ----------------------------------------------------------------------------
SELECT id, nombre,
       (foto LIKE 'https://xobgiiyizjtvezmshyvf.supabase.co/%')              AS foto_ok,
       (app_icon LIKE 'https://xobgiiyizjtvezmshyvf.supabase.co/%')           AS icon_ok,
       (app_icon_selected LIKE 'https://xobgiiyizjtvezmshyvf.supabase.co/%'
         OR app_icon_selected = '0')                                          AS icon_sel_ok
FROM apikamgusv2.types_transports
ORDER BY id;

COMMIT;
