<?php

namespace App\Support;

/**
 * Flat-file content store. Reads/writes data/{group}.json and merges over
 * the schema defaults in config/content.php so a missing key never breaks
 * a page. Bound as a singleton (per-request in-memory cache).
 */
class PageContent
{
    /** @var array<string,array> */
    protected array $cache = [];

    public function path(string $group): string
    {
        return base_path("data/{$group}.json");
    }

    public function schema(): array
    {
        return config('content', []);
    }

    /** Group metadata for the admin hub. */
    public function groups(): array
    {
        return collect($this->schema())->map(fn ($g, $key) => [
            'key'   => $key,
            'label' => $g['label'] ?? $key,
            'desc'  => $g['desc'] ?? '',
            'icon'  => $g['icon'] ?? 'doc',
        ])->values()->all();
    }

    public function fields(string $group): array
    {
        return data_get($this->schema(), "{$group}.fields", []);
    }

    /** Default values built from the schema. */
    public function defaults(string $group): array
    {
        $out = [];
        foreach ($this->fields($group) as $f) {
            $out[$f['key']] = $f['default'] ?? match ($f['type'] ?? 'text') {
                'list'   => [],
                'toggle' => false,
                'number' => 0,
                default  => '',
            };
        }
        return $out;
    }

    /** Read a content group (defaults merged with the saved JSON). */
    public function get(string $group): array
    {
        if (isset($this->cache[$group])) {
            return $this->cache[$group];
        }
        $data = $this->defaults($group);
        $path = $this->path($group);
        if (is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);
            if (is_array($json)) {
                $data = array_merge($data, $json);
            }
        }
        return $this->cache[$group] = $data;
    }

    /** Persist a content group to data/{group}.json (schema keys only). */
    public function save(string $group, array $data): void
    {
        $dir = base_path('data');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $allowed = array_keys($this->defaults($group));
        $clean   = array_intersect_key($data, array_flip($allowed));

        file_put_contents(
            $this->path($group),
            json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
        unset($this->cache[$group]);
    }

    public function exists(string $group): bool
    {
        return array_key_exists($group, $this->schema());
    }
}
