<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectTaskCsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectTaskCsvExportController extends Controller
{
    /**
     * 指定案件に紐づくタスクを
     * CSV形式でダウンロードします。
     */
    public function __invoke(
        Request $request,
        Project $project,
        ProjectTaskCsvExportService $projectTaskCsvExportService
    ): StreamedResponse {
        /*
         * 親案件を閲覧できるユーザーだけ、
         * タスクCSVをダウンロードできます。
         *
         * 他人の案件IDをURLへ直接入力しても、
         * Policyによって拒否されます。
         */
        $this->authorize(
            'view',
            $project
        );

        /*
         * ControllerではCSVを生成しません。
         *
         * ログインユーザーと対象案件をServiceへ渡し、
         * StreamedResponseをそのまま返します。
         */
        return $projectTaskCsvExportService->download(
            $request->user(),
            $project
        );
    }
}