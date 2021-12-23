<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrackExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    protected Collection $tracks;

    public function __construct(iterable $tracks)
    {
        $this->tracks = Collection::make($tracks);
    }

    public function collection()
    {
        return $this->tracks
            ->sortBy(fn ($track) => "{$track['date']} {$track['time']}");
    }

    /**
     * @var array
     */
    public function map($track): array
    {
        $date = Date::parse("{$track['date']}T{$track['time']}");

        return [
            $date->format('d-m'),
            $date->format('H:i'),
            $track['artist'],
            $track['title'],
        ];
    }

    public function headings(): array
    {
        return [
            'Date',
            'Time',
            'Artist',
            'Title',
        ];
    }
}
