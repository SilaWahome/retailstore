@extends('layouts.app')

@section('title', 'Support Tickets - Retail POS')

@section('content')
@php
    $tenant = Auth::user()->tenant ?? null;
    if ($tenant) {
        $tenant->refresh();
    }
    $isSuspended = $tenant && !in_array($tenant->subscription_status, ['active', 'trial']) && Auth::user()->role !== 'superadmin';
@endphp

@if($isSuspended)
<!-- ── SUSPENDED ACCOUNT MODAL POPUP ──────────────────────────────── -->
<div id="subscription-suspended-modal" style="position: fixed; inset: 0; z-index: 9999; background: rgba(5, 5, 10, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); display: flex; align-items: center; justify-content: center; padding: 1.5rem;">
    <div style="background: var(--card-bg); border: 1px solid rgba(239, 68, 68, 0.4); box-shadow: 0 20px 50px rgba(239, 68, 68, 0.25); border-radius: var(--radius-md); max-width: 520px; width: 100%; padding: 2.25rem; text-align: center; position: relative; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        
        <div style="width: 72px; height: 72px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); border: 2px solid var(--accent-coral); color: var(--accent-coral); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 1.25rem; box-shadow: 0 0 25px rgba(239, 68, 68, 0.3);">
            🔒
        </div>

        <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.6rem; letter-spacing: -0.5px;">
            Subscription Suspended
        </h2>

        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
            Access to <strong>{{ $tenant->name }}</strong> has been temporarily suspended or your plan has expired. All store resources (POS, inventory, reports, users) are currently locked.
        </p>

        <div style="background: rgba(239, 68, 68, 0.08); border: 1px dashed rgba(239, 68, 68, 0.3); border-radius: var(--radius-sm); padding: 1rem; margin-bottom: 1.75rem; text-align: left; font-size: 0.88rem; color: var(--text-main);">
            <div style="font-weight: 700; color: #f87171; margin-bottom: 0.25rem;">
                Action Required
            </div>
            Please contact your system administrator or submit a support ticket below to restore access.
        </div>

        <div style="display: flex; gap: 0.75rem; flex-direction: column;">
            <button type="button" onclick="document.getElementById('subscription-suspended-modal').style.display='none';" class="btn-primary" style="padding: 0.85rem; font-size: 0.95rem; font-weight: 700; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; cursor: pointer; width: 100%;">
                Submit a Support Ticket
            </button>
            <a href="{{ route('logout') }}" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: underline; margin-top: 0.25rem;">
                Log out of account
            </a>
        </div>
    </div>
</div>

<style>
@keyframes popIn {
    0% { opacity: 0; transform: scale(0.9); }
    100% { opacity: 1; transform: scale(1); }
}
</style>
@endif

<div class="page-header">
    <h1 class="page-title">Support & Billing Help</h1>
</div>

<div class="grid-2" style="align-items: start; gap: 2rem; margin-bottom: 3rem;">
    <!-- Submit Ticket Form -->
    <div class="glass-panel panel-body">
        <h2 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.5rem;">Create New Ticket</h2>
        <form action="{{ route('support.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label" for="subject">Subject</label>
                <input type="text" name="subject" id="subject" class="form-input" placeholder="Summarize your issue..." required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="message">Detailed Message</label>
                <textarea name="message" id="message" class="form-input" rows="6" placeholder="Provide complete details about your inquiry..." required style="resize: vertical; font-family: inherit; font-size: 0.9rem; padding: 0.75rem;"></textarea>
            </div>
            
            <button type="submit" class="btn-primary" style="margin-top: 1rem;">Submit Support Ticket</button>
        </form>
    </div>

    <!-- Tickets List -->
    <div class="glass-panel">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
            <h2 style="font-size: 1.15rem; font-weight: 700;">Previous Tickets</h2>
        </div>
        <div style="padding: 1rem; max-height: 450px; overflow-y: auto;">
            @forelse($tickets as $ticket)
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--card-border); padding: 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1rem; position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.8rem; color: var(--text-muted);">#{{ $ticket->id }} - {{ $ticket->created_at->format('Y-m-d H:i') }}</span>
                        @if($ticket->status === 'open')
                            <span class="badge badge-pending" style="font-size: 0.75rem;">Open</span>
                        @else
                            <span class="badge badge-received" style="font-size: 0.75rem; background: var(--accent-emerald-glow); color: var(--accent-emerald);">Resolved</span>
                        @endif
                    </div>
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">{{ $ticket->subject }}</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.5rem;">{{ $ticket->message }}</p>
                    <div style="font-size: 0.75rem; color: var(--text-muted); border-top: 1px dashed var(--card-border); padding-top: 0.5rem; margin-top: 0.5rem;">
                        Submitted by: {{ $ticket->user->name ?? 'System' }}
                    </div>
                </div>
            @empty
                <p style="text-align: center; color: var(--text-muted); padding: 2rem;">No support tickets logged. Submit your first ticket using the form.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
