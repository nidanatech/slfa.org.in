<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        // Get permissions grouped by category (simple convention: 'category.action')
        $permissions = Permission::all()->map(function ($perm) {
            $parts = explode('.', $perm->name);
            return [
                'id' => $perm->id,
                'name' => $perm->name,
                'category' => count($parts) > 1 ? ucfirst($parts[0]) : 'General',
                'label' => count($parts) > 1 ? ucfirst(str_replace('_', ' ', $parts[1])) : $perm->name,
            ];
        })->groupBy('category');

        $tenantId = auth()->user()->tenant_id;
        
        // Roles: System roles (team_id null) + Tenant roles
        // Assuming implementation of teams via tenant_id
        $roles = Role::where('team_id', $tenantId)
            ->orWhereNull('team_id')
            ->with('permissions')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'is_system' => is_null($role->team_id),
                    'permissions' => $role->permissions->pluck('name'),
                    'users_count' => $role->users()->count(),
                ];
            });

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('roles')->where(function ($query) use ($tenantId) {
                    return $query->where('team_id', $tenantId);
                })
            ],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'team_id' => $tenantId,
            'guard_name' => 'web',
        ]);

        // Sync permissions
        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        $tenantId = auth()->user()->tenant_id;

        // Ensure role belongs to tenant (cannot edit system roles)
        if ($role->team_id != $tenantId) {
            abort(403, 'Cannot edit system roles.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id)->where(function ($query) use ($tenantId) {
                    return $query->where('team_id', $tenantId);
                })
            ],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role->update(['name' => $validated['name']]);
        
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $tenantId = auth()->user()->tenant_id;

        if ($role->team_id != $tenantId) {
            abort(403, 'Cannot delete system roles.');
        }
        
        if ($role->users()->count() > 0) {
           return redirect()->back()->with('error', 'Cannot delete role assigned to users.');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }
    
    // Assign role to user (could be in TeamMemberController, but handling here for now)
    public function assign(Request $request)
    {
         $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_name' => 'required|exists:roles,name', // Note: name must be unique per team, Spatie handles retrieval by team
        ]);
        
        $user = \App\Models\User::where('id', $validated['user_id'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
            
        // Use syncRoles to replace existing roles or assignRole to add?
        // Usually single role per user is simpler for UI, but multiple supported.
        // We will do single role for now (sync).
        $user->syncRoles([$validated['role_name']]); // Spatie infers team from user or current state? 
        // We need to be careful. setPermissionsTeamId should be set in middleware?
        // Or passed explicitely? 
        // Spatie's syncRoles uses the model's team if available? 
        // We might need to manually ensure the role is from the correct team.
        
        return redirect()->back()->with('success', 'Role assigned successfully.');
    }
}
