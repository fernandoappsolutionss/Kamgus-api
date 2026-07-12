# T1 MySQL SQL Inventory

Scope: PHP files under `app/` and `routes/`, plus enum `CHECK` blocks from:

- `/private/tmp/claude-501/-Users-teamsolutionsslatam-Hermes-Agente-IA-PC/b0e009f3-dff2-4b64-9a86-bfaa80e643bd/scratchpad/apikamgusv2_pg.sql`
- `/private/tmp/claude-501/-Users-teamsolutionsslatam-Hermes-Agente-IA-PC/b0e009f3-dff2-4b64-9a86-bfaa80e643bd/scratchpad/proyecto_legacy_pg.sql`

## Search Criteria

Inventory was built from these MySQL-only or MySQL-risk patterns: `SHA2`, `UNIX_TIMESTAMP`, `TIMESTAMPDIFF`, `TIMEDIFF`, `CURRENT_DATE`, `MONTH/YEAR/DAY`, `ADDTIME`, `TIME_TO_SEC`, `IF`, `IFNULL`, `DATE_FORMAT`, `STR_TO_DATE`, `CONCAT`, and MySQL identifier `backticks`.

## SQL Findings And Resolution

| Pattern | Resolution |
| --- | --- |
| `SHA2(id, 256)` and `SHA2(driver_id, 256)` | Kept at call-sites and supported by `scripts/pg/0001_compat_functions.sql` via `pgcrypto` overloads for `text` and `bigint`. |
| `UNIX_TIMESTAMP` | Rewritten out of app queries. Compatibility shim remains as `unix_timestamp()` for smoke and future restored SQL. |
| `TIMEDIFF` plus `UNIX_TIMESTAMP(ADDTIME(...))` | Rewritten to Postgres interval arithmetic with `EXTRACT(EPOCH FROM (now() + (endTime::timestamp - startTime::timestamp))) * 1000` in customer/invited suggested-price responses. |
| `TIMESTAMPDIFF` | Rewritten in `CancelOldServices` and driver service count logic to Postgres `EXTRACT(EPOCH FROM (...)) / 60` or Carbon query bounds. |
| `CURRENT_DATE()` and `WEEK(...)` | Rewritten in dashboard/legacy balance queries to Postgres `CURRENT_DATE`, `DATE_TRUNC('week', CURRENT_DATE)` and PHP/Carbon bounds where query builder was clearer. |
| `MONTH/YEAR/DAY` | Rewritten in service status grouping to `EXTRACT(MONTH/YEAR/DAY FROM SS.created_at)`. |
| `ADDTIME` | Rewritten in migration code to `created_at::timestamp + tiempo::interval`. |
| `TIME_TO_SEC` | No active app/routes call-site remains; covered by inventory criterion and smoke checks for equivalent interval rewrites. |
| `IF(...)` | Rewritten to `CASE WHEN ... THEN ... ELSE ... END` in helpers, payout controller, license controller, payment manager, and balance queries. |
| `IFNULL` | No active app/routes call-site remains. Existing null handling uses `COALESCE` where SQL was touched. |
| `DATE_FORMAT` / `STR_TO_DATE` | No active app/routes call-site remains. |
| `CONCAT` / `concat` | Left in place. PostgreSQL supports `concat(...)`; uppercase `CONCAT` resolves case-insensitively. |
| MySQL `backticks` | Removed from executable app SQL except guarded `K_HelpersV1` legacy `mysql_old` SQL and comments. |

Current gate result for `app/` + `routes/`: no `TIMESTAMPDIFF`, `UNIX_TIMESTAMP`, `TIMEDIFF`, `current_date()`, or `test_refund` remains.

## Legacy `mysql_old`

`app/Classes/K_HelpersV1.php` still contains MySQL-shaped SQL for the old schema, including `GROUP_CONCAT` and backticks. This is intentional legacy code. Every `DB::connection("mysql_old")` access in `K_HelpersV1` is behind the `if(!self::ENABLE){ return false; }` guard, and the static test `T1PostgresCompatTest::testLegacyMysqlGuardPrecedesEveryMysqlOldConnection` locks that behavior.

## CHECK Inventory

`apikamgusv2_pg.sql` enum constraints require:

- `services.estado`: `ACTIVO`, `AGENDADO`, `INACTIVO`, `PENDIENTE`, `CANCELADO`, `ANULADO`, `TERMINADO`, `RESERVA`, `PROGRAMAR`, `REPETIR`
- `services.tipo_servicio`: `SIMPLE`, `MUDANZA`
- `services.tipo_pago`: `transferencia`, `Card`, `Efectivo`, `Yappy`
- `services.pago`: `PENDIENTE`, `PAGADO`, `ANULADO`, `TRANSFERIDO`
- `driver_services.status`: `Pendiente`, `Terminado`, `En curso`, `Agendado`, `Rechazado`
- `driver_services.confirmed`: `SI`, `NO`
- `driver_services.ispaid`: `Pagado`, `Pendiente`, `Omitido`, `Pagado Kamgus`

`proyecto_legacy_pg.sql` keeps legacy casing:

- `servicios.tipo_translado`: `Simple`, `Mudanza`
- `servicios.estado`: `Activo`, `Agendado`, `Inactivo`, `Pendiente`, `Cancelado`, `Anulado`, `Terminado`, `Reserva`, `Programar`, `Repetir`
- `conductor_servicios.estado`: `Rechazado`, `En Curso`, `Pendiente`, `Terminado`, `Agendado`

## CHECK Corrections Applied

- New v2 services now normalize request `tipo_translado` to `SIMPLE`/`MUDANZA`.
- v2 service state writes and filters were normalized to uppercase `services.estado` values.
- v2 driver service status writes and filters were normalized to `En curso`, `Agendado`, `Pendiente`, `Terminado`, or `Rechazado`.
- `driver_services.confirmed` writes now use `SI`/`NO`.
- v2 balance and payout filters now use `services.estado = 'TERMINADO'` and `services.pago = 'PENDIENTE'`.
- V1-to-V2 migration now normalizes `services.estado`, `services.tipo_servicio`, `services.pago`, `driver_services.status`, `driver_services.confirmed`, and `driver_services.ispaid`.
- `schema-only.sql` contains all enum `CHECK` constraints from both dump files without data rows; `smoke-fixtures.sql` adds only synthetic valid rows.
