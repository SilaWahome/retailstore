@extends('layouts.app')

@section('title', 'Inventory & Products - Retail Store POS')

@section('content')
<div class="page-header">
    <h1 class="page-title">Products & Inventory</h1>
    <div class="page-actions">
        <!-- Scroll/Trigger to Create Form -->
        <a href="#add-product-form" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
            <span>➕</span> Add New Product
        </a>
    </div>
</div>

<div class="grid-3" style="margin-bottom: 2rem;">
    <div class="glass-panel panel-body" style="display: flex; align-items: center; gap: 1rem;">
        <span style="font-size: 2.5rem;">📦</span>
        <div>
            <h3 style="font-size: 1.5rem; font-weight: 700;">{{ $products->count() }}</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Total Products</p>
        </div>
    </div>
    <div class="glass-panel panel-body" style="display: flex; align-items: center; gap: 1rem;">
        <span style="font-size: 2.5rem; color: #f59e0b;">⚠️</span>
        <div>
            <h3 style="font-size: 1.5rem; font-weight: 700; color: #f59e0b;">
                {{ $products->filter(fn($p) => $p->quantity <= $p->low_stock_threshold)->count() }}
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Low Stock Items</p>
        </div>
    </div>
    <div class="glass-panel panel-body" style="display: flex; align-items: center; gap: 1rem;">
        <span style="font-size: 2.5rem; color: #10b981;">💵</span>
        <div>
            <h3 style="font-size: 1.5rem; font-weight: 700; color: #10b981;">
                {{ $baseCurrency->symbol }}{{ number_format($products->sum(fn($p) => $p->quantity * $p->price), 2) }}
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Inventory Value</p>
        </div>
    </div>
</div>

