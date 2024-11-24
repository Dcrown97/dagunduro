<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class MemberExport implements FromCollection, WithHeadings, WithMapping
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
        $name = $record->full_name;
        $regDate = Carbon::parse($record->created_at)->format('jS M');
        $homeAddress = $record->home_address ? $record->home_address : "--";
        $phoneno = $record->phone_number;
        $email = $record->email;
        $occupation = $record->occupation ? $record->occupation : "--";
        return [
            $name,
            $regDate,
            $homeAddress,
            $phoneno,
            $email,
            $occupation
        ];
    }


    public function headings(): array
    {
        return array(
            'Name',
            'Reg Date',
            'Home Address',
            'Phone No',
            'Email Address',
            'Occupation'
        );
    }
}
