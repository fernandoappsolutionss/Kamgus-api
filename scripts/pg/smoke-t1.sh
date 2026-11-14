#!/usr/bin/env bash
set -euo pipefail

DB_HOST="${DB_HOST:-pg}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:-kamgus_smoke}"
DB_USERNAME="${DB_USERNAME:-kamgus}"
DB_PASSWORD="${DB_PASSWORD:-kamgus}"
export PGPASSWORD="$DB_PASSWORD"

psql_cmd=(
  psql
  --host "$DB_HOST"
  --port "$DB_PORT"
  --username "$DB_USERNAME"
  --dbname "$DB_DATABASE"
  --set ON_ERROR_STOP=1
)

until pg_isready --host "$DB_HOST" --port "$DB_PORT" --username "$DB_USERNAME" --dbname "$DB_DATABASE"; do
  sleep 1
done

"${psql_cmd[@]}" --file scripts/pg/schema-only.sql
"${psql_cmd[@]}" --file scripts/pg/0001_compat_functions.sql
"${psql_cmd[@]}" --file scripts/pg/smoke-fixtures.sql
"${psql_cmd[@]}" --file scripts/pg/0002_sync_sequences.sql

"${psql_cmd[@]}" <<'SQL'
SET search_path = apikamgusv2, public;

-- Asserts en DO/RAISE: el idiom CASE...ELSE 1/0 revienta en plan-time con
-- funciones no-IMMUTABLE (el planner constant-foldea el 1/0 sin podar la rama).
DO $assert$
DECLARE
    seq_services bigint;
    seq_legacy bigint;
BEGIN
    IF sha2('abc', 256) <> 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad' THEN
        RAISE EXCEPTION 'ASSERT sha2_text_ok FALLO';
    END IF;
    RAISE NOTICE 'ASSERT OK: sha2_text';

    IF sha2(42::bigint, 256) <> sha2('42', 256) THEN
        RAISE EXCEPTION 'ASSERT sha2_bigint_ok FALLO';
    END IF;
    RAISE NOTICE 'ASSERT OK: sha2_bigint';

    IF unix_timestamp() <= 0 THEN
        RAISE EXCEPTION 'ASSERT unix_timestamp_ok FALLO';
    END IF;
    RAISE NOTICE 'ASSERT OK: unix_timestamp';

    seq_services := nextval('apikamgusv2.services_id_seq'::regclass);
    IF seq_services <> 44 THEN
        RAISE EXCEPTION 'ASSERT services_sequence FALLO: nextval=% esperado 44', seq_services;
    END IF;
    RAISE NOTICE 'ASSERT OK: apikamgusv2_services_sequence';

    seq_legacy := nextval('proyecto_legacy.servicios_idservicios_seq'::regclass);
    IF seq_legacy <> 78 THEN
        RAISE EXCEPTION 'ASSERT legacy_sequence FALLO: nextval=% esperado 78', seq_legacy;
    END IF;
    RAISE NOTICE 'ASSERT OK: proyecto_legacy_servicios_sequence';
END
$assert$;

DO $$
BEGIN
    BEGIN
        INSERT INTO apikamgusv2.services (tipo_transporte, tipo_servicio, estado, tipo_pago, pago)
        VALUES ('MOTO', 'Simple', 'Activo', 'Yappy', 'Pendiente');
        RAISE EXCEPTION 'Expected apikamgusv2.services CHECK violation';
    EXCEPTION WHEN check_violation THEN
        RAISE NOTICE 'apikamgusv2.services CHECK rejected legacy literals';
    END;

    BEGIN
        INSERT INTO apikamgusv2.driver_services (status, confirmed, ispaid)
        VALUES ('En Curso', 'No', 'pendiente');
        RAISE EXCEPTION 'Expected apikamgusv2.driver_services CHECK violation';
    EXCEPTION WHEN check_violation THEN
        RAISE NOTICE 'apikamgusv2.driver_services CHECK rejected legacy literals';
    END;
END;
$$;
SQL

php artisan config:clear
vendor/bin/phpunit --filter 'T1' --exclude-group external
