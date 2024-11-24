<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class BlogExport implements FromCollection, WithHeadings, WithMapping
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
        $date = Carbon::parse($record->created_at)->format('jS M');
        $category = $record->category ? $record->category->name : "--";
        $views = $record->view_count;
        return [
            $title,
            $date,
            $category,
            $views
        ];
    }


    public function headings(): array
    {
        return array(
            'Post Title',
            'Date',
            'Category',
            'Views'
        );
    }
}
