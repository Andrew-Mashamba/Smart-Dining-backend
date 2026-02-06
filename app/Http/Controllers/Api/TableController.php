<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTableStatusRequest;
use App\Http\Resources\TableResource;
use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    /**
     * Get all tables
     */
    public function index(Request $request)
    {
        $query = Table::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('location')) {
            $query->where('location', $request->location);
        }

        $tables = $query->orderBy('name')->get();

        return response()->json([
            'tables' => TableResource::collection($tables),
            'total' => $tables->count(),
        ]);
    }

    /**
     * Get a specific table
     */
    public function show($id)
    {
        $table = Table::with(['orders' => function ($query) {
            $query->whereNotIn('status', ['completed', 'cancelled'])
                ->with(['items.menuItem', 'guest']);
        }])->findOrFail($id);

        return response()->json(new TableResource($table));
    }

    /**
     * Update table status
     */
    public function updateStatus(UpdateTableStatusRequest $request, $id)
    {
        $validated = $request->validated();

        $table = Table::findOrFail($id);

        $table->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Table status updated successfully',
            'table' => new TableResource($table),
        ]);
    }
}
