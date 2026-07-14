<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerCsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerCsvExportController extends Controller
{
    /**
     * ログインユーザーが所有する顧客一覧を
     * CSV形式でダウンロードします。
     */
    public function __invoke(
        Request $request,
        CustomerCsvExportService $customerCsvExportService
    ): StreamedResponse {
        /*
         * 顧客一覧を閲覧できるユーザーか確認します。
         *
         * authミドルウェアだけでなくPolicyも通すことで、
         * 顧客一覧画面と同じ認可ルールを適用します。
         */
        $this->authorize(
            'viewAny',
            Customer::class
        );

        /*
         * ControllerではCSVを生成しません。
         *
         * ログインユーザーをServiceへ渡し、
         * 生成されたStreamedResponseをそのまま返します。
         */
        return $customerCsvExportService->download(
            $request->user()
        );
    }
}