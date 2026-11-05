-- Replace only HASH_FROM_LOCAL before importing this statement.
-- Never put the plaintext password in this file.
-- Replace the name, username, and email placeholders during the controlled
-- production installation; do not commit the completed statement.

INSERT INTO `pengguna`
    (`id_role`, `nama`, `username`, `password`, `email`, `status_akun`, `is_super_admin`)
SELECT
    `id_role`, 'PRODUCTION_SUPERADMIN_NAME', 'PRODUCTION_SUPERADMIN_USERNAME',
    'HASH_FROM_LOCAL', 'PRODUCTION_SUPERADMIN_EMAIL', 'Aktif', 1
FROM `role`
WHERE `nama_role` = 'Admin'
LIMIT 1;
