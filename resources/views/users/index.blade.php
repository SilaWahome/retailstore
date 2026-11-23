@extends('layouts.app')

@section('title', 'User & Staff Management - Retail Store POS')

@section('content')
<div class="page-header">
    <h1 class="page-title">User & Staff Management</h1>
    <div class="page-actions">
        <span style="font-size: 0.85rem; color: var(--text-muted);">
            Manage store team members, branch assignments, system roles, and access rights.
        </span>
    </div>
</div>

<div class="grid-2">
    <!-- Staff List Table -->
    <div class="glass-panel">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Store Staff & Users</h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Overview of registered user accounts and their assigned branch and role responsibilities.</p>
        </div>

        <div class="custom-table-container" style="overflow-x: auto;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Assigned Branch</th>
                        <th>Role</th>
                        <th>Today's Shift</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $staff)
                        @php
                            $todayAttendance = \App\Models\Attendance::where('user_id', $staff->id)
                                ->where('date', \Carbon\Carbon::today()->toDateString())
                                ->first();
                        @endphp
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.6rem;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.85rem;">
                                        {{ strtoupper(substr($staff->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <strong style="color: var(--text-main); display: block;">{{ $staff->name }}</strong>
                                        @if($staff->id === auth()->id())
                                            <span style="font-size: 0.7rem; color: var(--accent-emerald); font-weight: 600;">(You)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td><span style="font-size: 0.85rem; color: var(--text-muted);">{{ $staff->email }}</span></td>
                            <td>
                                @if($staff->branch)
                                    <span style="font-size: 0.8rem; font-weight: 600; background: rgba(139, 92, 246, 0.1); color: var(--primary); padding: 0.2rem 0.5rem; border-radius: 4px;">
                                        📍 {{ $staff->branch->name }}
                                    </span>
                                @else
                                    <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">All Branches</span>
                                @endif
                            </td>
                            <td>
                                @if($staff->role === 'admin')
                                    <span class="badge" style="background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3);">Admin</span>
                                @elseif($staff->role === 'manager')
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">Manager</span>
                                @elseif($staff->role === 'accountant')
                                    <span class="badge badge-received">Accountant</span>
                                @elseif($staff->role === 'cashier')
                                    <span class="badge badge-pending">Server</span>
                                @else
                                    <span class="badge">{{ ucfirst($staff->role) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($todayAttendance)
                                    @if($todayAttendance->check_out)
                                        <span class="badge badge-received" style="font-size: 0.75rem;">Off Shift</span>
                                    @else
                                        <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 0.75rem;">On Shift (In {{ substr($todayAttendance->check_in, 0, 5) }})</span>
                                    @endif
                                @else
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">Not checked in</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <button type="button" onclick="openEditUserModal({{ $staff->id }}, '{{ addslashes($staff->name) }}', '{{ addslashes($staff->email) }}', '{{ $staff->role }}', '{{ $staff->branch_id }}')" class="btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Edit</button>
                                    
                                    @if($staff->id !== auth()->id())
                                        <form action="{{ route('users.delete', $staff) }}" method="POST" onsubmit="return confirm('Remove user {{ addslashes($staff->name) }} from store?');" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="btn-secondary" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2); padding: 0.3rem 0.6rem; font-size: 0.8rem;">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No staff users registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Staff Panel -->
    <div class="glass-panel" style="height: fit-content;">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Add New Staff Member</h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Create a user login, assign a branch, and set role permissions.</p>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="panel-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
            @csrf

            <div class="form-group">
                <label class="form-label" for="name">Full Name *</label>
                <input class="form-input @error('name') is-invalid @enderror" type="text" id="name" name="name" required placeholder="e.g. Jane Doe" value="{{ old('name') }}">
                @error('name')
                    <p style="color: var(--accent-coral); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input class="form-input @error('email') is-invalid @enderror" type="email" id="email" name="email" required placeholder="e.g. server@store.com" value="{{ old('email') }}">
                @error('email')
                    <p style="color: var(--accent-coral); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password *</label>
                <input class="form-input @error('password') is-invalid @enderror" type="password" id="password" name="password" required placeholder="Minimum 6 characters">
                @error('password')
                    <p style="color: var(--accent-coral); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="branch_id">Assigned Branch</label>
                <select class="form-input" id="branch_id" name="branch_id" style="padding: 0.7rem;">
                    <option value="">🏢 All Branches (No specific branch restrictions)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>📍 {{ $branch->name }} ({{ $branch->code ?? 'MAIN' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="role">System Role *</label>
                <select class="form-input @error('role') is-invalid @enderror" id="role" name="role" required style="padding: 0.7rem;">
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role')
                    <p style="color: var(--accent-coral); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--card-border); padding: 0.85rem; border-radius: var(--radius-sm); font-size: 0.8rem; color: var(--text-muted);">
                <strong style="color: var(--text-main); display: block; margin-bottom: 0.25rem;">Role Rights Overview:</strong>
                <ul style="margin: 0; padding-left: 1.2rem; display: flex; flex-direction: column; gap: 0.25rem;">
                    <li><strong>Server:</strong> Daily sales dashboard & POS checkout.</li>
                    <li><strong>Accountant:</strong> Reports, balance sheets, expenses & POs.</li>
                    <li><strong>Manager:</strong> Products, categories, sales, reports & POs.</li>
                    <li><strong>Admin:</strong> Complete rights including user management & settings.</li>
                </ul>
            </div>

            <button type="submit" class="btn-primary" style="padding: 0.8rem 1.5rem; align-self: flex-start; width: auto; background: var(--primary); border: none;">
                Create Staff Account
            </button>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="edit-user-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div class="glass-panel" style="width: 100%; max-width: 500px; background: var(--select-bg); margin: auto; padding: 2rem; border-radius: var(--radius-md); box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; flex-direction: column; gap: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Edit Staff Account</h2>
            <button type="button" onclick="closeEditUserModal()" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; border: none; background: transparent; color: var(--text-muted);">✕ Close</button>
        </div>
        <form id="edit-user-form" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="edit-user-name">Full Name *</label>
                <input class="form-input" type="text" id="edit-user-name" name="name" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-user-email">Email Address *</label>
                <input class="form-input" type="email" id="edit-user-email" name="email" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-user-branch">Assigned Branch</label>
                <select class="form-input" id="edit-user-branch" name="branch_id" style="padding: 0.7rem;">
                    <option value="">🏢 All Branches (No specific branch restrictions)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">📍 {{ $branch->name }} ({{ $branch->code ?? 'MAIN' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-user-role">System Role *</label>
                <select class="form-input" id="edit-user-role" name="role" required style="padding: 0.7rem;">
                    @foreach($roles as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-user-password">Reset Password (Optional)</label>
                <input class="form-input" type="password" id="edit-user-password" name="password" placeholder="Leave blank to keep current password">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 0.5rem;">
                <button type="button" onclick="closeEditUserModal()" class="btn-secondary" style="width: auto;">Cancel</button>
                <button type="submit" class="btn-primary" style="width: auto; background: var(--primary); border: none;">Save User Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openEditUserModal(id, name, email, role, branchId) {
        document.getElementById('edit-user-form').action = `/users/${id}`;
        document.getElementById('edit-user-name').value = name;
        document.getElementById('edit-user-email').value = email;
        document.getElementById('edit-user-role').value = role;
        document.getElementById('edit-user-branch').value = branchId || '';
        document.getElementById('edit-user-password').value = '';
        document.getElementById('edit-user-modal').style.display = 'flex';
    }

    function closeEditUserModal() {
        document.getElementById('edit-user-modal').style.display = 'none';
    }
</script>
@endsection
