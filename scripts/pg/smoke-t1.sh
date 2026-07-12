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

SELECT CASE
    WHEN sha2('abc', 256) = 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad'
    THEN 1 ELSE 1 / 0
END AS sha2_text_ok;

SELECT CASE
    WHEN sha2(42::bigint, 256) = sha2('42', 256)
    THEN 1 ELSE 1 / 0
END AS sha2_bigint_ok;

SELECT CASE
    WHEN unix_timestamp() > 0
    THEN 1 ELSE 1 / 0
END AS unix_timestamp_ok;

SELECT CASE
    WHEN nextval('apikamgusv2.services_id_seq'::regclass) = 44
    THEN 1 ELSE 1 / 0
END AS apikamgusv2_services_sequence_ok;

SELECT CASE
    WHEN nextval('proyecto_legacy.servicios_idservicios_seq'::regclass) = 78
    THEN 1 ELSE 1 / 0
END AS proyecto_legacy_servicios_sequence_ok;

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
