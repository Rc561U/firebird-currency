<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyRate extends Model
{
    /** @use HasFactory<\Database\Factories\CurrencyRateFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'currency_id',
        'rate_to_base',
        'fetched_at',
    ];

    /**
     * Each rate snapshot belongs to one currency.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_to_base' => 'string', // Keep as string to preserve decimal precision
            'fetched_at'   => 'datetime',
        ];
    }
}
