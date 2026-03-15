-- Idempotent patch for oa-planes cutover target
-- Adds users.auth_subject (unique nullable) and users.last_sso_login_at if missing.

SET @schema_name := DATABASE();

SET @has_auth_subject_column := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = @schema_name
    AND table_name = 'users'
    AND column_name = 'auth_subject'
);

SET @sql_add_auth_subject_column := IF(
  @has_auth_subject_column = 0,
  'ALTER TABLE `users` ADD COLUMN `auth_subject` VARCHAR(255) NULL AFTER `email`',
  'SELECT ''users.auth_subject already exists'''
);

PREPARE stmt FROM @sql_add_auth_subject_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_auth_subject_unique := (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = @schema_name
    AND table_name = 'users'
    AND column_name = 'auth_subject'
    AND non_unique = 0
);

SET @sql_add_auth_subject_unique := IF(
  @has_auth_subject_unique = 0,
  'ALTER TABLE `users` ADD UNIQUE KEY `users_auth_subject_unique` (`auth_subject`)',
  'SELECT ''users.auth_subject unique index already exists'''
);

PREPARE stmt FROM @sql_add_auth_subject_unique;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_last_sso_login_at_column := (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = @schema_name
    AND table_name = 'users'
    AND column_name = 'last_sso_login_at'
);

SET @sql_add_last_sso_login_at_column := IF(
  @has_last_sso_login_at_column = 0,
  'ALTER TABLE `users` ADD COLUMN `last_sso_login_at` TIMESTAMP NULL AFTER `email_verified_at`',
  'SELECT ''users.last_sso_login_at already exists'''
);

PREPARE stmt FROM @sql_add_last_sso_login_at_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
