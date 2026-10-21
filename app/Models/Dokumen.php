<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dokumen extends Model
{
    use SoftDeletes;

    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_SELESAI  = 'selesai';

    protected $table = 'dokumen';

    protected $fillable = ['user_id', 'layanan_id', 'nomor_surat', 'judul', 'tanggal', 'file_path', 'status'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class);
    }

    public function tindakLanjut(): HasMany
    {
        return $this->hasMany(TindakLanjut::class);
    }

    /** Membatasi dokumen sesuai hak akses pengguna. */
    public function scopeDapatDilihat(Builder $query, User $user): Builder
    {
        if ($user->isPimpinan()) {
            return $query;
        }

        return $query->whereIn('layanan_id', Layanan::where('role_id', $user->role_id)->select('id'));
    }

    public function labelStatus(): string
    {
        return match ($this->status) {
            self::STATUS_MENUNGGU => 'Menunggu',
            self::STATUS_SELESAI  => 'Selesai',
            default               => '-',
        };
    }
}
