<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:database 
                            {--sql-file= : Path to SQL file (default: ../../install/lib/database.sql)}
                            {--skip-errors : Skip SQL errors and continue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import database from installer SQL file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Try multiple possible paths for the SQL file
        $possiblePaths = [
            $this->option('sql-file'),
            base_path('../install/lib/database.sql'),
            __DIR__ . '/../../../install/lib/database.sql',
            storage_path('../install/lib/database.sql'),
        ];
        
        $sqlFile = null;
        foreach ($possiblePaths as $path) {
            if ($path && File::exists($path)) {
                $sqlFile = $path;
                break;
            }
        }
        
        if (!$sqlFile || !File::exists($sqlFile)) {
            $this->error("SQL file not found. Tried:");
            foreach ($possiblePaths as $path) {
                if ($path) {
                    $this->line("  - {$path}");
                }
            }
            return 1;
        }

        $this->info("Reading SQL file: {$sqlFile}");
        $sql = File::get($sqlFile);

        if (empty($sql)) {
            $this->error("SQL file is empty");
            return 1;
        }

        $this->info("Importing database...");
        
        try {
            // Split SQL into individual statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && 
                           !preg_match('/^\s*--/', $stmt) && 
                           !preg_match('/^\s*\/\*/', $stmt);
                }
            );

            $imported = 0;
            $errors = 0;

            DB::beginTransaction();

            foreach ($statements as $statement) {
                if (empty(trim($statement))) {
                    continue;
                }

                try {
                    DB::statement($statement);
                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                    if (!$this->option('skip-errors')) {
                        $this->warn("Error executing statement: " . substr($statement, 0, 100) . "...");
                        $this->warn("Error: " . $e->getMessage());
                        
                        if (!$this->confirm('Continue with remaining statements?', true)) {
                            DB::rollBack();
                            return 1;
                        }
                    }
                }
            }

            DB::commit();

            $this->info("✅ Database imported successfully!");
            $this->info("   - Statements executed: {$imported}");
            if ($errors > 0) {
                $this->warn("   - Errors encountered: {$errors}");
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to import database: " . $e->getMessage());
            return 1;
        }
    }
}

