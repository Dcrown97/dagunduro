<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class TransactionExport implements FromCollection, WithHeadings, WithMapping
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
        $date = Carbon::parse($record->paid_at)->format('jS M');
        $amount = number_format($record->amount);
        $status = $record->payment_status;
        return [
            $name,
            $date,
            $amount,
            $status
        ];
    }


    public function headings(): array
    {
        return array(
            'Name',
            'Date',
            'Amount',
            'Status'
        );
    }
}
