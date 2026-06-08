-- Migration v2: almacenamiento de archivos en BD (fallback sin R2)
-- Ejecutar una sola vez en Railway vía MySQL console

ALTER TABLE `revistas`
    MODIFY COLUMN `portada_url` LONGTEXT DEFAULT NULL,
    ADD COLUMN `pdf_blob` LONGBLOB DEFAULT NULL AFTER `pdf_url`;

ALTER TABLE `revistas_en`
    MODIFY COLUMN `portada_url` LONGTEXT DEFAULT NULL,
    ADD COLUMN `pdf_blob` LONGBLOB DEFAULT NULL AFTER `pdf_url`;
