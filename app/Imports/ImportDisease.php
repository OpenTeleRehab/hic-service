<?php

namespace App\Imports;

use App\Models\InternationalClassificationDisease;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Row;

class ImportDisease implements OnEachRow, WithHeadingRow, WithEvents, WithValidation
{
    /**
     * @var string
     */
    private $sheetName = '';

    /**
     * @var integer
     */
    private $newRecords = 0;

    /**
     * @var integer
     */
    private $updatedRecords = 0;

    /**
     * @param \Maatwebsite\Excel\Row $row
     *
     * @return void
     */
    public function onRow(Row $row)
    {
        $row = $row->toArray();
        $data = [
            'name' => isset($row['name']) ? $row['name'] : ''
        ];
        $existedDisease = InternationalClassificationDisease::where('name', $row['name'])
            ->count();
        if (!$existedDisease) {
            $disease = InternationalClassificationDisease::where('id', $row['id'])->updateOrCreate([], $data);
            if ($disease->wasRecentlyCreated) {
                $this->newRecords++;
                $disease->save();
            } else {
                $this->updatedRecords++;
            }
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

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'name.required' => 'error_message.disease_upload_name_required',
        ];
    }

    /**
     * @return array
     */
    public function getImportInfo()
    {
        return [
            'new_records' => $this->newRecords,
            'updated_records' => $this->updatedRecords,
        ];
    }

    /**
     * @return string
     */
    public function getCurrentSheetName()
    {
        return $this->sheetName;
    }
}
