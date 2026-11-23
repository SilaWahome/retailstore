<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $users = User::with('branch')->where('tenant_id', $tenantId)->orderBy('name')->get();
        $branches = Branch::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $roles = [
            'admin' => 'Admin (Full Rights)',
            'manager' => 'Manager (Inventory & Sales)',
            'accountant' => 'Accountant (Financial Reports & Expenses)',
            'cashier' => 'Cashier / Server (POS & Tables)',
            'kitchen' => 'Kitchen Staff (KDS & KOTs)',
            'receptionist' => 'Receptionist (Hotel Rooms & Folios)',
        ];

        return view('users.index', compact('users', 'roles', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['admin', 'manager', 'accountant', 'cashier', 'kitchen', 'receptionist'])],
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'branch_id' => $request->branch_id ?: null,
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        return redirect()->route('users.index')->with('success', 'Staff member created successfully!');
    }

    public function update(Request $request, User $user)
    {
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:191',
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'manager', 'accountant', 'cashier', 'kitchen', 'receptionist'])],
            'branch_id' => 'nullable|exists:branches,id',
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'branch_id' => $request->branch_id ?: null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Staff member updated successfully!');
    }

    public function destroy(User $user)
    {
        if ($user->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own user account.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Staff member removed successfully!');
    }
}
