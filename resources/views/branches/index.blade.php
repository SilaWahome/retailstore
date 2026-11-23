@extends('layouts.app')

@section('title', 'Branch Management - Retail Store POS')

@section('content')
<div style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 700; background: linear-gradient(to right, #fff, var(--primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                Store Branches
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Manage store branches, locations, and branch contexts</p>
        </div>

        <button onclick="document.getElementById('addBranchModal').style.display='flex'" class="btn-primary" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.25rem; font-weight: 600; cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add New Branch
        </button>
    </div>

    <!-- Branches Grid -->
    <div class="table-responsive glass-panel" style="padding: 1.5rem; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--card-border); font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">
                    <th style="padding: 0.75rem 1rem;">Code</th>
                    <th style="padding: 0.75rem 1rem;">Branch Name</th>
                    <th style="padding: 0.75rem 1rem;">Location / Address</th>
                    <th style="padding: 0.75rem 1rem;">Phone</th>
                    <th style="padding: 0.75rem 1rem;">Status</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 0.95rem;">
                    <td style="padding: 1rem;">
                        <span style="font-weight: 700; color: var(--primary); background: rgba(139, 92, 246, 0.1); padding: 0.25rem 0.5rem; border-radius: 4px;">
                            {{ $branch->code ?? 'MAIN' }}
                        </span>
                    </td>
                    <td style="padding: 1rem; font-weight: 600;">
                        {{ $branch->name }}
                        @if(session('active_branch_id') == $branch->id)
                            <span style="font-size: 0.7rem; background: var(--accent-emerald-glow); color: var(--accent-emerald); padding: 0.15rem 0.4rem; border-radius: 10px; margin-left: 0.5rem; border: 1px solid var(--accent-emerald);">
                                Active Context
                            </span>
                        @endif
                    </td>
                    <td style="padding: 1rem; color: var(--text-muted);">
                        {{ $branch->address ?? 'N/A' }}
                    </td>
                    <td style="padding: 1rem; color: var(--text-muted);">
                        {{ $branch->phone ?? 'N/A' }}
                    </td>
                    <td style="padding: 1rem;">
                        @if($branch->is_active)
                            <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: rgba(16, 185, 129, 0.15); color: #10b981;">
                                Active
                            </span>
                        @else
                            <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td style="padding: 1rem; text-align: right;">
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <button onclick="editBranch({{ json_encode($branch) }})" class="btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 6px; cursor: pointer;">
                                Edit
                            </button>
                            <form action="{{ route('branches.toggle', $branch->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 6px; cursor: pointer; {{ $branch->is_active ? 'color: #ef4444; border-color: rgba(239, 68, 68, 0.3);' : 'color: #10b981; border-color: rgba(16, 185, 129, 0.3);' }}">
                                    {{ $branch->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                        No store branches found. Click "Add New Branch" to create one.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Add Branch Modal -->
<div id="addBranchModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div class="glass-panel" style="width: 100%; max-width: 480px; padding: 2rem; border-radius: var(--radius-md);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700;">Add New Branch</h3>
            <button onclick="document.getElementById('addBranchModal').style.display='none'" style="background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form action="{{ route('branches.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Branch Name *</label>
                <input type="text" name="name" required placeholder="e.g. Downtown Branch" class="form-input" style="width: 100%; padding: 0.65rem; border-radius: 6px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Branch Code</label>
                <input type="text" name="code" placeholder="e.g. DT01" class="form-input" style="width: 100%; padding: 0.65rem; border-radius: 6px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Address / Location</label>
                <input type="text" name="address" placeholder="e.g. 123 Commercial Ave" class="form-input" style="width: 100%; padding: 0.65rem; border-radius: 6px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Phone Number</label>
                <input type="text" name="phone" placeholder="e.g. +1 555-0199" class="form-input" style="width: 100%; padding: 0.65rem; border-radius: 6px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            </div>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('addBranchModal').style.display='none'" class="btn-secondary" style="padding: 0.65rem 1.25rem;">Cancel</button>
                <button type="submit" class="btn-primary" style="padding: 0.65rem 1.25rem;">Save Branch</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Branch Modal -->
<div id="editBranchModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div class="glass-panel" style="width: 100%; max-width: 480px; padding: 2rem; border-radius: var(--radius-md);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700;">Edit Branch</h3>
            <button onclick="document.getElementById('editBranchModal').style.display='none'" style="background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form id="editBranchForm" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Branch Name *</label>
                <input type="text" id="edit_name" name="name" required class="form-input" style="width: 100%; padding: 0.65rem; border-radius: 6px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Branch Code</label>
                <input type="text" id="edit_code" name="code" class="form-input" style="width: 100%; padding: 0.65rem; border-radius: 6px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Address / Location</label>
                <input type="text" id="edit_address" name="address" class="form-input" style="width: 100%; padding: 0.65rem; border-radius: 6px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">Phone Number</label>
                <input type="text" id="edit_phone" name="phone" class="form-input" style="width: 100%; padding: 0.65rem; border-radius: 6px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            </div>
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('editBranchModal').style.display='none'" class="btn-secondary" style="padding: 0.65rem 1.25rem;">Cancel</button>
                <button type="submit" class="btn-primary" style="padding: 0.65rem 1.25rem;">Update Branch</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function editBranch(branch) {
    document.getElementById('edit_name').value = branch.name;
    document.getElementById('edit_code').value = branch.code || '';
    document.getElementById('edit_address').value = branch.address || '';
    document.getElementById('edit_phone').value = branch.phone || '';
    document.getElementById('editBranchForm').action = '/branches/' + branch.id;
    document.getElementById('editBranchModal').style.display = 'flex';
}
</script>
@endsection
