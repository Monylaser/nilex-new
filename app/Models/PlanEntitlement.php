<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanEntitlement extends Model
{
    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_STRING = 'string';

    public const TIER_STARTER = 'starter';

    public const TIER_GROWTH = 'growth';

    public const TIER_PRO_SELLER = 'pro_seller';

    public const TIER_BUSINESS = 'business';

    public const TIER_RANKS = [
        self::TIER_STARTER    => 1,
        self::TIER_GROWTH     => 2,
        self::TIER_PRO_SELLER => 3,
        self::TIER_BUSINESS   => 4,
    ];

    protected $fillable = [
        'plan_tier',
        'feature_key',
        'value_type',
        'value',
        'description',
    ];

    public function castValue(): bool|int|string
    {
        return match ($this->value_type) {
            self::TYPE_BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_INTEGER => (int) $this->value,
            default            => (string) $this->value,
        };
    }

    public static function tierRank(?string $tier): int
    {
        if ($tier === null) {
            return 0;
        }

        return self::TIER_RANKS[$tier] ?? 0;
    }

    public static function shouldUpgradeTier(?string $current, string $incoming): bool
    {
        return self::tierRank($incoming) > self::tierRank($current);
    }
}
