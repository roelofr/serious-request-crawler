<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exports\TrackExport;
use App\Models\Track;
use Illuminate\Console\Command;
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
    protected $signature = 'app:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a report of all tracks in the database, as .ods file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startDate = Date::parse(self::START_DATE)->toImmutable();
        $endDate = Date::parse(self::END_DATE)->toImmutable();

        $tracks = Track::all();

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
