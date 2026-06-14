<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $search = $request->input('search');

        $tasks = Task::where('tenant_id', $tenantId)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->with('owner')
            // Special custom ordering:
            // 1. Overdue and incomplete first (due_at < now AND status != completed)
            // 2. Then upcoming incomplete
            // 3. Then completed tasks last
            ->orderByRaw("
                CASE 
                    WHEN status != 'completed' AND due_at < ? THEN 1 
                    WHEN status != 'completed' THEN 2 
                    ELSE 3 
                END ASC
            ", [now()])
            ->orderBy('due_at', 'asc') // Secondary sort by due date
            ->latest() // Tertiary sort by created_at
            ->paginate(50)
            ->withQueryString();

        $users = User::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
        ]);

        Task::create([
            'tenant_id' => auth()->user()->tenant_id,
            'title' => $request->title,
            'description' => $request->description,
            'due_at' => $request->due_at,
            'owner_id' => $request->owner_id ?? auth()->id(),
            'created_by' => auth()->id(),
            'status' => 'open',
        ]);

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Task $task)
    {
        // Simple update for status/completion toggle
        if ($request->has('completed')) {
            $status = $request->completed ? 'completed' : 'open';
            $completedAt = $request->completed ? now() : null;
            
            $task->update([
                'status' => $status,
                'completed_at' => $completedAt,
            ]);
            
            return redirect()->back(); // No flash needed for quick toggle
        }

        // Full update
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
            'status' => 'nullable|string',
        ]);

        $task->update($data);

        return redirect()->back()->with('success', 'Task updated.');
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->back()->with('success', 'Task deleted.');
    }

    public function getChannelTasks(Request $request, \App\Models\Channel $channel)
    {
        // Ensure tenant access
        if ($channel->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $tasks = Task::where('tasks.tenant_id', $channel->tenant_id)
            ->where('tasks.object_type', \App\Models\Contact::class)
            ->join('contacts', 'tasks.object_id', '=', 'contacts.id')
            ->where('contacts.channel_id', $channel->id)
            ->select('tasks.*', 'contacts.name as contact_name')
            ->with(['owner', 'creator'])
            ->latest('tasks.created_at')
            ->paginate(20);

        return response()->json($tasks);
    }
}
