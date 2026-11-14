CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE SCHEMA IF NOT EXISTS apikamgusv2;
CREATE SCHEMA IF NOT EXISTS proyecto_legacy;

CREATE OR REPLACE FUNCTION public.sha2(input text, bits integer)
RETURNS text
LANGUAGE plpgsql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
BEGIN
    IF bits <> 256 THEN
        RAISE EXCEPTION 'sha2 compatibility shim only supports 256 bits, got %', bits
            USING ERRCODE = '22023';
    END IF;

    RETURN encode(digest(input, 'sha256'), 'hex');
END;
$$;

CREATE OR REPLACE FUNCTION public.sha2(input bigint, bits integer)
RETURNS text
LANGUAGE sql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
    SELECT public.sha2(input::text, bits);
$$;

CREATE OR REPLACE FUNCTION public.unix_timestamp()
RETURNS bigint
LANGUAGE sql
STABLE
PARALLEL SAFE
AS $$
    SELECT floor(extract(epoch FROM now()))::bigint;
$$;

CREATE OR REPLACE FUNCTION public.unix_timestamp(input timestamp without time zone)
RETURNS bigint
LANGUAGE sql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
    SELECT floor(extract(epoch FROM input))::bigint;
$$;

CREATE OR REPLACE FUNCTION public.unix_timestamp(input timestamp with time zone)
RETURNS bigint
LANGUAGE sql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
    SELECT floor(extract(epoch FROM input))::bigint;
$$;

CREATE OR REPLACE FUNCTION apikamgusv2.sha2(input text, bits integer)
RETURNS text
LANGUAGE sql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
    SELECT public.sha2(input, bits);
$$;

CREATE OR REPLACE FUNCTION apikamgusv2.sha2(input bigint, bits integer)
RETURNS text
LANGUAGE sql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
    SELECT public.sha2(input, bits);
$$;

CREATE OR REPLACE FUNCTION proyecto_legacy.sha2(input text, bits integer)
RETURNS text
LANGUAGE sql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
    SELECT public.sha2(input, bits);
$$;

CREATE OR REPLACE FUNCTION proyecto_legacy.sha2(input bigint, bits integer)
RETURNS text
LANGUAGE sql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
    SELECT public.sha2(input, bits);
$$;
