CREATE TABLE IF NOT EXISTS arma_users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(64) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(16) NOT NULL DEFAULT 'padrao',
  must_change_password SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS arma_categories (
  id SERIAL PRIMARY KEY,
  name VARCHAR(120) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS arma_assets (
  id SERIAL PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  description TEXT,
  category VARCHAR(120) NOT NULL DEFAULT 'Geral',
  ip_lan VARCHAR(64) DEFAULT '',
  ip_dmz VARCHAR(64) DEFAULT '',
  port VARCHAR(16) DEFAULT '',
  environment VARCHAR(32) NOT NULL DEFAULT 'Produção',
  url VARCHAR(255) DEFAULT '',
  tags TEXT,
  created_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS arma_audit_logs (
  id SERIAL PRIMARY KEY,
  username VARCHAR(64) NOT NULL,
  action VARCHAR(32) NOT NULL,
  details TEXT,
  created_at TIMESTAMP NOT NULL
);

INSERT INTO arma_categories (name) VALUES ('Geral') ON CONFLICT (name) DO NOTHING;
INSERT INTO arma_categories (name) VALUES ('Infraestrutura') ON CONFLICT (name) DO NOTHING;
INSERT INTO arma_categories (name) VALUES ('Aplicação') ON CONFLICT (name) DO NOTHING;
INSERT INTO arma_categories (name) VALUES ('Segurança') ON CONFLICT (name) DO NOTHING;
