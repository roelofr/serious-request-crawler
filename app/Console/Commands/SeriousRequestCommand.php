<?php

declare(strict_types=1);

namespace App\Console\Commands;

use DOMDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class SeriousRequestCommand extends Command
{
    protected const START_DATE = '2021-12-18T12:00';

    protected const END_DATE = '2021-12-24T17:00';

    protected const BASE_URL = 'https://www.npo3fm.nl/';

    protected const CACHE_PAGES = 'serious-request.pages';

    protected const CACHE_TRACKS = 'serious-request.tracks';

    protected const CACHE_HTTP = 'serious-request.http.%s';

    public function run(InputInterface $input, OutputInterface $output)
    {
        URL::forceRootUrl(self::BASE_URL);
        URL::forceScheme(parse_url('https', PHP_URL_SCHEME));

        return parent::run($input, $output);
    }

    /**
     * Downloads the specified page without stressing out the NPO servers.
     * @throws RuntimeException if download fails
     */
    protected function download(string $url, bool $noCache = false): string
    {
        $url = URL::to($url);

        $cacheKey = sprintf(self::CACHE_HTTP, md5($url));

        if (! $noCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::withUserAgent('Laravel; Roelof\'s 3FM Serious Request indexer (+github@roelof.io)')
            ->get($url);

        $responseBody = $response->successful() ? $response->body() : null;

        Cache::forever($cacheKey, $responseBody);

        if (! $responseBody) {
            $this->error(sprintf('Failed to fetch %s', $url));

            throw new RuntimeException('Failed to fetch page');
        }

        return $responseBody;
    }

    /**
     * Downloads the specified page without stressing out the NPO servers, and converts
     * it to a HTML page.
     * @return DOMDocument The HTML page
     * @throws RuntimeException if download fails
     */
    protected function downloadDom(string $url, bool $noCache = false): DOMDocument
    {
        $response = $this->download($url, $noCache);

        $doc = new DOMDocument();

        $internalErrors = libxml_use_internal_errors(true);
        $doc->loadHTML($response, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors($internalErrors);

        return $doc;
    }
}
