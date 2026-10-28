ALTER TABLE design_images
    ADD COLUMN check_score TINYINT NULL,
    ADD COLUMN check_result TEXT NULL,
    ADD COLUMN attempts TINYINT NOT NULL DEFAULT 0;
