<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Track;
use Illuminate\Support\Collection;
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
        return $this->tracks->sortBy('played_at');
    }

    /**
     * @param Track $track
     * @var array
     */
    public function map($track): array
    {
        return [
            $track->played_at->format('d-m'),
            $track->played_at->format('H:i'),
            $track->artist,
            $track->title,
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
