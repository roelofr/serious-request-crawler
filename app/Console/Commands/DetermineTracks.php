<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Track;
use Carbon\CarbonInterface;
use DOMDocument;
use DOMElement;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

class DetermineTracks extends SeriousRequestCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '3fm:determine-tracks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startDate = Date::parse(self::START_DATE)->toImmutable();
        $endDate = Date::parse(self::END_DATE)->toImmutable();

        $pages = Cache::get(self::CACHE_PAGES, []);
        $tracks = [];

        $currentDate = $startDate->startOfDay();
        foreach ($pages as $date => $page) {
            $pageDate = Date::parse($date);
            $firstPage = $page['firstPage'];
            $lastPage = $page['lastPage'];

            if ($firstPage === null) {
                $this->line("Skipping download for <info>{$pageDate->isoFormat('DD MMMM')}</>");

                continue;
            }

            $this->line("Starting download for <info>{$pageDate->isoFormat('DD MMMM')}</>…");

            $pageDocuments = $this->downloadPages($pageDate, $firstPage, $lastPage);

            $tracks[] = $this->getTracksFromPages($pageDocuments, $pageDate, $startDate, $endDate);

            Cache::forever(self::CACHE_TRACKS, Arr::collapse($tracks));
        }

        return Command::SUCCESS;
    }

    private function downloadPages(CarbonInterface $pageDate, string $startPage, string $endPage): array
    {
        if (! preg_match('/(?:[?&])page=(\d+)$/', $endPage, $matches)) {
            throw new InvalidArgumentException("Invalid start page: {$startPage}");
        }

        $maxPage = (int) $matches[1];

        $trackPages = [];
        $baseUrl = new Uri($startPage);

        $this->line("Downloading <comment>{$maxPage}</> pages…");

        $pages = range(1, $maxPage);
        $this->withProgressBar($pages, function ($page) use ($baseUrl, &$trackPages, $pageDate) {
            $pageUrl = (string) Uri::withQueryValue($baseUrl, 'page', "{$page}");

            $shouldNotCache = Date::now()->subHour()->startOfDay()->lte($pageDate) && $page <= 1;
            $trackPages[] = $this->downloadDom($pageUrl, $shouldNotCache);
        });

        $this->line('');

        return $trackPages;
    }

    private function getTracksFromPages(array $pages, CarbonInterface $pageDate, CarbonInterface $startDate, CarbonInterface $endDate): ?iterable
    {
        $tracks = Collection::make();
        $trackXpath = "//main//a[contains(@href, '/muziek/tracks/')]";

        /** @var DOMDocument $page */
        foreach ($pages as $page) {
            $xpath = new DOMXPath($page);
            $trackNodes = $xpath->query($trackXpath);

            /** @var DOMElement $trackNode */
            foreach ($trackNodes as $trackNode) {
                $trackTime = $trackNode->getElementsByTagName('span')->item(0)->textContent;
                $trackTitle = $trackNode->getElementsByTagName('p')->item(0)->textContent;
                $trackArist = $trackNode->getElementsByTagName('p')->item(1)->textContent;

                if (! preg_match('/^(\d+):(\d+)$/', $trackTime, $matches)) {
                    continue;
                }

                $trackDate = Date::instance($pageDate)->setTime((int) $matches[1], (int) $matches[2]);

                if ($trackDate->lt($startDate) || $trackDate->gt($endDate)) {
                    continue;
                }

                $tracks[] = Track::findByPlayedAt(
                    $trackDate,
                    $trackArist,
                    $trackTitle,
                );
            }
        }

        $this->line("Found <info>{$tracks->count()}</> tracks");

        return $tracks;
    }
}
