<?php

declare(strict_types=1);

namespace App\Console\Commands;

use DateTimeInterface;
use DOMElement;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;

class DeterminePages extends SeriousRequestCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:determine-pages';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startDate = Date::parse(self::START_DATE)->toImmutable();
        $endDate = Date::parse(self::END_DATE)->toImmutable();

        $pages = [];

        $currentDate = $startDate->startOfDay();
        do {
            $this->line("Downloading info for {$currentDate->isoFormat('DD MMMM')}…");
            $minMax = $this->determineMinMax($currentDate);

            $pages[$currentDate->format('Y-m-d')] = [
                'startDate' => max($currentDate, $startDate),
                'endDate' => min($currentDate, $endDate),
                'firstPage' => $minMax[0] ?? null,
                'lastPage' => $minMax[1] ?? null,
            ];

            if ($minMax && preg_match('/page=(\d+)/', $minMax[1], $matches)) {
                $this->line("{$currentDate->isoFormat('DD MMMM')} has <info>{$matches[1]} page(s)</>");
            } else {
                $this->line("{$currentDate->isoFormat('DD MMMM')} has <comment>no pages</comment>");
            }
        } while (($currentDate = $currentDate->addDay())->lte($endDate));

        Cache::forever(self::CACHE_PAGES, $pages);

        return Command::SUCCESS;
    }

    public function determineMinMax(DateTimeInterface $date): ?array
    {
        $path = "/gedraaid/{$date->format('d-m-Y')}";
        $doc = $this->downloadDom($path);

        $xpath = new DOMXPath($doc);

        $anchorTags = $xpath->query("//main//a[contains(@href, \"{$path}\")]");

        $highestPage = 1;
        $highestPageLink = null;

        /** @var DOMElement $anchorTag */
        foreach ($anchorTags as $anchorTag) {
            $href = $anchorTag->getAttribute('href');
            if (! preg_match('/(?:\?|&)page=(\d+)/', $href, $matches)) {
                continue;
            }

            $pageNumber = (int) $matches[1];
            if ($pageNumber > $highestPage) {
                $highestPage = $pageNumber;
                $highestPageLink = URL::to($href);
                $highestPage = max($highestPage, (int) $matches[1]);
            }
        }

        if (! $highestPageLink) {
            return null;
        }

        $lowestPageLink = (string) Uri::withoutQueryValue(new Uri($highestPageLink), 'page');

        return [$lowestPageLink, $highestPageLink];
    }
}
