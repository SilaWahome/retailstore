<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    public function index()
    {
        // Automatically scoped by tenant (thanks to HasTenant trait)
        $tickets = SupportTicket::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('support.index', compact('tickets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:191',
            'message' => 'required|string|min:5',
        ]);

        SupportTicket::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'open',
        ]);

        return redirect()->route('support.index')->with('success', 'Your support ticket has been submitted to SaaS Controller. We will review it shortly.');
    }
}
