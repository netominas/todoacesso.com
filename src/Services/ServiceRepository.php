<?php
declare(strict_types=1);

namespace TodoAcceso\Services;

use TodoAcceso\Core\Database as DB;

final class ServiceRepository
{
    /** Columnas editables por update(). */
    private const COLUMNS = [
        'name', 'category', 'description', 'description_source', 'editorial', 'icon_path', 'icon_source_url',
        'hero_image_path', 'hero_image_source_url', 'featured', 'featured_order', 'indexable',
        'last_status', 'last_http_code', 'last_response_ms', 'last_error', 'last_checked_at', 'metadata_checked_at',
    ];

    public static function findByDomain(string $domain): ?array
    {
        return DB::one('SELECT * FROM services WHERE domain = ?', [$domain]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return DB::one('SELECT * FROM services WHERE slug = ?', [$slug]);
    }

    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM services WHERE id = ?', [$id]);
    }

    /** @param array<string,mixed> $data */
    public static function create(string $domain, array $data = []): array
    {
        $now = time();
        $slug = DomainNormalizer::slugFor($domain);
        $base = $slug;
        $n = 2;
        while (DB::one('SELECT 1 FROM services WHERE slug = ?', [$slug]) !== null) {
            $slug = $base . '-' . $n++;
        }
        $name = $data['name'] ?? self::guessName($domain);
        DB::run(
            'INSERT INTO services (domain, slug, name, category, description, description_source, editorial, featured, featured_order, indexable, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $domain, $slug, $name, $data['category'] ?? 'sitio', $data['description'] ?? null,
                $data['description_source'] ?? 'none', $data['editorial'] ?? null,
                (int) ($data['featured'] ?? 0), (int) ($data['featured_order'] ?? 0), (int) ($data['indexable'] ?? 0),
                $now, $now,
            ]
        );
        return self::findByDomain($domain) ?? throw new \RuntimeException('No se pudo crear el servicio.');
    }

    /** @param array<string,mixed> $fields */
    public static function update(int $id, array $fields): void
    {
        $sets = [];
        $params = [];
        foreach ($fields as $col => $value) {
            if (!in_array($col, self::COLUMNS, true)) {
                continue;
            }
            $sets[] = "$col = ?";
            $params[] = $value;
        }
        if (!$sets) {
            return;
        }
        $sets[] = 'updated_at = ?';
        $params[] = time();
        $params[] = $id;
        DB::run('UPDATE services SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    /** Actualiza el resultado de una verificación sin tocar updated_at (no es un cambio editorial). */
    public static function saveCheck(int $id, string $status, ?int $http, int $ms, ?string $error): void
    {
        DB::run(
            'UPDATE services SET last_status = ?, last_http_code = ?, last_response_ms = ?, last_error = ?, last_checked_at = ? WHERE id = ?',
            [$status, $http, $ms, $error, time(), $id]
        );
    }

    public static function incrementSearches(int $id): void
    {
        DB::run('UPDATE services SET search_count = search_count + 1 WHERE id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        DB::run('DELETE FROM services WHERE id = ?', [$id]);
    }

    public static function guessName(string $domain): string
    {
        $labels = explode('.', $domain);
        $second = count($labels) >= 3 && strlen($labels[count($labels) - 2]) <= 3 ? $labels[count($labels) - 3] : $labels[count($labels) - 2];
        $second = str_replace('-', ' ', $second);
        return mb_convert_case($second, MB_CASE_TITLE, 'UTF-8');
    }

    /** @return array<int,array<string,mixed>> */
    public static function featured(int $limit = 60): array
    {
        return DB::all('SELECT * FROM services WHERE featured = 1 ORDER BY featured_order ASC, name ASC LIMIT ' . (int) $limit);
    }

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public static function popular(int $page, int $perPage): array
    {
        $total = (int) DB::value('SELECT COUNT(*) FROM services WHERE indexable = 1');
        $offset = max(0, ($page - 1) * $perPage);
        $rows = DB::all(
            'SELECT * FROM services WHERE indexable = 1 ORDER BY search_count DESC, name ASC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
        );
        return ['rows' => $rows, 'total' => $total];
    }

    /** Servicios relacionados: misma categoría, destacados primero. */
    public static function related(array $service, int $limit = 6): array
    {
        $rows = DB::all(
            'SELECT * FROM services WHERE indexable = 1 AND id <> ? AND category = ? ORDER BY featured DESC, search_count DESC LIMIT ' . (int) $limit,
            [$service['id'], $service['category']]
        );
        if (count($rows) < $limit) {
            $ids = array_merge([(int) $service['id']], array_map(static fn ($r) => (int) $r['id'], $rows));
            $in = implode(',', array_fill(0, count($ids), '?'));
            $more = DB::all(
                "SELECT * FROM services WHERE indexable = 1 AND id NOT IN ($in) ORDER BY featured DESC, featured_order ASC LIMIT " . (int) ($limit - count($rows)),
                $ids
            );
            $rows = array_merge($rows, $more);
        }
        return $rows;
    }

    /** @return array<int,array<string,mixed>> */
    public static function sitemapRows(int $limit = 40000): array
    {
        return DB::all(
            'SELECT slug, updated_at, metadata_checked_at FROM services WHERE indexable = 1 ORDER BY id ASC LIMIT ' . (int) $limit
        );
    }

    /** @return array{rows:array<int,array<string,mixed>>,total:int} */
    public static function adminList(string $q, string $filter, int $page, int $perPage): array
    {
        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(domain LIKE ? OR name LIKE ?)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if ($filter === 'featured') {
            $where[] = 'featured = 1';
        } elseif ($filter === 'indexable') {
            $where[] = 'indexable = 1';
        } elseif ($filter === 'pending') {
            $where[] = 'indexable = 0';
        }
        $w = implode(' AND ', $where);
        $total = (int) DB::value("SELECT COUNT(*) FROM services WHERE $w", $params);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = DB::all(
            "SELECT s.*, (SELECT COUNT(*) FROM reports r WHERE r.service_id = s.id) AS report_count
             FROM services s WHERE $w ORDER BY " . ($filter === 'pending' ? 'search_count DESC' : 'id DESC') . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        );
        return ['rows' => $rows, 'total' => $total];
    }
}
