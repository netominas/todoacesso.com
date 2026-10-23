<?php
declare(strict_types=1);

/**
 * Esquema de Todo Acceso. Marcadores: {PK}, {ENGINE}, {INT}, {BIGINT} (ver Migrator).
 * Las fechas son timestamps Unix en UTC.
 */
return [
    1 => [
        'CREATE TABLE IF NOT EXISTS services (
            id {PK},
            domain VARCHAR(253) NOT NULL,
            slug VARCHAR(120) NOT NULL,
            name VARCHAR(120) NOT NULL,
            category VARCHAR(30) NOT NULL DEFAULT \'sitio\',
            description TEXT NULL,
            description_source VARCHAR(10) NOT NULL DEFAULT \'none\',
            editorial TEXT NULL,
            icon_path VARCHAR(255) NULL,
            icon_source_url VARCHAR(600) NULL,
            hero_image_path VARCHAR(255) NULL,
            hero_image_source_url VARCHAR(600) NULL,
            featured TINYINT NOT NULL DEFAULT 0,
            featured_order {INT} NOT NULL DEFAULT 0,
            indexable TINYINT NOT NULL DEFAULT 0,
            search_count {INT} NOT NULL DEFAULT 0,
            last_status VARCHAR(20) NULL,
            last_http_code {INT} NULL,
            last_response_ms {INT} NULL,
            last_error VARCHAR(40) NULL,
            last_checked_at {BIGINT} NULL,
            metadata_checked_at {BIGINT} NULL,
            created_at {BIGINT} NOT NULL,
            updated_at {BIGINT} NOT NULL
        ) {ENGINE}',
        'CREATE UNIQUE INDEX IF NOT EXISTS ux_services_domain ON services (domain)',
        'CREATE UNIQUE INDEX IF NOT EXISTS ux_services_slug ON services (slug)',
        'CREATE INDEX IF NOT EXISTS ix_services_featured ON services (featured, featured_order)',
        'CREATE INDEX IF NOT EXISTS ix_services_popular ON services (indexable, search_count)',

        'CREATE TABLE IF NOT EXISTS reports (
            id {PK},
            service_id {INT} NOT NULL,
            problem_type VARCHAR(30) NOT NULL,
            reporter_hash CHAR(64) NOT NULL,
            period_key {BIGINT} NOT NULL,
            latitude DOUBLE NULL,
            longitude DOUBLE NULL,
            region VARCHAR(40) NULL,
            created_at {BIGINT} NOT NULL,
            CONSTRAINT fk_reports_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
        ) {ENGINE}',
        'CREATE UNIQUE INDEX IF NOT EXISTS ux_reports_dedupe ON reports (service_id, reporter_hash, problem_type, period_key)',
        'CREATE INDEX IF NOT EXISTS ix_reports_service_time ON reports (service_id, created_at)',
        'CREATE INDEX IF NOT EXISTS ix_reports_time ON reports (created_at)',
        'CREATE INDEX IF NOT EXISTS ix_reports_cooldown ON reports (service_id, reporter_hash, created_at)',

        'CREATE TABLE IF NOT EXISTS rate_limits (
            rl_key VARCHAR(100) NOT NULL PRIMARY KEY,
            window_start {BIGINT} NOT NULL,
            hits {INT} NOT NULL DEFAULT 0
        ) {ENGINE}',
    ],
];
