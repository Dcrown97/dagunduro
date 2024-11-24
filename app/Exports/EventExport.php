<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class EventExport implements FromCollection, WithHeadings, WithMapping
{
    protected $record;

    public function __construct($record)
    {
        $this->record = $record;
    }


    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $record = $this->record;
    }

    public function map($record): array
    {
        $title = $record->title;
        $startDate = $record->start_date . ' ' . $record->start_date_time;
        $endDate = $record->end_date . ' ' . $record->end_date_time;
        $registrants = $record->registrants_count ? $record->registrants_count : 0;
        $status = $record->status;
        return [
            $title,
            $startDate,
            $endDate,
            $registrants,
            $status
        ];
    }


    public function headings(): array
    {
        return array(
            'Event Title',
            'Start Date',
            'End Date',
            'Registrants',
            'Status'
        );
    }
}