<div class="glass-panel" style="margin-bottom: 2rem; overflow: hidden;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
        <h2 style="font-size: 1.25rem; font-weight: 700;">Active Inventory</h2>
    </div>
    <div class="custom-table-container" style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Branch</th>
                    <th>Category</th>
                    <th>SKU / Barcode</th>
                    <th>QR Code</th>
                    <th>Cost Price</th>
                    <th>Selling Price</th>
                    <th>Current Stock</th>
                    <th>Low Limit</th>
                    <th>Adjust Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr style="{{ !$product->is_active ? 'opacity: 0.55;' : ($product->quantity <= $product->low_stock_threshold ? 'background: rgba(239, 68, 68, 0.03);' : '') }}">
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 48px; height: 48px; border-radius: 8px; background: rgba(255,255,255,0.03); overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid var(--card-border);">
                                    @if($product->image_path)
                                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <span style="font-size: 1.2rem; color: var(--text-muted);">🛍️</span>
                                    @endif
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: var(--text-main);">{{ $product->name }}</div>
                                    @if(!$product->is_active)
                                        <div style="font-size: 0.7rem; color: #f59e0b; margin-top: 0.2rem; font-weight: 600; letter-spacing: 0.02em;">🔕 DISABLED ON POS</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($product->branch)
                                <span style="font-size: 0.8rem; font-weight: 600; background: rgba(139, 92, 246, 0.1); color: var(--primary); padding: 0.2rem 0.5rem; border-radius: 4px;">
                                    📍 {{ $product->branch->name }}
                                </span>
                            @else
                                <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">All Branches</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge" style="background: rgba(255,255,255,0.03); border: 1px solid var(--card-border); color: var(--text-main);">
                                {{ $product->category ?: 'General' }}
                            </span>
                        </td>
                        <td>
                            <code style="background: rgba(255,255,255,0.05); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem; border: 1px solid var(--card-border); color: var(--text-main);">{{ $product->sku }}</code>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data={{ urlencode($product->sku) }}" alt="QR" style="border-radius: 4px; background: #fff; padding: 2px;" title="Scan/Print Product QR">
                                <a href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($product->sku) }}" target="_blank" style="font-size: 0.7rem; color: var(--primary); text-decoration: none;">Print QR</a>
                            </div>
                        </td>
                        <td>{{ $baseCurrency->symbol }}{{ number_format($product->cost, 2) }}</td>
                        <td style="font-weight: 600; color: var(--primary-hover);">{{ $baseCurrency->symbol }}{{ number_format($product->price, 2) }}</td>
                        <td>
                            <span class="badge {{ $product->quantity <= $product->low_stock_threshold ? 'badge-cancelled' : 'badge-received' }}" style="font-size: 0.9rem;">
                                {{ $product->quantity }}
                            </span>
                            @if($product->quantity <= $product->low_stock_threshold)
                                <div style="font-size: 0.7rem; color: #fca5a5; margin-top: 0.25rem; font-weight: 500;">Low Stock Alert</div>
                            @endif
                        </td>
                        <td>{{ $product->low_stock_threshold }}</td>
                        <td>
                            <form action="{{ route('products.update-stock', $product) }}" method="POST" style="display: flex; gap: 0.5rem; align-items: center; max-width: 180px;">
                                @csrf
                                <input type="number" name="adjustment_qty" required placeholder="Qty" style="width: 60px; padding: 0.4rem; background: rgba(0,0,0,0.2); border: 1px solid var(--card-border); color: var(--text-main); border-radius: 4px; outline: none; font-size: 0.85rem;">
                                <button type="submit" class="btn-secondary" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; background: var(--primary); color: #fff; border: none;">Apply</button>
                            </form>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                <button type="button" class="btn-secondary" style="color: var(--primary); border-color: rgba(139, 92, 246, 0.2); padding: 0.4rem 0.6rem; font-size: 0.8rem; background: rgba(139, 92, 246, 0.02);" onclick='openEditModal({!! json_encode($product) !!})'>Edit</button>

                                {{-- Enable / Disable toggle --}}
                                <form action="{{ route('products.toggle-active', $product) }}" method="POST"
                                      onsubmit="return confirm('{{ $product->is_active ? 'Disable this product on POS?' : 'Enable this product on POS?' }}')"
                                      style="margin: 0;">
                                    @csrf
                                    @if($product->is_active)
                                        <button type="submit" class="btn-secondary"
                                                style="color: #f59e0b; border-color: rgba(245,158,11,0.3); padding: 0.4rem 0.6rem; font-size: 0.8rem; background: rgba(245,158,11,0.05);">
                                            🔕 Disable
                                        </button>
                                    @else
                                        <button type="submit" class="btn-secondary"
                                                style="color: #10b981; border-color: rgba(16,185,129,0.3); padding: 0.4rem 0.6rem; font-size: 0.8rem; background: rgba(16,185,129,0.05);">
                                            ✅ Enable
                                        </button>
                                    @endif
                                </form>

                                <form action="{{ route('products.delete', $product) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="btn-secondary" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.2); padding: 0.4rem 0.6rem; font-size: 0.8rem; background: rgba(239, 68, 68, 0.02); border: 1px solid rgba(239, 68, 68, 0.2);">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No products found. Use the form below to add your first product!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="glass-panel" id="add-product-form" style="max-width: 800px; margin: 0 auto 3rem auto;">
    <div class="panel-body" style="border-bottom: 1px solid var(--card-border);">
        <h2 style="font-size: 1.25rem; font-weight: 700;">Add Product to Inventory</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">Create a product record with branch assignment and QR scanning support.</p>
    </div>
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="panel-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
        @csrf
        
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="name">Product Name *</label>
                <input class="form-input" type="text" id="name" name="name" required placeholder="e.g. Gucci Handbag">
            </div>
            <div class="form-group">
                <label class="form-label" for="category">Category</label>
                <input class="form-input" type="text" id="category" name="category" list="category-list" placeholder="e.g. Bags, Shoes">
                <datalist id="category-list">
                    @foreach(($categoriesList ?? $products->pluck('category'))->unique() as $cat)
                        <option value="{{ $cat }}">
                    @endforeach
                </datalist>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="branch_id">Target Branch</label>
                <select class="form-input" id="branch_id" name="branch_id" style="padding: 0.7rem;">
                    <option value="">🏢 All Branches (Available Store-wide)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">📍 {{ $branch->name }} ({{ $branch->code ?? 'MAIN' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="sku">SKU / Barcode (Leave blank to auto-generate)</label>
                <input class="form-input" type="text" id="sku" name="sku" placeholder="e.g. GUC-9801">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="sold_by">Sold By *</label>
                <select class="form-input" id="sold_by" name="sold_by" style="padding: 0.7rem;" required>
                    <option value="unit" selected>Unit / Piece</option>
                    <option value="weight">Weight (kg / g)</option>
                    <option value="volume">Volume (L / ml)</option>
                    <option value="length">Length (m / cm)</option>
                    <option value="pack">Pack / Box</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="base_unit">Base Unit *</label>
                <input class="form-input" type="text" id="base_unit" name="base_unit" value="pcs" required placeholder="e.g. pcs, kg, L, pack">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="low_stock_threshold">Low Stock Warning Limit *</label>
                <input class="form-input" type="number" id="low_stock_threshold" name="low_stock_threshold" value="5" required placeholder="5">
            </div>
            <div class="form-group">
                <label class="form-label" for="quantity">Initial Stock Quantity *</label>
                <input class="form-input" type="number" id="quantity" name="quantity" value="0" required placeholder="0">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" for="cost">Supplier Cost Price *</label>
                <input class="form-input" type="number" step="0.01" id="cost" name="cost" required placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label" for="price">Retail Selling Price *</label>
                <input class="form-input" type="number" step="0.01" id="price" name="price" required placeholder="0.00">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="image">Product Image / Icon (Optional)</label>
            <input class="form-input" type="file" id="image" name="image" accept="image/*" style="padding: 0.5rem;">
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 0.5rem; align-self: flex-start; width: auto; padding: 0.85rem 2rem;">Save Product</button>
    </form>
