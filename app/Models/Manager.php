<?php

namespace App\Models;

use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manager extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function scopeWithOpenLeadsCount(Builder $query): Builder
    {
        return $query->withCount([
            'leads as open_leads_count' => function (Builder $query) {
                $query->whereIn('status', [
                    LeadStatus::NEW,
                    LeadStatus::IN_PROGRESS,
                ]);
            },
        ]);
    }
}
