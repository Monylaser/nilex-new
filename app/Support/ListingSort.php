<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListingSort
{
    public const DEFAULT = 'latest';

    public const OPTIONS = [
        'latest',
        'oldest',
        'price_asc',
        'price_desc',
    ];

    /**
     * Normalize a sort value. HTTP controllers validate `sort` with Rule::in()
     * before calling this — invalid query params return 422. This fallback is a
     * defensive default for internal/programmatic callers only.
     */
    public static function fromRequest(?string $sort): string
    {
        return in_array($sort, self::OPTIONS, true) ? $sort : self::DEFAULT;
    }

    public static function isValid(?string $sort): bool
    {
        return $sort === null || in_array($sort, self::OPTIONS, true);
    }

    public static function apply(Builder|Relation $query, string $sort, bool $qualify = false): Builder|Relation
    {
        $createdAt = $qualify ? 'listings.created_at' : 'created_at';
        $price = $qualify ? 'listings.price' : 'price';
        $id = $qualify ? 'listings.id' : 'id';

        return match ($sort) {
            'oldest' => $query->orderBy($createdAt)->orderBy($id),
            'price_asc' => $query->orderBy($price)->orderByDesc($createdAt),
            'price_desc' => $query->orderByDesc($price)->orderByDesc($createdAt),
            default => $query->orderByDesc($createdAt)->orderByDesc($id),
        };
    }
}
