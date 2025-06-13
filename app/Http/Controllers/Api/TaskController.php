<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks
     */
    public function index(Request $request)
    {
        try {
            $query = Task::forUser(Auth::id());

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $query->search($request->search);
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $tasks = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Tasks retrieved successfully',
                'data' => $tasks
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created task
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tasklist' => 'required|string|max:255',
            'description' => 'required|string',
            'deadline' => 'required|date|after_or_equal:today',
            'status' => 'sometimes|in:pending,in-progress,completed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::create([
                'tasklist' => $request->tasklist,
                'description' => $request->description,
                'deadline' => $request->deadline,
                'status' => $request->get('status', 'pending'),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $task->load('user')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified task
     */
    public function show($id)
    {
        try {
            $task = Task::forUser(Auth::id())->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Task retrieved successfully',
                'data' => $task->load('user')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified task
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tasklist' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'deadline' => 'sometimes|required|date|after_or_equal:today',
            'status' => 'sometimes|in:pending,in-progress,completed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::forUser(Auth::id())->findOrFail($id);
            
            $task->update($request->only([
                'tasklist', 'description', 'deadline', 'status'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => $task->fresh()->load('user')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update task status only
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in-progress,completed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $task = Task::forUser(Auth::id())->findOrFail($id);
            $task->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully',
                'data' => $task->fresh()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified task
     */
    public function destroy($id)
    {
        try {
            $task = Task::forUser(Auth::id())->findOrFail($id);
            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task statistics
     */
    public function statistics()
    {
        try {
            $userId = Auth::id();
            
            $stats = [
                'total_tasks' => Task::forUser($userId)->count(),
                'pending' => Task::forUser($userId)->where('status', 'pending')->count(),
                'in_progress' => Task::forUser($userId)->where('status', 'in-progress')->count(),
                'completed' => Task::forUser($userId)->where('status', 'completed')->count(),
                'overdue' => Task::forUser($userId)
                    ->where('deadline', '<', now())
                    ->where('status', '!=', 'completed')
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}