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

    public static function fromRequest(?string $sort): string
    {
        return in_array($sort, self::OPTIONS, true) ? $sort : self::DEFAULT;
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
