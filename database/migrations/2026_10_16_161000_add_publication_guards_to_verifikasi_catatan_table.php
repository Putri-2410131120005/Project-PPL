<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Enforce publication consistency at the database layer for MySQL/MariaDB.
     * SQLite test runs skip these triggers; application service validation remains required.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Safe to re-run after an interrupted first attempt: rebuild only these triggers.
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_verifikasi_publikasi_insert`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_verifikasi_publikasi_update`');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_verifikasi_publikasi_insert`
BEFORE INSERT ON `verifikasi_catatan`
FOR EACH ROW
BEGIN
    IF (
        (NEW.id_publikator IS NULL AND NEW.tanggal_publikasi IS NOT NULL)
        OR (NEW.id_publikator IS NOT NULL AND NEW.tanggal_publikasi IS NULL)
        OR (NEW.id_publikator IS NOT NULL AND NEW.status_verifikasi <> 'Terverifikasi')
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Publikasi memerlukan GPK, tanggal, dan status Terverifikasi';
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER `trg_verifikasi_publikasi_update`
BEFORE UPDATE ON `verifikasi_catatan`
FOR EACH ROW
BEGIN
    IF (
        (NEW.id_publikator IS NULL AND NEW.tanggal_publikasi IS NOT NULL)
        OR (NEW.id_publikator IS NOT NULL AND NEW.tanggal_publikasi IS NULL)
        OR (NEW.id_publikator IS NOT NULL AND NEW.status_verifikasi <> 'Terverifikasi')
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Publikasi memerlukan GPK, tanggal, dan status Terverifikasi';
    END IF;
END
SQL);
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS `trg_verifikasi_publikasi_update`');
        DB::unprepared('DROP TRIGGER IF EXISTS `trg_verifikasi_publikasi_insert`');
    }
};
