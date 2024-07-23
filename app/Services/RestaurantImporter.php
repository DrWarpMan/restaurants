<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Tools\Restaurant\FullImporter;
use App\Tools\Restaurant\BasicImporter;

class RestaurantImporter
{
    /**
     * Import new restaurant(s) via CSV string.
     * 
     * @return int The number of rows imported
     */
    public function import(string $csv): int
    {
        $lines = explode("\n", $csv);
        $rowCount = count($lines);

        if($rowCount === 0) {
            throw new Exception('Invalid/empty CSV file');
        }

        $columnCount = count($this->lineToArray($lines[0]));

        $importer = match($columnCount) {
            10 => new FullImporter(),
            2 => new BasicImporter(),
            default => throw new Exception('Unsupported import content format'),
        };

        DB::beginTransaction();
        
        try {
            for($i = $columnCount === 10 ? 1 : 0; $i < $rowCount; $i++) {
                $columns = $this->lineToArray($lines[$i]);

                // Either we reached the end of the file or the line is empty
                // Empty lines should not be present in the middle of the file
                if($columns[0] === null) {
                    break;
                }

                $importer->importRow($columns);
            }

            DB::commit();
        } catch(Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $rowCount;
    }

    private function lineToArray(string $line): array
    {
        return str_getcsv($line, separator: ',', enclosure: '"');
    }
}
