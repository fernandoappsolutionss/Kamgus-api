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

## Post-auditoría Sonnet 5 (2026-07-12)
- ALTA #1 corregido: `Drivers/ServicesController.php` accept — estados a MAYÚSCULAS (la oferta del conductor volvía siempre 0 y la transacción nunca commiteaba).
- ALTA #2 corregido: `Customers/ServiceController.php` isFirstService + 2 whereNotIn adicionales (531/654) con el mismo bug de caja — sin fix, el descuento de primer servicio se regalaba siempre y los filtros de activos no excluían nada.
- MEDIA #3 corregido: `Dashboard/NotificationsController.php` fcmtopics ahora respeta DISABLE_EXTERNAL_NOTIFICATIONS (hacía curl directo a FCM).
- MEDIA #5 corregido: mapeo `"Transferencia" => "transferencia"` (el CHECK solo acepta minúscula) en Customers e Inviteds.
- MEDIA #4 (GATE DE ENV): setear `DISABLE_EXTERNAL_NOTIFICATIONS=true` en Railway STAGING antes de exponer a QA — el default es fail-open (correcto para prod, riesgoso en staging con tokens reales).
- MEDIA #6 (nota): `calculateDriverBalanceOld` perdió el literal muerto "PagoCash" del filtro — sin filas ni escritores con esa caja exacta; confirmado inofensivo, documentado.
- BAJA #7 (mina para Ronda B): la reescritura de WEEK() en `K_HelpersV1::updateDriverServicePayment` (código muerto, ENABLE=false) cambia la semántica de semanas (dom-sáb → lun-dom) y era innecesaria (corre contra MySQL legacy); si Ronda B revive el double-write, restaurar el WEEK() original.
- BAJA #9 (Ronda B): endurecer el test de literales para verificar call-site por call-site (grep de whereIn("estado") con caja vieja).
