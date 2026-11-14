DO $$
DECLARE
    sequence_record record;
    max_value bigint;
    next_value bigint;
    is_called boolean;
BEGIN
    FOR sequence_record IN
        SELECT
            n.nspname AS schema_name,
            c.relname AS table_name,
            a.attname AS attname,
            pg_get_serial_sequence(format('%I.%I', n.nspname, c.relname), a.attname) AS sequence_name
        FROM pg_class c
        JOIN pg_namespace n ON n.oid = c.relnamespace
        JOIN pg_attribute a ON a.attrelid = c.oid
        WHERE n.nspname IN ('apikamgusv2', 'proyecto_legacy')
            AND c.relkind IN ('r', 'p')
            AND a.attnum > 0
            AND NOT a.attisdropped
            AND pg_get_serial_sequence(format('%I.%I', n.nspname, c.relname), a.attname) IS NOT NULL
    LOOP
        EXECUTE format(
            'SELECT max(%I)::bigint FROM %I.%I',
            sequence_record.attname,
            sequence_record.schema_name,
            sequence_record.table_name
        )
        INTO max_value;

        next_value := COALESCE(GREATEST(max_value, 1), 1);
        is_called := COALESCE(max_value > 0, false);

        EXECUTE 'SELECT setval($1::regclass, $2::bigint, $3::boolean)'
        USING sequence_record.sequence_name, next_value, is_called;

        RAISE NOTICE 'Synced %.% column % with sequence % to value %, is_called=%',
            sequence_record.schema_name,
            sequence_record.table_name,
            sequence_record.attname,
            sequence_record.sequence_name,
            next_value,
            is_called;
    END LOOP;
END;
$$;
