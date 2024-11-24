<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class ResourcesExport implements FromCollection, WithHeadings, WithMapping
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
        $date = Carbon::parse($record->created_at)->format('jS M, Y');
        $fileType = $record->file_type;
        $downloads = 0;
        return [
            $title,
            $date,
            $fileType,
            $downloads
        ];
    }


    public function headings(): array
    {
        return array(
            'File Name',
            'Uploaded',
            'File Type',
            'Downloads'
        );
    }
}
