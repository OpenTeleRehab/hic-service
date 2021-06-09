<?php

namespace App\Imports;

use App\Models\InternationalClassificationDisease;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Row;

class ImportDisease implements OnEachRow, WithHeadingRow, WithEvents
{
    /**
     * @param \Maatwebsite\Excel\Row $row
     *
     * @return void
     */
    public function onRow(Row $row)
    {
        if (isset($row['name'])) {
            $row = $row->toArray();
            $data = [
                'name' => $row['name'],
            ];

            $disease = InternationalClassificationDisease::where('name', $row['name'])->updateOrCreate([], $data);
            $disease->save();
        }
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->sheetName = $event->getSheet()->getTitle();
            }
        ];
    }
}
