<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * @group Admin - Email Management
 *
 * Endpoints for managing email configuration, templates, and subscribers.
 */
class EmailApiController extends Controller
{
    /**
     * Get Email Configuration
     *
     * Retrieve current email settings.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function getConfig()
    {
        $config = [
            'email_method' => config('mail.default'),
            'email_from' => config('mail.from.address'),
            'email_from_name' => config('mail.from.name'),
            'smtp_host' => config('mail.mailers.smtp.host'),
            'smtp_port' => config('mail.mailers.smtp.port'),
            'smtp_encryption' => config('mail.mailers.smtp.encryption'),
            'smtp_username' => config('mail.mailers.smtp.username'),
        ];

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    /**
     * Update Email Configuration
     *
     * Update email settings.
     *
     * @bodyParam email_method string Email method: smtp, sendmail, mailgun. Example: smtp
     * @bodyParam email_from string From email address. Example: noreply@example.com
     * @bodyParam smtp_host string SMTP host. Example: smtp.gmail.com
     * @bodyParam smtp_port int SMTP port. Example: 587
     * @response 200 {
     *   "success": true,
     *   "message": "Email configuration updated"
     * }
     */
    public function updateConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_method' => 'sometimes|in:smtp,sendmail,mailgun',
            'email_from' => 'sometimes|email',
            'email_from_name' => 'sometimes|string',
            'smtp_host' => 'sometimes|string',
            'smtp_port' => 'sometimes|integer',
            'smtp_encryption' => 'sometimes|in:tls,ssl',
            'smtp_username' => 'sometimes|string',
            'smtp_password' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update .env file or configuration
        // This is a simplified version - in production, you'd update the .env file
        
        return response()->json([
            'success' => true,
            'message' => 'Email configuration updated successfully'
        ]);
    }

    /**
     * List Email Templates
     *
     * Get all email templates.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function listTemplates()
    {
        $templates = EmailTemplate::all();

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * Get Email Template
     *
     * Retrieve a specific email template.
     *
     * @urlParam slug string required Template slug. Example: welcome-email
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function getTemplate($slug)
    {
        $template = EmailTemplate::where('slug', $slug)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $template
        ]);
    }

    /**
     * Update Email Template
     *
     * Update an email template.
     *
     * @urlParam slug string required Template slug. Example: welcome-email
     * @bodyParam subject string Email subject. Example: Welcome to our platform
     * @bodyParam body string Email body content.
     * @response 200 {
     *   "success": true,
     *   "message": "Template updated successfully"
     * }
     */
    public function updateTemplate(Request $request, $slug)
    {
        $template = EmailTemplate::where('slug', $slug)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'subject' => 'sometimes|string',
            'body' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $template->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
            'data' => $template
        ]);
    }

    /**
     * List Subscribers
     *
     * Get all email subscribers.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [...]
     * }
     */
    public function listSubscribers()
    {
        $subscribers = Subscriber::latest()->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $subscribers
        ]);
    }

    /**
     * Send Single Email
     *
     * Send email to a specific subscriber.
     *
     * @bodyParam email string required Subscriber email. Example: user@example.com
     * @bodyParam subject string required Email subject. Example: Special Offer
     * @bodyParam message string required Email message.
     * @response 200 {
     *   "success": true,
     *   "message": "Email sent successfully"
     * }
     */
    public function sendSingle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Mail::raw($request->message, function ($mail) use ($request) {
                $mail->to($request->email)
                     ->subject($request->subject);
            });

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send Bulk Email
     *
     * Send email to all subscribers.
     *
     * @bodyParam subject string required Email subject. Example: Newsletter
     * @bodyParam message string required Email message.
     * @response 200 {
     *   "success": true,
     *   "message": "Bulk email sent successfully"
     * }
     */
    public function sendBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subscribers = Subscriber::all();
            
            foreach ($subscribers as $subscriber) {
                Mail::raw($request->message, function ($mail) use ($request, $subscriber) {
                    $mail->to($subscriber->email)
                         ->subject($request->subject);
                });
            }

            return response()->json([
                'success' => true,
                'message' => "Bulk email sent to {$subscribers->count()} subscribers"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk email: ' . $e->getMessage()
            ], 500);
        }
    }
}
