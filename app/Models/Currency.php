<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Currency extends Model
{
    /** @use HasFactory<\Database\Factories\CurrencyFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    /**
     * One currency can have many historical rate snapshots.
     *
     * @return HasMany<CurrencyRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(CurrencyRate::class);
    }

    /**
     * Convenience accessor for the most recent rate record.
     *
     * @return HasOne<CurrencyRate, $this>
     */
    public function latestRate(): HasOne
    {
        return $this->hasOne(CurrencyRate::class)->latestOfMany('fetched_at');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
