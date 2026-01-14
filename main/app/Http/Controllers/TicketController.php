<?php

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Helpers\NotificationHelper;
use App\Http\Requests\TicketRequest;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\UserTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TicketController extends Controller
{
    protected $ticket;
    public function __construct(UserTicketService $ticket)
    {
        $this->ticket = $ticket;
    }

    public function index()
    {
        $data['title'] = "Support Ticket";
        $userId = Auth::id();
        
        $data['tickets'] = Ticket::where('user_id', $userId)->with('ticketReplies')->latest()->paginate(Helper::pagination());
        $data['tickets_pending'] = Ticket::where('user_id', $userId)->where('status', '2')->count();
        $data['tickets_answered'] = Ticket::where('user_id', $userId)->where('status', '3')->count();
        $data['tickets_closed'] = Ticket::where('user_id', $userId)->where('status', '1')->count();
        $data['tickets_all'] = Ticket::where('user_id', $userId)->count();

        return view(Helper::themeView('user.ticket.list'))->with($data);
    }

    public function create()
    {
        // Tickets are created via modal in the list view, redirect to index
        return redirect()->route('user.ticket.index');
    }

    public function store(TicketRequest $request)
    {
        $isSuccess = $this->ticket->create($request);

        if ($isSuccess['type'] === 'success')
            return redirect()->route('user.ticket.index')->with('notify', NotificationHelper::success($isSuccess['message']));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $data['title'] = "Support Ticket Discussion";
        $data['ticket'] = Ticket::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();
        
        $data['tickets'] = Ticket::where('user_id', Auth::user()->id)->with('ticketReplies')->get();
        $data['ticket_reply'] = TicketReply::where('ticket_id', $data['ticket']->id)->latest()->get();

        return view(Helper::themeView('user.ticket.show'))->with($data);
    }


    public function update(TicketRequest $request, $id)
    {
        // Ensure user owns the ticket
        $ticket = Ticket::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $isSuccess = $this->ticket->update($request, $id);

        if ($isSuccess['type'] === 'success')
            return redirect()->route('user.ticket.index')->with('notify', NotificationHelper::success($isSuccess['message']));
    }


    public function destroy($id)
    {
        // Ensure user owns the ticket
        $ticket = Ticket::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $isSuccess = $this->ticket->delete($id);

        if ($isSuccess['type'] === 'success')
            return redirect()->back()->with('notify', NotificationHelper::success($isSuccess['message']));
    }

    public function reply(Request $request)
    {
        // Ensure user owns the ticket
        $ticket = Ticket::where('id', $request->ticket_id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $isSuccess = $this->ticket->reply($request);

        if ($isSuccess['type'] === 'success')
            return redirect()->back()->with('notify', NotificationHelper::success($isSuccess['message']));
    }

    public function statusChange($id)
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();
        
        $ticket->status = 1;
        $ticket->save();

        return redirect()->route('user.ticket.index')->with('notify', NotificationHelper::success('Closed conversation Successfully'));
    }

    public function ticketStatus(Request $request)
    {
        $ticketStatus = [
            'answered' => 3,
            'pending' => 2,
            'closed' => 1
        ];

        $data['title'] = "{$request->status} Support Ticket";

        $data['tickets'] = Ticket::where('user_id', Auth::user()->id)->where('status', $ticketStatus[$request->status])->with('ticketReplies')->latest()->paginate(Helper::pagination());

        $data['tickets_pending'] = Ticket::where('user_id', Auth::user()->id)->where('status', '2')->count();
        $data['tickets_answered'] = Ticket::where('user_id', Auth::user()->id)->where('status', '3')->count();
        $data['tickets_closed'] = Ticket::where('user_id', Auth::user()->id)->where('status', '1')->count();
        $data['tickets_all'] = Ticket::where('user_id', Auth::user()->id)->count();
        return view(Helper::themeView('user.ticket.list'))->with($data);
    }

    public function ticketDownload($id)
    {
        $ticket = TicketReply::findOrFail($id);

        if ($ticket->file) {

            $file = Helper::filePath('Ticket', true) . '/' . $ticket->file;

            if (file_exists($file)) {
                return response()->download($file);
            }
        }
    }

    // ========== BETA METHODS ==========

    public function betaIndex()
    {
        $data['title'] = "Support Ticket";
        $userId = Auth::id();

        $data['tickets'] = Ticket::where('user_id', $userId)->with('ticketReplies')->latest()->paginate(10);
        $data['tickets_pending'] = Ticket::where('user_id', $userId)->where('status', '2')->count();
        $data['tickets_answered'] = Ticket::where('user_id', $userId)->where('status', '3')->count();
        $data['tickets_closed'] = Ticket::where('user_id', $userId)->where('status', '1')->count();
        $data['tickets_all'] = Ticket::where('user_id', $userId)->count();

        return Inertia::render('User/TicketList', $data);
    }

    public function betaCreate()
    {
        return redirect()->route('user.beta.ticket.index');
    }

    public function betaStore(TicketRequest $request)
    {
        $isSuccess = $this->ticket->create($request);

        if ($isSuccess['type'] === 'success')
            return redirect()->route('user.beta.ticket.index')->with('notify', NotificationHelper::success($isSuccess['message']));
    }

    public function betaShow($id)
    {
        $data['title'] = "Support Ticket Discussion";
        $data['ticket'] = Ticket::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $data['ticket_reply'] = TicketReply::where('ticket_id', $data['ticket']->id)->latest()->get();

        return Inertia::render('User/TicketShow', $data);
    }

    public function betaUpdate(TicketRequest $request, $id)
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $isSuccess = $this->ticket->update($request, $id);

        if ($isSuccess['type'] === 'success')
            return redirect()->route('user.beta.ticket.index')->with('notify', NotificationHelper::success($isSuccess['message']));
    }

    public function betaDestroy($id)
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $isSuccess = $this->ticket->delete($id);

        if ($isSuccess['type'] === 'success')
            return redirect()->back()->with('notify', NotificationHelper::success($isSuccess['message']));
    }

    public function betaReply(Request $request)
    {
        $ticket = Ticket::where('id', $request->ticket_id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $isSuccess = $this->ticket->reply($request);

        if ($isSuccess['type'] === 'success')
            return redirect()->back()->with('notify', NotificationHelper::success($isSuccess['message']));
    }

    public function betaTicketStatus(Request $request)
    {
        $ticketStatus = [
            'answered' => 3,
            'pending' => 2,
            'closed' => 1
        ];

        $statusKey = $request->status ?? 'all';
        $statusValue = $statusKey !== 'all' ? $ticketStatus[$statusKey] : null;

        $data['title'] = ucfirst($statusKey) . " Support Ticket";
        $data['current_status'] = $statusKey;

        $query = Ticket::where('user_id', Auth::user()->id);
        if ($statusValue !== null) {
            $query->where('status', $statusValue);
        }
        $data['tickets'] = $query->with('ticketReplies')->latest()->paginate(10);

        $data['tickets_pending'] = Ticket::where('user_id', Auth::user()->id)->where('status', '2')->count();
        $data['tickets_answered'] = Ticket::where('user_id', Auth::user()->id)->where('status', '3')->count();
        $data['tickets_closed'] = Ticket::where('user_id', Auth::user()->id)->where('status', '1')->count();
        $data['tickets_all'] = Ticket::where('user_id', Auth::user()->id)->count();

        return Inertia::render('User/TicketList', $data);
    }
}
