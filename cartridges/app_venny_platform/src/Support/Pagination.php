<?php

declare(strict_types=1);

namespace VennyIO\Support;

/**
 * Shared offset pagination and sorting helper for Venny I/O collection endpoints.
 *
 * Cartridges should not implement their own pagination math. They should pass
 * their allowed columns/sort options into this platform helper and let the
 * platform normalize request input consistently.
 */
final class Pagination
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $options
     * @return array{page:int,per_page:int,limit:int,offset:int,sort:string,direction:string}
     */
    public static function fromInput(array $input, array $options = []): array
    {
        $defaultPerPage = self::positiveInt($options['default_per_page'] ?? 25, 25);
        $maxPerPage = self::positiveInt($options['max_per_page'] ?? 100, 100);
        $maxPerPage = max(1, $maxPerPage);

        $page = max(1, self::positiveInt($input['page'] ?? 1, 1));
        $perPage = self::positiveInt($input['per_page'] ?? $defaultPerPage, $defaultPerPage);
        $perPage = max(1, min($maxPerPage, $perPage));

        $sortableFields = $options['sortable_fields'] ?? [];
        if (!is_array($sortableFields)) {
            $sortableFields = [];
        }
        $sortableFields = array_values(array_filter($sortableFields, 'is_string'));

        $defaultSort = (string) ($options['default_sort'] ?? 'time_started');
        if ($defaultSort === '' || ($sortableFields !== [] && !in_array($defaultSort, $sortableFields, true))) {
            $defaultSort = $sortableFields[0] ?? 'time_started';
        }

        $sort = trim((string) ($input['sort'] ?? $defaultSort));
        if ($sort === '' || ($sortableFields !== [] && !in_array($sort, $sortableFields, true))) {
            $sort = $defaultSort;
        }

        $direction = strtolower(trim((string) ($input['direction'] ?? 'desc')));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        return [
            'page' => $page,
            'per_page' => $perPage,
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    /**
     * @param array{page:int,per_page:int,limit:int,offset:int,sort:string,direction:string} $pagination
     * @return array<string, mixed>
     */
    public static function meta(array $pagination, int $total): array
    {
        $page = max(1, (int) $pagination['page']);
        $perPage = max(1, (int) $pagination['per_page']);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => max(0, $total),
            'total_pages' => $totalPages,
            'has_next' => $totalPages > 0 && $page < $totalPages,
            'has_previous' => $page > 1 && $totalPages > 0,
            'sort' => $pagination['sort'],
            'direction' => $pagination['direction'],
        ];
    }

    private static function positiveInt(mixed $value, int $fallback): int
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return $fallback;
        }

        return max(1, (int) $value);
    }
}
