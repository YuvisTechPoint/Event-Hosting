-- Event Hosting production PostgreSQL initialization
-- Database, user, and password are created via POSTGRES_* environment variables.

CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";
