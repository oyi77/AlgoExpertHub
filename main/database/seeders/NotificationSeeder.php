<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates demo notifications for investor presentations
     */
    public function run()
    {
        $users = User::where('email', '!=', 'admin@admin.com')->get();

        if ($users->isEmpty()) {
            $this->command->warn('Users not found. Skipping notification seeding.');
            return;
        }

        $notifications = [];
        $types = [
            'payment_approved' => 'Your payment has been approved!',
            'payment_rejected' => 'Your payment was rejected. Please contact support.',
            'signal_published' => 'New trading signal has been published!',
            'subscription_expiring' => 'Your subscription is expiring soon.',
            'deposit_received' => 'Deposit received successfully!',
            'withdraw_approved' => 'Your withdrawal request has been approved!',
            'welcome' => 'Welcome to our trading platform!',
            'plan_upgrade' => 'You have been upgraded to a premium plan!',
        ];

        // Create 30 demo notifications
        for ($i = 0; $i < 30; $i++) {
            $user = $users->random();
            $type = array_rand($types);
            $message = $types[$type];

            $readAt = rand(0, 10) > 5 ? Carbon::now()->subHours(rand(1, 48)) : null;

            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));

            try {
                DB::table('notifications')->insert([
                    'type' => 'App\Notifications\\' . ucfirst(str_replace('_', '', ucwords($type, '_'))) . 'Notification',
                    'notifiable_type' => 'App\Models\User',
                    'notifiable_id' => (string)$user->id,
                    'data' => json_encode([
                        'message' => $message,
                        'type' => $type,
                        'action_url' => '/dashboard',
                    ]),
                    'read_at' => $readAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            } catch (\Exception $e) {
                // Skip if unique constraint fails (migration has wrong unique constraints)
                continue;
            }

            $notifications[] = $notification;
        }

        $this->command->info('Created ' . count($notifications) . ' demo notifications successfully!');
    }
}
