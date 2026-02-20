<?php namespace Pensoft\EndangeredMap\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * ImportSpeciesData Command
 */
class ImportSpeciesData extends Command
{
    /**
     * @var string name is the console command name
     */
    protected $name = 'endangeredmap:import';

    /**
     * @var string description is the console command description
     */
    protected $description = 'Import endangered species data from Excel file';

    /**
     * Column mapping: Excel columns A-H to species table fields
     */
    protected $speciesColumns = [
        1 => 'internal_name',       // A
        2 => 'family',              // B
        3 => 'subfamily',           // C
        4 => 'tribe',               // D
        5 => 'genus',               // E
        6 => 'subgenus',            // F
        7 => 'species',             // G
        8 => 'taxonomic_authority', // H
    ];

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info('Loading Excel file...');
        $spreadsheet = IOFactory::load($filePath);

        DB::beginTransaction();

        try {
            // Phase 1: Truncate tables and import acronyms
            $this->importAcronyms($spreadsheet);

            // Phase 2: Import species
            $this->importSpecies($spreadsheet);

            // Phase 3: Import statuses
            $this->importStatuses($spreadsheet);

            DB::commit();

            $this->displaySummary();

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Import acronyms from Sheet 2
     */
    protected function importAcronyms($spreadsheet)
    {
        $this->info('Phase 1: Importing acronyms...');

        // Truncate in correct order for FK constraints
        DB::table('pensoft_endangeredmap_statuses')->truncate();
        DB::table('pensoft_endangeredmap_species')->truncate();
        DB::table('pensoft_endangeredmap_acronyms')->truncate();

        $sheet = $spreadsheet->getSheet(1); // Second sheet (0-indexed)
        $highestRow = $sheet->getHighestRow();
        $count = 0;
        $now = now();

        for ($row = 2; $row <= $highestRow; $row++) {
            $acronym = trim((string) $sheet->getCell([1, $row])->getValue());
            $meaning = trim((string) $sheet->getCell([2, $row])->getValue());

            if (empty($acronym)) {
                continue;
            }

            DB::table('pensoft_endangeredmap_acronyms')->insert([
                'acronym' => $acronym,
                'meaning' => $meaning,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $count++;
        }

        $this->info("  Imported {$count} acronyms.");
    }

    /**
     * Import species from Sheet 1 (columns A-H)
     */
    protected function importSpecies($spreadsheet)
    {
        $this->info('Phase 2: Importing species...');

        $sheet = $spreadsheet->getSheet(0); // First sheet
        $highestRow = $sheet->getHighestRow();
        $count = 0;
        $now = now();

        $bar = $this->output->createProgressBar($highestRow - 1);
        $bar->start();

        for ($row = 2; $row <= $highestRow; $row++) {
            $data = [
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $hasData = false;
            foreach ($this->speciesColumns as $col => $field) {
                $value = trim((string) $sheet->getCell([$col, $row])->getValue());
                $data[$field] = $value !== '' ? $value : null;
                if ($value !== '') {
                    $hasData = true;
                }
            }

            if (!$hasData) {
                $bar->advance();
                continue;
            }

            DB::table('pensoft_endangeredmap_species')->insert($data);
            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->info("  Imported {$count} species.");
    }

    /**
     * Import statuses from Sheet 1 (columns I-BV)
     */
    protected function importStatuses($spreadsheet)
    {
        $this->info('Phase 3: Importing statuses...');

        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Read country headers from row 1, starting at column I (index 9)
        $countries = [];
        for ($col = 9; $col <= $highestColumnIndex; $col++) {
            $header = trim((string) $sheet->getCell([$col, 1])->getValue());
            if (!empty($header)) {
                $countries[$col] = $header;
            }
        }

        $this->info("  Found " . count($countries) . " country columns.");

        $statusBatch = [];
        $batchSize = 1000;
        $totalStatuses = 0;
        $now = now();

        $bar = $this->output->createProgressBar($highestRow - 1);
        $bar->start();

        for ($row = 2; $row <= $highestRow; $row++) {
            // Get the species ID (rows are sequential, species_id = row - 1)
            // But we need to account for potentially skipped empty rows
            $speciesName = trim((string) $sheet->getCell([1, $row])->getValue());
            if (empty($speciesName)) {
                $bar->advance();
                continue;
            }

            // Look up the species ID by internal_name
            $speciesId = DB::table('pensoft_endangeredmap_species')
                ->where('internal_name', $speciesName)
                ->value('id');

            if (!$speciesId) {
                $bar->advance();
                continue;
            }

            foreach ($countries as $col => $country) {
                $status = trim((string) $sheet->getCell([$col, $row])->getValue());

                if ($status === '') {
                    continue;
                }

                $statusBatch[] = [
                    'species_id' => $speciesId,
                    'country' => $country,
                    'status' => $status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($statusBatch) >= $batchSize) {
                    DB::table('pensoft_endangeredmap_statuses')->insert($statusBatch);
                    $totalStatuses += count($statusBatch);
                    $statusBatch = [];
                }
            }

            $bar->advance();
        }

        // Insert remaining batch
        if (!empty($statusBatch)) {
            DB::table('pensoft_endangeredmap_statuses')->insert($statusBatch);
            $totalStatuses += count($statusBatch);
        }

        $bar->finish();
        $this->line('');
        $this->info("  Imported {$totalStatuses} status records.");
    }

    /**
     * Display import summary
     */
    protected function displaySummary()
    {
        $this->line('');
        $this->info('=== Import Summary ===');
        $this->info('Species:  ' . DB::table('pensoft_endangeredmap_species')->count());
        $this->info('Statuses: ' . DB::table('pensoft_endangeredmap_statuses')->count());
        $this->info('Acronyms: ' . DB::table('pensoft_endangeredmap_acronyms')->count());
        $this->info('Import completed successfully!');
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['file', InputArgument::REQUIRED, 'Path to the Excel file'],
        ];
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [];
    }
}
