<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderTasksRequest;
use App\Models\Project;
use App\Services\TaskOrderService;
use Illuminate\Http\JsonResponse;

class TaskOrderController extends Controller
{
    /**
     * 指定案件に紐づくタスクの並び順を更新する。
     */
    public function __invoke(
        ReorderTasksRequest $request,
        Project $project,
        TaskOrderService $taskOrderService
    ): JsonResponse {
        $taskIds = $request->validated('task_ids');

        $taskOrderService->reorder(
            $project,
            $request->user(),
            $taskIds,
        );

        return response()->json([
            'message' => 'タスクの並び順を保存しました。',
            'task_ids' => $taskIds,
        ]);
    }
}