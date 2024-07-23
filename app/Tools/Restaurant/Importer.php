<?php

declare(strict_types=1);

namespace App\Tools\Restaurant;

interface Importer
{
    /**
     * Import a row from a CSV file into the database.
     * 
     * @param array $columns The columns of the row to import.
     */
    public function importRow(array $columns): void;
}
