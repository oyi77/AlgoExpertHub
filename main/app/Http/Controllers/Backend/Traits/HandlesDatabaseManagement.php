<?php

namespace App\Http\Controllers\Backend\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

trait HandlesDatabaseManagement
{
    /**
     * Re-seed database with demo data
     */
    public function reseedDatabase(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            set_time_limit(300); // 5 minutes timeout

            $output = [];
            
            // Run database seeder
            Artisan::call('db:seed', [
                '--force' => true
            ]);
            
            $output[] = Artisan::output();
            
            // Clear all caches
            Artisan::call('optimize:clear');
            
            $message = 'Database re-seeded successfully! All demo data has been restored.';
            
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            $errorMessage = 'Failed to re-seed database: ' . $e->getMessage();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Full database reset and reseed (DANGEROUS)
     */
    public function resetDatabase(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            if ($request->confirm !== 'RESET') {
                $errorMessage = 'Please type RESET to confirm database reset.';
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => $errorMessage], 400);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            set_time_limit(600); // 10 minutes timeout

            // Wipe database
            Artisan::call('db:wipe', ['--force' => true]);
            
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            
            // Seed database
            Artisan::call('db:seed', ['--force' => true]);
            
            // Clear caches
            Artisan::call('optimize:clear');

            $message = 'Database reset complete! Please login again.';
            
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => route('admin.login')
                ]);
            }

            return redirect()->route('admin.login')->with('success', $message);
            
        } catch (\Exception $e) {
            $errorMessage = 'Failed to reset database: ' . $e->getMessage();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Get dynamic seeder count from DatabaseSeeder
     * Counts all seeders by parsing the source code
     */
    protected function getSeederCount(): int
    {
        try {
            $seederPath = database_path('seeders/DatabaseSeeder.php');
            if (!file_exists($seederPath)) {
                return 0;
            }

            $content = file_get_contents($seederPath);
            
            // Count $this->call() statements
            preg_match_all('/\$this->call\([^)]+\)/', $content, $matches);
            $count = count($matches[0]);
            
            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
