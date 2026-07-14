<?php

namespace App\Http\Controllers;

use App\Services\ProjectCsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectCsvExportController extends Controller
{
    /**
     * ログインユーザーが所有する案件をCSVでダウンロードします。
     */
    public function __invoke(
        Request $request,
        ProjectCsvExportService $projectCsvExportService
    ): StreamedResponse {
        /*
         * 一覧画面とCSVで検索結果が一致するように、
         * 案件一覧で使用している条件だけをServiceへ渡す。
         *
         * pageは画面表示専用なのでCSVには渡さない。
         */
        $filters = $request->only([
            'keyword',
            'category_id',
            'tag_id',
            'status',
            'sort',
        ]);

        return $projectCsvExportService->download(
            $request->user(),
            $filters
        );
    }
}