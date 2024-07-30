<?php

declare(strict_types=1);

namespace App\Tools\Restaurant;

use SplFileInfo;
use SplFileObject;

class ImporterFactory
{
    /**
     * Create an importer based on the file's header (first line).
     * 
     * @param SplFileInfo $file 
     * @return null|Importer null if cannot determine the importer
     */
    public static function fromFile(SplFileInfo $file): ?Importer
    {
        $fileObject = $file->openFile(mode: "r");
        $fileObject->setFlags(flags: SplFileObject::READ_CSV);
        $fileObject->setCsvControl(separator: ',', enclosure: '"', escape: '\\');
        
        $fileObject->seek(line: 0);
        $header = $fileObject->current();

        // Close the file
        $fileObject = null;

        // Empty line
        if($header[0] === null) {
            return null;
        }

        switch(count($header)) {
            case 10:
                return new FullImporter();
            case 2:
                return new BasicImporter();
            default:
                return null;
        }
    }
}
