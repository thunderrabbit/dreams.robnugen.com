-- Store remember-me tokens hashed (SHA-256 hex, 64 chars) instead of plaintext.
-- A leaked DB dump or backup previously contained ready-to-use session tokens
-- for every user. Existing rows cannot be re-hashed, because the plaintext IS
-- the token the browser holds, so they are dropped. Every user simply logs in
-- again once, which re-issues a hashed row.
--
-- Keep semicolons out of these comments. executeMultipleSQL() splits on them.
DELETE FROM `cookies`;

ALTER TABLE `cookies`
  MODIFY `cookie` CHAR(64) COLLATE utf8mb4_bin NOT NULL;