</div>

<!-- Edit Product Modal -->
<div id="edit-product-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div class="glass-panel" style="width: 100%; max-width: 650px; background: var(--select-bg); border-color: var(--card-border); margin: auto; padding: 2rem; border-radius: var(--radius-md); box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; flex-direction: column; gap: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); padding-bottom: 1rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);">Edit Product Details</h2>
            <button type="button" onclick="closeEditModal()" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; width: auto; margin: 0; border: none; background: transparent; color: var(--text-muted);">✕ Close</button>
        </div>
        <form id="edit-product-form" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.25rem;">
            @csrf
            
            <div class="grid-2">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="edit-name">Product Name *</label>
                    <input class="form-input" type="text" id="edit-name" name="name" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="edit-category">Category</label>
                    <input class="form-input" type="text" id="edit-category" name="category" list="category-list">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="edit-branch-id">Target Branch</label>
                    <select class="form-input" id="edit-branch-id" name="branch_id" style="padding: 0.7rem;">
                        <option value="">🏢 All Branches (Available Store-wide)</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">📍 {{ $branch->name }} ({{ $branch->code ?? 'MAIN' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="edit-sku">SKU / Barcode *</label>
                    <input class="form-input" type="text" id="edit-sku" name="sku" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="edit-cost">Cost Price ({{ $baseCurrency->symbol }}) *</label>
                    <input class="form-input" type="number" step="0.01" id="edit-cost" name="cost" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="edit-price">Selling Price ({{ $baseCurrency->symbol }}) *</label>
                    <input class="form-input" type="number" step="0.01" id="edit-price" name="price" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="edit-sold-by">Sold By *</label>
                    <select class="form-input" id="edit-sold-by" name="sold_by" style="padding: 0.7rem;" required>
                        <option value="unit">Unit / Piece</option>
                        <option value="weight">Weight (kg / g)</option>
                        <option value="volume">Volume (L / ml)</option>
                        <option value="length">Length (m / cm)</option>
                        <option value="pack">Pack / Box</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="edit-base-unit">Base Unit *</label>
                    <input class="form-input" type="text" id="edit-base-unit" name="base_unit" value="pcs" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="edit-low-stock-threshold">Low Stock Warning Limit *</label>
                <input class="form-input" type="number" id="edit-low-stock-threshold" name="low_stock_threshold" required>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="edit-image">Product Image / Icon (Optional)</label>
                <input class="form-input" type="file" id="edit-image" name="image" accept="image/*" style="padding: 0.5rem;">
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Leave blank to keep existing image.</p>
            </div>

            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" onclick="closeEditModal()" class="btn-secondary" style="width: auto; padding: 0.75rem 1.5rem; margin: 0;">Cancel</button>
                <button type="submit" class="btn-primary" style="width: auto; padding: 0.75rem 1.5rem; margin: 0; background: var(--primary); border: none;">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openEditModal(product) {
        const modal = document.getElementById('edit-product-modal');
        const form = document.getElementById('edit-product-form');
        
        form.action = `/products/${product.id}`;
        document.getElementById('edit-name').value = product.name;
        document.getElementById('edit-category').value = product.category || 'General';
        document.getElementById('edit-branch-id').value = product.branch_id || '';
        document.getElementById('edit-sku').value = product.sku;
        document.getElementById('edit-sold-by').value = product.sold_by || 'unit';
        document.getElementById('edit-base-unit').value = product.base_unit || 'pcs';
        document.getElementById('edit-low-stock-threshold').value = product.low_stock_threshold;
        document.getElementById('edit-cost').value = product.cost;
        document.getElementById('edit-price').value = product.price;

        modal.style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('edit-product-modal').style.display = 'none';
    }

    window.addEventListener('click', function(e) {
        const modal = document.getElementById('edit-product-modal');
        if (e.target === modal) {
            closeEditModal();
        }
    });
</script>
@endsection
