<?php

declare(strict_types=1);

namespace App\Services;

use App\Tools\Restaurant\BasicImporter;
use App\Tools\Restaurant\FullImporter;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Tools\Restaurant\ImporterFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use LimitIterator;
use SplFileObject;

class RestaurantImportHandler
{
    /**
     * Import new restaurant(s) from a .csv file.
     * 
     * @return int The number of restaurants that were imported.
     * @throws ValidationException
     */
    public function import(UploadedFile $file): int
    {
        $importer = ImporterFactory::fromFile($file);

        if($importer === null) {
            throw ValidationException::withMessages(['file' => 'The CSV file is not in the correct format']);
        }

        $fileObject = $file->openFile(mode: "r");
        $fileObject->setFlags(flags: SplFileObject::READ_CSV);
        $fileObject->setCsvControl(separator: ',', enclosure: '"', escape: '\\');

        $counter = 0;

        DB::beginTransaction();
        
        try {
            // Skip the header (first line) for full import
            $offset = $importer instanceof FullImporter ? 1 : 0;

            foreach(new LimitIterator($fileObject, $offset) as $columns) {
                /** @var array<string|null> $columns */

                // We caught an empty line. Either we are at the end of the file,
                // or the file is not in the correct format and contains empty lines.
                if($columns[0] === null) {
                    break;
                }

                $importer->importRow($columns);
                $counter++;
            }

            DB::commit();
        } catch(Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $counter;
    }
}
