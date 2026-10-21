<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Layanan extends Model
{
    protected $table = 'layanan';

    protected $fillable = ['role_id', 'kode', 'nama', 'is_pengaduan', 'butuh_tindak_lanjut'];

    protected function casts(): array
    {
        return [
            'is_pengaduan'        => 'boolean',
            'butuh_tindak_lanjut' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(Dokumen::class);
    }

    /** Pimpinan melihat semua layanan; admin hanya layanan sesuai perannya. */
    public function scopeUntukUser(Builder $query, User $user): Builder
    {
        return $user->isPimpinan() ? $query : $query->where('role_id', $user->role_id);
    }
}
