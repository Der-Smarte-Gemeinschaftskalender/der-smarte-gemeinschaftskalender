-- Create Mobilizon database
CREATE DATABASE mobilizon;

-- Grant permissions to the laravel user for mobilizon database
GRANT ALL PRIVILEGES ON DATABASE mobilizon TO laravel;

-- Enable required extensions for Mobilizon
\c mobilizon;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "postgis";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "unaccent";