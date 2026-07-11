<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerBusinessCardService;
use Illuminate\Support\Facades\DB;
use Throwable;

class CustomerController extends Controller
{
    /**
     * ログインユーザーが所有する顧客一覧を表示します。
     */
    public function index()
    {
        $this->authorize('viewAny', Customer::class);

        $customers = auth()->user()
            ->customers()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view(
            'customers.index',
            compact('customers')
        );
    }

    /**
     * 顧客登録画面を表示します。
     */
    public function create()
    {
        $this->authorize('create', Customer::class);

        return view('customers.create');
    }

    /**
     * 顧客と名刺画像を登録します。
     */
    public function store(
        StoreCustomerRequest $request,
        CustomerBusinessCardService $businessCardService
    ) {
        $validated = $request->validated();

        /*
         * UploadedFileはcustomersテーブルへ直接保存しないため、
         * DB登録用データから取り除きます。
         */
        unset(
            $validated['business_card_front'],
            $validated['business_card_back']
        );

        /*
         * 今回Storageへ保存した新画像パスです。
         *
         * DB登録に失敗した場合は、
         * この配列内の画像をcleanupします。
         */
        $storedPaths = [];

        try {
            if ($request->hasFile('business_card_front')) {
                /** @var \Illuminate\Http\UploadedFile $frontImage */
                $frontImage = $request->file('business_card_front');

                $frontPath = $businessCardService
                    ->store($frontImage);

                $storedPaths[] = $frontPath;

                $validated['business_card_front_path'] = $frontPath;
            }

            if ($request->hasFile('business_card_back')) {
                /** @var \Illuminate\Http\UploadedFile $backImage */
                $backImage = $request->file('business_card_back');

                $backPath = $businessCardService
                    ->store($backImage);

                $storedPaths[] = $backPath;

                $validated['business_card_back_path'] = $backPath;
            }

            DB::transaction(function () use ($validated) {
                auth()->user()
                    ->customers()
                    ->create($validated);
            });
        } catch (Throwable $exception) {
            /*
             * DBトランザクションをrollbackしても、
             * Storage上の画像は自動削除されません。
             *
             * 今回新しく保存した画像だけを削除します。
             */
            $businessCardService->deleteMany($storedPaths);

            throw $exception;
        }

        return redirect()
            ->route('customers.index')
            ->with('success', '顧客を登録しました。');
    }
}