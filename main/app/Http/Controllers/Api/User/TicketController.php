<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketRequest;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\UserTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User APIs
 * Support ticket endpoints
 */
class TicketController extends Controller
{
    protected $ticket;

    public function __construct(UserTicketService $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * List Tickets
     * 
     * Get user's support tickets
     * 
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @queryParam page integer Page number. Example: 1
     * @queryParam status string Filter by status (pending, answered, closed). Example: pending
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "subject": "Support Request",
     *         "status": "pending",
     *         "priority": "medium",
     *         "created_at": "2023-01-01T00:00:00.000000Z"
     *       }
     *     ],
     *     "stats": {
     *       "pending": 2,
     *       "answered": 5,
     *       "closed": 3,
     *       "all": 10
     *     }
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::where('user_id', $request->user()->id);

        if ($request->status) {
            $statusMap = [
                'pending' => '2',
                'answered' => '3',
                'closed' => '1'
            ];
            if (isset($statusMap[$request->status])) {
                $query->where('status', $statusMap[$request->status]);
            }
        }

        $tickets = $query->with('ticketReplies')->latest()->paginate(15);

        $stats = [
            'pending' => Ticket::where('user_id', $request->user()->id)->where('status', '2')->count(),
            'answered' => Ticket::where('user_id', $request->user()->id)->where('status', '3')->count(),
            'closed' => Ticket::where('user_id', $request->user()->id)->where('status', '1')->count(),
            'all' => Ticket::where('user_id', $request->user()->id)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                ...$tickets->toArray(),
                'stats' => $stats
            ]
        ]);
    }

    /**
     * Create Ticket
     * 
     * Create a new support ticket
     * 
     * @param TicketRequest $request
     * @return JsonResponse
     * @authenticated
     * @bodyParam subject string required Ticket subject. Example: Need help with payment
     * @bodyParam message string required Ticket message. Example: I have an issue with...
     * @bodyParam priority string required Priority (low, medium, high). Example: medium
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "subject": "Need help with payment",
     *     "status": "pending",
     *     "priority": "medium"
     *   },
     *   "message": "Ticket created successfully"
     * }
     */
    public function store(TicketRequest $request): JsonResponse
    {
        $isSuccess = $this->ticket->create($request);

        if ($isSuccess['type'] === 'success') {
            $ticket = Ticket::where('user_id', $request->user()->id)->latest()->first();
            return response()->json([
                'success' => true,
                'data' => $ticket,
                'message' => $isSuccess['message']
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $isSuccess['message'] ?? 'Failed to create ticket'
        ], 400);
    }

    /**
     * Get Ticket Details
     * 
     * Get ticket with replies
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Ticket ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "subject": "Need help with payment",
     *     "status": "pending",
     *     "priority": "medium",
     *     "replies": [
     *       {
     *         "id": 1,
     *         "message": "Reply message...",
     *         "created_at": "2023-01-01T00:00:00.000000Z"
     *       }
     *     ]
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Ticket not found"
     * }
     */
    public function show($id, Request $request): JsonResponse
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('ticketReplies')
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket
        ]);
    }

    /**
     * Reply to Ticket
     * 
     * Add a reply to a ticket
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Ticket ID. Example: 1
     * @bodyParam message string required Reply message. Example: Thank you for your response
     * @response 200 {
     *   "success": true,
     *   "message": "Reply sent successfully"
     * }
     */
    public function reply($id, Request $request): JsonResponse
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }

        $request->merge(['ticket_id' => $id]);
        $isSuccess = $this->ticket->reply($request);

        if ($isSuccess['type'] === 'success') {
            return response()->json([
                'success' => true,
                'message' => $isSuccess['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $isSuccess['message'] ?? 'Failed to send reply'
        ], 400);
    }

    /**
     * Close Ticket
     * 
     * Close a ticket
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     * @authenticated
     * @urlParam id integer required Ticket ID. Example: 1
     * @response 200 {
     *   "success": true,
     *   "message": "Ticket closed successfully"
     * }
     */
    public function close($id, Request $request): JsonResponse
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }

        $ticket->status = 1; // closed
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed successfully'
        ]);
    }
}
