<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'played_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'played_at',
        'artist',
        'title',
        'cover',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    public static function booted(): void
    {
        self::saving(function (self $track) {
            $originalPlayedAt = $track->played_at;
            $playedAt = CarbonImmutable::instance($originalPlayedAt)->setTime($originalPlayedAt->hour, $originalPlayedAt->minute, 0);

            if ($playedAt->equalTo($originalPlayedAt)) {
                return;
            }

            $track->played_at = $playedAt;
        });
    }

    public static function findByPlayedAt(CarbonInterface $playedAt, string $artist, string $title): ?self
    {
        $playedAt = CarbonImmutable::instance($playedAt)->setTime($playedAt->hour, $playedAt->minute, 0);

        return static::firstOrCreate([
            'played_at' => $playedAt,
            'artist' => $artist,
            'title' => $title,
        ]);
    }
}
