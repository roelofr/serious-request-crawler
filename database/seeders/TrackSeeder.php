<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Track;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

class TrackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tracks = json_decode(file_get_contents(resource_path('json/tracks.json')), true, 16, JSON_THROW_ON_ERROR);

        foreach ($tracks as $track) {
            $trackModel = Track::findByPlayedAt(
                Date::parse($track['played_at']),
                $track['artist'],
                $track['title'],
            );

            $trackModel->cover ??= $track['cover'];

            $trackModel->save();
        }
    }
}
