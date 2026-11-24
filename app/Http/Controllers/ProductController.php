<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPortion;
use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $activeBranchId = session('active_branch_id', 'all');

        $query = Product::where('tenant_id', $tenantId)->with('branch', 'portions')->orderBy('name');
        if ($activeBranchId !== 'all') {
            $query->where(function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId)->orWhereNull('branch_id');
            });
        }
        $products = $query->get();
        $categoriesList = Category::orderBy('name')->pluck('name');
        $branches = Branch::where('tenant_id', $tenantId)->where('is_active', true)->get();

        return view('products.index', compact('products', 'categoriesList', 'branches'));
    }

    public function toggleActive(Product $product)
    {
        // Security: ensure the product belongs to the current tenant
        if ($product->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'enabled' : 'disabled';
        return redirect()->route('products.index')
            ->with('success', "Product \"{$product->name}\" has been {$status} on the POS.");
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:191',
            'category'            => 'nullable|string|max:100',
            'sku'                 => 'nullable|string|max:100',
            'sold_by'             => 'nullable|in:unit,weight,volume,length,pack',
            'base_unit'           => 'nullable|string|max:20',
            'price'               => 'required|numeric|min:0',
            'cost'                => 'required|numeric|min:0',
            'quantity'            => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|numeric|min:0',
            'branch_id'           => 'nullable|exists:branches,id',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            // Portions (optional)
            'portions'            => 'nullable|array',
            'portions.*.label'    => 'required_with:portions|string|max:50',
            'portions.*.quantity' => 'required_with:portions|numeric|min:0.001',
            'portions.*.price'    => 'required_with:portions|numeric|min:0',
        ]);

        $sku = $request->sku ?: $this->generateUniqueSku();

        if (Product::where('sku', $sku)->exists()) {
            return back()->withErrors(['sku' => 'The SKU/Barcode already exists in your inventory.'])->withInput();
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $catName = $request->category ? trim($request->category) : 'General';
        Category::firstOrCreate(['name' => $catName], ['description' => $catName . ' catalog category.']);

        $branchId = $request->filled('branch_id') ? $request->branch_id : (session('active_branch_id') !== 'all' ? session('active_branch_id') : null);

        DB::beginTransaction();
        try {
            $product = Product::create([
                'tenant_id'           => auth()->user()->tenant_id,
                'branch_id'           => $branchId,
                'name'                => $request->name,
                'category'            => $catName,
                'sold_by'             => $request->input('sold_by') ?: 'unit',
                'base_unit'           => $request->input('base_unit') ?: 'pcs',
                'sku'                 => $sku,
                'price'               => $request->price,
                'cost'                => $request->cost,
                'quantity'            => $request->quantity,
                'low_stock_threshold' => $request->low_stock_threshold,
                'image_path'          => $imagePath,
            ]);

            // Save preset portions
            $this->syncPortions($product, $request->input('portions', []));

            if ($product->quantity > 0) {
                InventoryLog::create([
                    'tenant_id'     => auth()->user()->tenant_id,
                    'branch_id'     => $branchId,
                    'product_id'    => $product->id,
                    'change_amount' => $product->quantity,
                    'type'          => 'adjustment',
                    'description'   => 'Initial stock addition upon product creation. (' . $product->quantity . ' ' . $product->base_unit . ')',
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to save product: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('products.index')->with('success', 'Product added successfully! Barcode SKU is: ' . $product->sku);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'                => 'required|string|max:191',
            'category'            => 'nullable|string|max:100',
            'sku'                 => 'required|string|max:100',
            'sold_by'             => 'nullable|in:unit,weight,volume,length,pack',
            'base_unit'           => 'nullable|string|max:20',
            'price'               => 'required|numeric|min:0',
            'cost'                => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|numeric|min:0',
            'branch_id'           => 'nullable|exists:branches,id',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'portions'            => 'nullable|array',
            'portions.*.label'    => 'required_with:portions|string|max:50',
            'portions.*.quantity' => 'required_with:portions|numeric|min:0.001',
            'portions.*.price'    => 'required_with:portions|numeric|min:0',
        ]);

        if (Product::where('sku', $request->sku)->where('id', '!=', $product->id)->exists()) {
            return back()->withErrors(['sku' => 'The SKU/Barcode is already used by another product.'])->withInput();
        }

        $catName = $request->category ? trim($request->category) : 'General';
        Category::firstOrCreate(['name' => $catName], ['description' => $catName . ' catalog category.']);

        $data = [
            'name'                => $request->name,
            'category'            => $catName,
            'sold_by'             => $request->input('sold_by') ?: ($product->sold_by ?: 'unit'),
            'base_unit'           => $request->input('base_unit') ?: ($product->base_unit ?: 'pcs'),
            'sku'                 => $request->sku,
            'price'               => $request->price,
            'cost'                => $request->cost,
            'low_stock_threshold' => $request->low_stock_threshold,
            'branch_id'           => $request->filled('branch_id') ? $request->branch_id : null,
        ];

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        DB::beginTransaction();
        try {
            $product->update($data);
            $this->syncPortions($product, $request->input('portions', []));
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()])->withInput();
        }

        return redirect()->route('products.index')->with('success', 'Product details updated successfully.');
    }

    public function updateStock(Request $request, Product $product)
    {
        $request->validate([
            'adjustment_qty' => 'required|numeric',
            'description'    => 'nullable|string|max:191',
        ]);

        $change = (float) $request->adjustment_qty;
        if ($change == 0) {
            return back();
        }

        $newQty = (float) $product->quantity + $change;
        if ($newQty < 0) {
            return back()->withErrors(['adjustment_qty' => 'Adjustment would result in negative stock.'])->withInput();
        }

        $product->update(['quantity' => $newQty]);

        $branchId = $product->branch_id ?? (session('active_branch_id') !== 'all' ? session('active_branch_id') : null);

        InventoryLog::create([
            'tenant_id'     => auth()->user()->tenant_id,
            'branch_id'     => $branchId,
            'product_id'    => $product->id,
            'change_amount' => $change,
            'type'          => 'adjustment',
            'description'   => ($request->description ?: 'Manual stock adjustment.') . ' (' . number_format(abs($change), 3) . ' ' . $product->base_unit . ')',
        ]);

        return redirect()->route('products.index')->with('success', 'Stock updated successfully for ' . $product->name);
    }

    public function destroy(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    /**
     * Sync preset portions for a product.
     * Replaces all existing portions with the new list.
     */
    private function syncPortions(Product $product, array $portions): void
    {
        $product->portions()->delete();

        foreach ($portions as $index => $portion) {
            if (!isset($portion['label']) || !isset($portion['quantity']) || !isset($portion['price'])) {
                continue;
            }
            ProductPortion::create([
                'product_id' => $product->id,
                'label'      => $portion['label'],
                'quantity'   => $portion['quantity'],
                'price'      => $portion['price'],
                'sort_order' => $index,
            ]);
        }
    }

    private function generateUniqueSku()
    {
        do {
            $sku = 'LUX-' . rand(100000, 999999);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}
