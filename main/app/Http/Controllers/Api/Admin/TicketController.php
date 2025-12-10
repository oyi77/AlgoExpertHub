<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\Helper\Helper;

/**
 * @group Admin APIs
 * Ticket management endpoints
 */
class TicketController extends Controller
{
    /**
     * List Tickets
     * 
     * Get all support tickets with filters
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam user integer Filter by user ID. Example: 1
     * @queryParam search string Search by support ID. Example: TKT123
     * @queryParam status string Filter by status (pending, answered, closed). Example: pending
     * @queryParam page integer Page number. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {...}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::query();

        if ($request->user) {
            $tickets->where('user_id', $request->user);
        }

        if ($request->search) {
            $tickets->where('support_id', 'LIKE', '%' . $request->search . '%');
        } elseif ($request->status) {
            $status = $request->status === 'closed' ? 1 : ($request->status === 'pending' ? 2 : 3);
            $tickets->where('status', $status);
        }

        $tickets = $tickets->with('ticketReplies', 'user')->latest()->paginate(Helper::pagination());

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    /**
     * Get Ticket Details
     * 
     * Get ticket with replies
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Ticket ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "ticket": {...},
     *     "replies": [...]
     *   }
     * }
     */
    public function show($id): JsonResponse
    {
        $ticket = Ticket::with('user')->findOrFail($id);
        $replies = TicketReply::where('ticket_id', $id)->latest()->with('admin', 'user')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'ticket' => $ticket,
                'replies' => $replies
            ]
        ]);
    }

    /**
     * Reply to Ticket
     * 
     * Add admin reply to a ticket
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Ticket ID. Example: 1
     * @bodyParam message string required Reply message. Example: Thank you for contacting us
     * @bodyParam file file optional Attachment file
     * @response 200 {
     *   "success": true,
     *   "message": "Reply sent successfully"
     * }
     */
    public function reply(Request $request, $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'file' => 'nullable|file|max:2048'
        ]);

        $ticket = Ticket::findOrFail($id);

        $file = null;
        if ($request->hasFile('file')) {
            $file = Helper::saveImage($request->file, Helper::filePath('Ticket', true));
        }

        TicketReply::create([
            'ticket_id' => $id,
            'admin_id' => auth()->guard('admin')->id(),
            'message' => $request->message,
            'file' => $file,
        ]);

        $ticket->status = 3; // answered
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully'
        ]);
    }

    /**
     * Close Ticket
     * 
     * Close a ticket
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Ticket ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Ticket closed successfully"
     * }
     */
    public function close($id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->status = 1; // closed
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed successfully'
        ]);
    }

    /**
     * Delete Ticket
     * 
     * Delete a ticket and its replies
     * 
     * @param int $id
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Ticket ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Ticket deleted successfully"
     * }
     */
    public function destroy($id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $replies = TicketReply::where('ticket_id', $id)->get();

        foreach ($replies as $reply) {
            if ($reply->file) {
                Helper::removeFile(Helper::filePath('Ticket', true) . $reply->file);
            }
            $reply->delete();
        }

        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ticket deleted successfully'
        ]);
    }
}
