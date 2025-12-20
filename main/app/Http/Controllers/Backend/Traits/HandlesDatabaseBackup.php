<?php

namespace App\Http\Controllers\Backend\Traits;

use Illuminate\Http\Request;

trait HandlesDatabaseBackup
{
    /**
     * Create database backup
     */
    public function createBackup(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            set_time_limit(300);
            
            $name = $request->backup_name ?? 'backup_' . date('Y-m-d_H-i-s');
            $result = $this->backupService->createBackup($name);
            
            if ($isAjax) {
                return response()->json([
                    'success' => $result['type'] === 'success',
                    'message' => $result['message'],
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            $errorMessage = 'Backup failed: ' . $e->getMessage();
            
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
     * Load backup state
     */
    public function loadBackup(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            if (empty($request->backup_file)) {
                $errorMessage = 'Please select a backup file';
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => $errorMessage], 400);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            set_time_limit(600);
            
            $result = $this->backupService->loadBackup($request->backup_file);
            
            if ($result['type'] === 'success') {
                $message = 'Database restored successfully! Please login again.';
                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'redirect' => route('admin.login')
                    ]);
                }
                return redirect()->route('admin.login')->with('success', $message);
            }
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            $errorMessage = 'Restore failed: ' . $e->getMessage();
            
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
     * Delete backup
     */
    public function deleteBackup(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            if (empty($request->backup_file)) {
                $errorMessage = 'Please select a backup file';
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => $errorMessage], 400);
                }
                return redirect()->back()->with('error', $errorMessage);
            }

            $result = $this->backupService->deleteBackup($request->backup_file);
            
            if ($isAjax) {
                return response()->json([
                    'success' => $result['type'] === 'success',
                    'message' => $result['message'],
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            $errorMessage = 'Delete failed: ' . $e->getMessage();
            
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
     * Save backup as factory state
     */
    public function saveAsFactoryState(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            $result = $this->backupService->saveAsFactoryState($request->backup_file ?? null);
            
            if ($isAjax) {
                return response()->json([
                    'success' => $result['type'] === 'success',
                    'message' => $result['message'],
                    'refresh' => true
                ]);
            }
            
            return redirect()->back()->with($result['type'], $result['message']);
            
        } catch (\Exception $e) {
            $errorMessage = 'Save failed: ' . $e->getMessage();
            
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
     * Load factory state
     */
    public function loadFactoryState(Request $request)
    {
        $isAjax = $request->expectsJson() || $request->ajax();

        try {
            set_time_limit(600);

            $result = $this->backupService->loadFactoryState();

            if ($result['type'] === 'success') {
                $message = 'Restored to factory state! Please login again.';
                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'redirect' => route('admin.login')
                    ]);
                }
                return redirect()->route('admin.login')->with('success', $message);
            }

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

            return redirect()->back()->with($result['type'], $result['message']);

        } catch (\Exception $e) {
            $errorMessage = 'Factory restore failed: ' . $e->getMessage();
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return redirect()->back()->with('error', $errorMessage);
        }
    }
}

