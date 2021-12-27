<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Track;
use Illuminate\Console\Command;

class WriteTracksToSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:write-seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Write the seed file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tracks = Track::query()
            ->orderBy('played_at')
            ->get();

        if (empty($tracks)) {
            $this->error('No tracks found.');

            return Command::FAILURE;
        }

        file_put_contents(
            resource_path('json/tracks.json'),
            json_encode($tracks->toArray(), JSON_PRETTY_PRINT)
        );

        $this->info('Track seed file updated.');

        return Command::SUCCESS;
    }
}
