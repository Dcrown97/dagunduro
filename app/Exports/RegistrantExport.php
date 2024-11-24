<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class RegistrantExport implements FromCollection, WithHeadings, WithMapping
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
        $name = $record->name;
        $regDate = Carbon::parse($record->created_at)->format('jS M');
        $phoneno = $record->phoneno;
        $email = $record->email;
        return [
            $name,
            $regDate,
            $phoneno,
            $email
        ];
    }


    public function headings(): array
    {
        return array(
            'Name',
            'Reg Date',
            'Phone No',
            'Email Address'
        );
    }
}
