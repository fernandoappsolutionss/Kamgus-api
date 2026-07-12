# IMPLEMENTATION NOTES T1

## Implementado

- Agregados `scripts/pg/0001_compat_functions.sql` y `scripts/pg/0002_sync_sequences.sql`.
  - `0001` crea `pgcrypto`, `sha2(text, integer)`, `sha2(bigint, integer)` y `unix_timestamp`.
  - `0002` sincroniza secuencias en `apikamgusv2` y `proyecto_legacy` usando `attname` real.
- Reescritos SQL MySQL-only activos: `TIMESTAMPDIFF`, `UNIX_TIMESTAMP`, `TIMEDIFF`, `ADDTIME`, `CURRENT_DATE()`, `MONTH/YEAR/DAY`, `IF(...)` y backticks ejecutables fuera de `mysql_old`.
- Creado `scripts/pg/mysql-sql-inventory.md` con inventario de patrones SQL y `CHECK` de ambos dumps.
- Normalizados literales contra `CHECK`:
  - `services.estado`: uppercase v2.
  - `services.tipo_servicio`: `SIMPLE` / `MUDANZA`.
  - `services.pago`: uppercase.
  - `driver_services.status`: title-case v2.
  - `driver_services.confirmed`: `SI` / `NO`.
- `K_HelpersV1` mantiene SQL legacy, pero todo acceso `DB::connection("mysql_old")` queda guardado por `if(!self::ENABLE)`.
- Stripe:
  - Configurado `services.stripe.key` y `services.stripe.secret`.
  - Separados `getPublishableKey()` y `getSecretKey()`.
  - Call-sites server-side usan secret; respuestas publicables usan publishable.
  - Eliminado hardcode/nombre legacy `STRIPE_SK` del código app/config/routes.
  - `tests/TestCase.php` falla si una respuesta HTTP contiene `sk_`.
- Rutas debug/peligrosas removidas y documentadas en `docs/api/debug-routes-removed.md`.
- Validaciones de monto actualizadas con `numeric|gt:0`.
- `DISABLE_EXTERNAL_NOTIFICATIONS`:
  - Helpers push devuelven `null`.
  - Firebase channel devuelve `[]`.
  - Resend devuelve resultado disabled sin HTTP.
  - Jobs de email retornan antes.
  - `config/mail.php` usa mailer `array` cuando el flag está activo.
- T1c:
  - `Dockerfile.test` sin copiar código de app; instala dependencias desde composer manifests.
  - `docker-compose.smoke.yml` con bind mount, Postgres 17 y red interna.
  - `.env.smoke`.
  - `scripts/pg/schema-only.sql` con `CHECK` enum sintéticos extraídos de dumps, sin datos reales.
  - `scripts/pg/smoke-fixtures.sql` con datos sintéticos.
  - `scripts/pg/smoke-t1.sh` aplica SQL, valida shims/constraints/secuencias y corre tests T1.
  - `tests/Unit/YappyTest.php` marcado `@group external`.

## Verificado localmente

- `php -l` sobre todos los PHP en `app/`, `config/`, `routes/`, `tests/`: OK.
- `bash -n scripts/pg/smoke-t1.sh`: OK.
- Gate sin resultados para `TIMESTAMPDIFF|UNIX_TIMESTAMP|TIMEDIFF|current_date()|test_refund` en `app/` y `routes/`.
- Gate sin resultados para `STRIPE_SK|sk_` en `app/`, `config/`, `routes/`.
- Gate sin rutas debug removidas en `routes/`.
- Guard de `mysql_old` en `K_HelpersV1`: OK.

## Pendiente para Hermes

- Ejecutar el smoke Docker:

```bash
docker compose -f docker-compose.smoke.yml up --build --abort-on-container-exit --exit-code-from api
```

- Confirmar que el contenedor `api` completa `scripts/pg/smoke-t1.sh`.
- Correr PHPUnit completo si Hermes quiere ampliar más allá de los tests T1. No lo ejecuté localmente porque este worktree no tiene `vendor/bin/phpunit`.
- No ejecuté Docker, no apliqué SQL a DB remota y no usé comandos `git`.
