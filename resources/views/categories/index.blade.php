@extends('layouts.app')

@section('title', 'Product Categories - Luxeshop POS')

@section('content')
<div class="page-header">
    <h1 class="page-title">Product Categories</h1>
    <div class="page-actions">
        <a href="{{ route('sales') }}" class="btn-secondary" style="text-decoration: none; font-size: 0.85rem;">
            View Category Sales Charts on Dashboard
        </a>
    </div>
</div>

<div class="grid-2">
    <!-- Categories List Table -->
    <div class="glass-panel">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Store Product Categories</h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Manage catalog categories linked directly to POS filters, products, and sales charts.</p>
        </div>

        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Products Linked</th>
                        <th>Sales Revenue</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr>
                            <td><strong style="color: var(--text-main);">{{ $cat->name }}</strong></td>
                            <td><span style="font-size: 0.85rem; color: var(--text-muted);">{{ $cat->description ?: 'N/A' }}</span></td>
                            <td><span class="badge badge-received">{{ $cat->products_count }} products</span></td>
                            <td style="font-weight: 600; color: var(--primary-hover);">{{ $baseCurrency->symbol }}{{ number_format($cat->total_revenue, 2) }}</td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <button type="button" onclick="openEditModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ addslashes($cat->description) }}')" class="btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Edit</button>
                                    <form action="{{ route('categories.delete', $cat) }}" method="POST" onsubmit="return confirm('Delete category {{ addslashes($cat->name) }}?');" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-secondary" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2); padding: 0.3rem 0.6rem; font-size: 0.8rem;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No categories registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Category Panel -->
    <div class="glass-panel" style="height: fit-content;">
        <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">Add New Category</h2>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Register custom categories to group products and view performance metrics.</p>
        </div>

        <form action="{{ route('categories.store') }}" method="POST" class="panel-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
            @csrf

            <div class="form-group">
                <label class="form-label" for="name">Category Name *</label>
                <input class="form-input @error('name') is-invalid @enderror" type="text" id="name" name="name" required placeholder="e.g. Footwear, Electronics">
                @error('name')
                    <p style="color: var(--accent-coral); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Short Description</label>
                <input class="form-input" type="text" id="description" name="description" placeholder="e.g. Men and Women shoes catalog">
            </div>

            <button type="submit" class="btn-primary" style="padding: 0.8rem 1.5rem; align-self: flex-start; width: auto; background: var(--primary); border: none;">
                Create Category
            </button>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="edit-category-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div class="glass-panel" style="width: 100%; max-width: 500px; background: var(--select-bg); margin: auto; padding: 2rem; border-radius: var(--radius-md); box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; flex-direction: column; gap: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Edit Category</h2>
            <button type="button" onclick="closeEditModal()" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; border: none; background: transparent; color: var(--text-muted);">✕ Close</button>
        </div>
        <form id="edit-category-form" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="edit-name">Category Name *</label>
                <input class="form-input" type="text" id="edit-name" name="name" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-description">Description</label>
                <input class="form-input" type="text" id="edit-description" name="description">
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 0.5rem;">
                <button type="button" onclick="closeEditModal()" class="btn-secondary" style="width: auto;">Cancel</button>
                <button type="submit" class="btn-primary" style="width: auto; background: var(--primary); border: none;">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openEditModal(id, name, description) {
        document.getElementById('edit-category-form').action = `/categories/${id}`;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-description').value = description || '';
        document.getElementById('edit-category-modal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('edit-category-modal').style.display = 'none';
    }
</script>
@endsection
