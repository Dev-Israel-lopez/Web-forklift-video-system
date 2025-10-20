<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

class EventService
{
    /**
     * Retorna:
     *  - $events: últimos N eventos (<=100) desc
     *  - $statsOverall: totales globales por nivel (critical|warning|normal)
     *  - $statsFiltered: totales del conjunto filtrado (si hay filtros)
     */
    public function listLatestWithStats(array $opts): array
    {
        $limit = $opts['limit'] ?? 100;
        $level = $opts['level'] ?? null;          // puede venir como critical|warning|normal o red|yellow|green
        $save  = array_key_exists('save', $opts) ? (int)$opts['save'] : null;

        // Normalizar 'level' a inglés semántico (critical|warning|normal)
        $normalizedLevel = $level ? $this->normalizeLevel($level) : null;

        $cacheKey = 'events.index:' . http_build_query([
            'limit' => $limit,
            'level' => $normalizedLevel ?? $level,
            'save'  => $save,
        ]);

        return Cache::remember($cacheKey, 30, function () use ($limit, $normalizedLevel, $save) {
            // Query base para los eventos
            $query = Event::query()
                ->select([
                    'id','user_id','timestamp_str','event','zone_idx','level',
                    'save','filename','forklift_name','confidence','meta','created_at',
                ])
                ->with(['user:id,name'])
                ->orderByDesc('id')
                ->limit($limit);

            if ($normalizedLevel !== null) {
                // filtrar por el nivel normalizado (también acepta colores en DB)
                $query->where(function ($q) use ($normalizedLevel) {
                    [$crit, $warn, $norm] = $this->levelAliases();
                    if ($normalizedLevel === 'critical') {
                        $q->whereIn(\DB::raw('LOWER(level)'), $crit);
                    } elseif ($normalizedLevel === 'warning') {
                        $q->whereIn(\DB::raw('LOWER(level)'), $warn);
                    } else { // normal
                        $q->whereIn(\DB::raw('LOWER(level)'), $norm);
                    }
                });
            }

            if ($save !== null) {
                $query->where('save', $save);
            }

            /** @var Collection $events */
            $events = $query->get();

            // Stats globales en toda la tabla (para los recuadros del dashboard)
            $statsOverall = $this->countsByLevel(); // cachea internamente

            // Stats del conjunto filtrado (para que el front sepa lo que trajo la query)
            $statsFiltered = $this->countsByLevel($normalizedLevel, $save);

            return [$events, $statsOverall, $statsFiltered];
        });
    }

    /**
     * Cuenta por nivel (critical|warning|normal). Si se pasan filtros, cuenta sobre ellos.
     * Acepta que en DB 'level' pueda ser critical|warning|normal o red|yellow|green (case-insensitive).
     */
    private function countsByLevel(?string $normalizedLevel = null, ?int $save = null): array
    {
        [$crit, $warn, $norm] = $this->levelAliases();

        $base = Event::query();

        // Filtros opcionales
        if ($normalizedLevel !== null) {
            $base->where(function ($q) use ($normalizedLevel, $crit, $warn, $norm) {
                if ($normalizedLevel === 'critical') {
                    $q->whereIn(\DB::raw('LOWER(level)'), $crit);
                } elseif ($normalizedLevel === 'warning') {
                    $q->whereIn(\DB::raw('LOWER(level)'), $warn);
                } else {
                    $q->whereIn(\DB::raw('LOWER(level)'), $norm);
                }
            });
        }
        if ($save !== null) {
            $base->where('save', $save);
        }

        // Un solo SELECT con SUM(CASE ...) para eficiencia
        $row = $base->selectRaw("
            SUM(CASE WHEN LOWER(level) IN ('critical','red')   THEN 1 ELSE 0 END) AS critical,
            SUM(CASE WHEN LOWER(level) IN ('warning','yellow') THEN 1 ELSE 0 END) AS warning,
            SUM(CASE WHEN LOWER(level) IN ('normal','green')   THEN 1 ELSE 0 END) AS normal
        ")->first();

        return [
            'critical' => (int) ($row->critical ?? 0),
            'warning'  => (int) ($row->warning ?? 0),
            'normal'   => (int) ($row->normal ?? 0),
        ];
    }

    /**
     * Mapea entradas tipo 'rojo/amarillo/verde' o 'red/yellow/green' a critical|warning|normal
     */
    private function normalizeLevel(string $value): string
    {
        $v = Str::lower(trim($value));
        return match ($v) {
            'critical','rojo','red'   => 'critical',
            'warning','amarillo','yellow' => 'warning',
            'normal','verde','green'  => 'normal',
            default => $v, // si ya viene mapeado o es un valor custom
        };
    }

    /**
     * Aliases aceptados en DB para cada nivel.
     * Se devuelven lowercased.
     */
    private function levelAliases(): array
    {
        return [
            // critical
            ['critical','red','rojo'],
            // warning
            ['warning','yellow','amarillo'],
            // normal
            ['normal','green','verde'],
        ];
    }
}
