<?php

namespace Addons\SmartRiskManagement\App\Http\Controllers\User;

use Addons\SmartRiskManagement\App\Http\Controllers\Controller;
use Addons\SmartRiskManagement\App\Models\SignalProviderMetrics;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Addons\TradingExecutionEngine\App\Models\ExecutionPosition;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SrmInsightController extends Controller
{
    /**
     * Display performance insights.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'Performance Insights';

        $user = Auth::user();
        $insights = [
            'recommendations' => [],
            'trends' => [],
            'warnings' => [],
        ];

        $connectionIds = [];

        if (class_exists(ExecutionConnection::class)) {
            $connectionIds = ExecutionConnection::where('user_id', $user->id)
                ->pluck('id')
                ->all();
        }

        if (!empty($connectionIds) && class_exists(ExecutionPosition::class)) {
            $positions = ExecutionPosition::whereIn('connection_id', $connectionIds)
                ->with('connection')
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();

            $avgScore = $positions->avg('performance_score_at_entry');
            $hasSrm = $positions->contains(function ($pos) {
                return $pos->srm_adjusted_lot !== null || $pos->srm_sl_buffer !== null;
            });

            // Recommendation dasar berdasarkan skor rata-rata
            if ($hasSrm && $avgScore !== null) {
                if ($avgScore >= 70) {
                    $insights['recommendations'][] = [
                        'title' => 'Pertahankan konfigurasi SRM Anda',
                        'message' => 'Rata-rata skor kualitas sinyal yang dieksekusi di akun Anda tinggi. Anda bisa mempertahankan pengaturan SRM saat ini dan perlahan menaikkan risiko hanya pada koneksi dengan track record baik.',
                    ];
                } elseif ($avgScore >= 50) {
                    $insights['recommendations'][] = [
                        'title' => 'Perketat filter dan kurangi eksposur',
                        'message' => 'Skor kualitas sinyal Anda berada di level menengah. Pertimbangkan untuk menurunkan ukuran lot maksimum dan menggunakan Filter Strategies yang lebih ketat untuk sumber sinyal yang kurang konsisten.',
                    ];
                } else {
                    $insights['recommendations'][] = [
                        'title' => 'Fokus ke perlindungan modal dulu',
                        'message' => 'Skor kualitas sinyal yang masuk cenderung rendah. Disarankan menurunkan lot ke minimum, mengaktifkan batas drawdown yang konservatif, dan menghapus koneksi dari penyedia sinyal yang sering rugi beruntun.',
                    ];
                }
            } else {
                $insights['recommendations'][] = [
                    'title' => 'Aktifkan SRM untuk mulai mengumpulkan data',
                    'message' => 'Belum ada cukup data penyesuaian risiko otomatis di akun Anda. Hubungkan minimal satu Execution Connection dan aktifkan fitur SRM agar sistem bisa belajar dari histori trading Anda.',
                ];
            }

            // Tren sederhana: jumlah posisi dengan SL buffer / lot adjustment dalam 30 hari terakhir
            $last30 = $positions->where('created_at', '>=', now()->subDays(30));
            $bufferCount = $last30->whereNotNull('srm_sl_buffer')->count();
            $lotAdjCount = $last30->whereNotNull('srm_adjusted_lot')->count();

            if ($bufferCount > 0 || $lotAdjCount > 0) {
                $insights['trends'][] = [
                    'type' => 'info',
                    'title' => 'SRM aktif bekerja di akun Anda',
                    'description' => "Dalam 30 hari terakhir, SRM menambahkan buffer SL pada {$bufferCount} posisi dan melakukan penyesuaian ukuran lot pada {$lotAdjCount} posisi.",
                ];
            }

            // Peringatan risiko berdasarkan drawdown per koneksi (menggunakan field pnl & status kalau tersedia)
            $highRiskConnections = $positions
                ->groupBy('connection_id')
                ->filter(function ($group) {
                    // Heuristik: lebih dari 5 posisi rugi beruntun
                    $lossStreak = 0;
                    $maxLossStreak = 0;
                    foreach ($group->sortBy('created_at') as $pos) {
                        if (($pos->pnl ?? 0) < 0) {
                            $lossStreak++;
                            $maxLossStreak = max($maxLossStreak, $lossStreak);
                        } else {
                            $lossStreak = 0;
                        }
                    }
                    return $maxLossStreak >= 5;
                });

            foreach ($highRiskConnections as $connectionId => $group) {
                $conn = $group->first()->connection;
                $insights['warnings'][] = [
                    'title' => "Drawdown tinggi pada koneksi {$conn->name}",
                    'message' => 'Kami mendeteksi rangkaian kerugian beruntun pada koneksi ini. Pertimbangkan menurunkan risk multiplier, menonaktifkan sementara koneksi, atau mengganti sumber sinyal yang digunakan.',
                ];
            }
        }

        // Integrasi dengan Filter Strategies & AI Model Profiles (jika addon aktif)
        // Tujuan: beri rekomendasi actionable yang menghubungkan SRM ke tools konfigurasi user.
        try {
            // Filter Strategies
            if (class_exists(\Addons\FilterStrategyAddon\App\Models\FilterStrategy::class)) {
                $userFilterCount = \Addons\FilterStrategyAddon\App\Models\FilterStrategy::where('created_by_user_id', $user->id)->count();
                $enabledFilterCount = \Addons\FilterStrategyAddon\App\Models\FilterStrategy::where('created_by_user_id', $user->id)
                    ->where('enabled', true)
                    ->count();

                if ($enabledFilterCount === 0) {
                    $insights['recommendations'][] = [
                        'title' => 'Aktifkan Filter Strategies untuk menyaring sinyal buruk',
                        'message' => 'Saat ini Anda belum mengaktifkan Filter Strategies pribadi. SRM akan lebih efektif jika sinyal yang masuk sudah lebih “bersih”. Coba buat minimal satu strategi filter dan hubungkan ke preset / koneksi utama Anda.',
                    ];
                } elseif ($enabledFilterCount > 0 && ($avgScore ?? 0) < 60) {
                    $insights['recommendations'][] = [
                        'title' => 'Optimalkan kembali Filter Strategies Anda',
                        'message' => 'Anda sudah menggunakan Filter Strategies, namun skor kualitas sinyal masih menengah ke bawah. Pertimbangkan untuk menambah aturan filter (misalnya, hindari sinyal saat volatilitas ekstrem) atau memisahkan strategi khusus untuk SP yang agresif.',
                    ];
                }
            }

            // AI Model Profiles
            if (class_exists(\Addons\AiTradingAddon\App\Models\AiModelProfile::class)) {
                $userProfileCount = \Addons\AiTradingAddon\App\Models\AiModelProfile::where('created_by_user_id', $user->id)->count();
                $enabledProfileCount = \Addons\AiTradingAddon\App\Models\AiModelProfile::where('created_by_user_id', $user->id)
                    ->where('enabled', true)
                    ->count();

                if ($enabledProfileCount === 0) {
                    $insights['recommendations'][] = [
                        'title' => 'Gunakan AI Model Profiles untuk analisa sebelum eksekusi',
                        'message' => 'Belum ada AI Model Profiles aktif di akun Anda. Untuk sinyal yang kualitasnya fluktuatif, Anda bisa menambahkan lapisan AI decision (misalnya konfirmasi tren / news) sebelum SRM memutuskan ukuran risiko.',
                    ];
                } elseif ($enabledProfileCount > 0 && ($avgScore ?? 0) < 70) {
                    $insights['trends'][] = [
                        'type' => 'info',
                        'title' => 'Pertimbangkan kalibrasi ulang prompt AI Model Profiles',
                        'description' => 'Anda sudah menggunakan AI Model Profiles, tetapi skor rata-rata sinyal belum stabil tinggi. Coba tinjau kembali prompt / mode analisa, misalnya menekankan penghindaran entry di area konsolidasi atau menjelang berita berdampak tinggi.',
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Insight integrasi bersifat best-effort; kalau gagal jangan ganggu halaman
        }

        $data['insights'] = $insights;

        return view('smart-risk-management::user.insights.index', $data);
    }
}

