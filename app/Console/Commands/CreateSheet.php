<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exports\TrackExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CreateSheet extends SeriousRequestCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '3fm:report';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startDate = Date::parse(self::START_DATE)->toImmutable();
        $endDate = Date::parse(self::END_DATE)->toImmutable();

        $tracks = Cache::get(self::CACHE_TRACKS, []);

        if (empty($tracks)) {
            $this->error('No tracks found.');

            return Command::FAILURE;
        }

        $fileLocation = 'serious-request/tracks.ods';

        $this->line('Writing Excel sheet…');
        Excel::store(new TrackExport($tracks), $fileLocation);

        $this->info('File written');
        $this->line(Storage::path($fileLocation));

        return Command::SUCCESS;
    }
}
