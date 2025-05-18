CREATE TABLE users (
  id           SERIAL PRIMARY KEY,
  username     TEXT    UNIQUE NOT NULL,
  password     TEXT    NOT NULL,
  totp_secret  TEXT              -- NULL si no usa 2FA
);

CREATE TABLE files (
  id           SERIAL PRIMARY KEY,
  user_id      INTEGER NOT NULL REFERENCES users(id),
  filename     TEXT    NOT NULL,
  path         TEXT    NOT NULL,
  size         BIGINT  NOT NULL DEFAULT 0,       -- tamaño en bytes
  uploaded_at  TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE shares (
  id           SERIAL PRIMARY KEY,
  file_id      INTEGER NOT NULL REFERENCES files(id) ON DELETE CASCADE,
  token        TEXT    UNIQUE NOT NULL,
  expires_at   TIMESTAMP NULL,         -- NULL = sin caducidad
  created_at   TIMESTAMP NOT NULL DEFAULT now()
);
