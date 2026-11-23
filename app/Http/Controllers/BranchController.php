<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $branches = Branch::where('tenant_id', $tenantId)->get();

        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $tenantId = auth()->user()->tenant_id;

        Branch::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'code' => strtoupper($request->code ?? substr($request->name, 0, 4)),
            'address' => $request->address,
            'phone' => $request->phone,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Branch created successfully.');
    }

    public function update(Request $request, Branch $branch)
    {
        if ($branch->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $branch->update([
            'name' => $request->name,
            'code' => strtoupper($request->code ?? $branch->code),
            'address' => $request->address,
            'phone' => $request->phone,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        return redirect()->back()->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // Deactivate or delete branch
        $branch->update(['is_active' => !$branch->is_active]);

        $status = $branch->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Branch {$status} successfully.");
    }

    public function switchBranch(Request $request)
    {
        $branchId = $request->input('branch_id', 'all');

        if ($branchId !== 'all') {
            $branch = Branch::where('tenant_id', auth()->user()->tenant_id)
                ->where('id', $branchId)
                ->first();

            if (!$branch) {
                return redirect()->back()->with('error', 'Invalid branch selected.');
            }
            session(['active_branch_id' => $branch->id]);
        } else {
            session(['active_branch_id' => 'all']);
        }

        return redirect()->back()->with('success', 'Branch context updated.');
    }
}
