<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get()->map(function ($cat) {
            $cat->products_count = Product::where('category', $cat->name)->count();
            $cat->total_revenue = DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->where('products.category', $cat->name)
                ->sum('sale_items.total');
            return $cat;
        });

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string|max:255',
        ]);

        $tenantId = auth()->user()->tenant_id;

        if (Category::where('name', $request->name)->exists()) {
            return back()->withErrors(['name' => 'This category already exists.'])->withInput();
        }

        Category::create([
            'tenant_id' => $tenantId,
            'name' => trim($request->name),
            'description' => $request->description,
        ]);

        return redirect()->route('categories.index')->with('success', 'Product category "' . $request->name . '" created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string|max:255',
        ]);

        $oldName = $category->name;
        $newName = trim($request->name);

        $category->update([
            'name' => $newName,
            'description' => $request->description,
        ]);

        if ($oldName !== $newName) {
            Product::where('category', $oldName)->update(['category' => $newName]);
        }

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $categoryName = $category->name;
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category "' . $categoryName . '" deleted.');
    }
}
