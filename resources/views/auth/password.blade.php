@extends('layouts.app')

@section('title', 'Change Password - Luxeshop POS')

@section('content')
<div class="glass-panel" style="max-width: 600px; margin: 3rem auto; padding: 2.5rem; border-radius: var(--radius-md);">
    <div style="border-bottom: 1px solid var(--card-border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Update Password</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Change your login password. We recommend using a strong and unique password.</p>
    </div>

    <form action="{{ route('password.update') }}" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
        @csrf

        <div class="form-group">
            <label class="form-label" for="current_password">Current Password</label>
            <input class="form-input @error('current_password') is-invalid @enderror" type="password" id="current_password" name="current_password" required placeholder="Enter current login password">
            @error('current_password')
                <p style="color: var(--accent-coral); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="new_password">New Password</label>
            <input class="form-input @error('new_password') is-invalid @enderror" type="password" id="new_password" name="new_password" required placeholder="Enter new password (min. 6 characters)">
            @error('new_password')
                <p style="color: var(--accent-coral); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="new_password_confirmation">Confirm New Password</label>
            <input class="form-input" type="password" id="new_password_confirmation" name="new_password_confirmation" required placeholder="Re-type new password">
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 1rem;">
            <button type="submit" class="btn-primary" style="width: auto; padding: 0.85rem 2rem; background: var(--primary); border: none;">Update Password</button>
            <a href="/" class="btn-secondary" style="text-decoration: none; padding: 0.85rem 1.5rem; text-align: center;">Cancel</a>
        </div>
    </form>
</div>
@endsection
