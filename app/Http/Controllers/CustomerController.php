<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
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
         * DB登録に失敗した場合はcleanupします。
         */
        $storedPaths = [];

        try {
            if ($request->hasFile('business_card_front')) {
                /** @var \Illuminate\Http\UploadedFile $frontImage */
                $frontImage = $request->file(
                    'business_card_front'
                );

                $frontPath = $businessCardService
                    ->store($frontImage);

                $storedPaths[] = $frontPath;
                $validated['business_card_front_path'] = $frontPath;
            }

            if ($request->hasFile('business_card_back')) {
                /** @var \Illuminate\Http\UploadedFile $backImage */
                $backImage = $request->file(
                    'business_card_back'
                );

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
             * DBがrollbackされてもStorageは自動で戻らないため、
             * 今回保存した新画像だけを削除します。
             */
            $businessCardService->deleteMany($storedPaths);

            throw $exception;
        }

        return redirect()
            ->route('customers.index')
            ->with('success', '顧客を登録しました。');
    }

    /**
     * 顧客詳細を表示します。
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        return view(
            'customers.show',
            compact('customer')
        );
    }

    /**
     * 顧客編集画面を表示します。
     */
    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);

        return view(
            'customers.edit',
            compact('customer')
        );
    }

    /**
     * 顧客情報と名刺画像を更新します。
     */
    public function update(
        UpdateCustomerRequest $request,
        Customer $customer,
        CustomerBusinessCardService $businessCardService
    ) {
        $validated = $request->validated();

        /*
         * UploadedFileはcustomersテーブルへ直接保存しないため、
         * DB更新用データから取り除きます。
         */
        unset(
            $validated['business_card_front'],
            $validated['business_card_back']
        );

        /*
         * 差し替え前の既存画像パスを保持します。
         *
         * DB更新成功後に、差し替えた側の旧画像だけを
         * Storageから削除するために使用します。
         */
        $oldFrontPath = $customer->business_card_front_path;
        $oldBackPath = $customer->business_card_back_path;

        /*
         * 今回新しく保存した画像パスです。
         *
         * DB更新に失敗した場合は、この画像だけを
         * Storageからcleanupします。
         */
        $storedPaths = [];

        /*
         * DB更新成功後に削除する旧画像パスです。
         *
         * 新画像が選択された側だけを追加します。
         */
        $replacedOldPaths = [];

        try {
            if ($request->hasFile('business_card_front')) {
                /** @var \Illuminate\Http\UploadedFile $frontImage */
                $frontImage = $request->file(
                    'business_card_front'
                );

                $newFrontPath = $businessCardService
                    ->store($frontImage);

                $storedPaths[] = $newFrontPath;
                $validated['business_card_front_path'] = $newFrontPath;

                if (
                    is_string($oldFrontPath)
                    && $oldFrontPath !== ''
                ) {
                    $replacedOldPaths[] = $oldFrontPath;
                }
            }

            if ($request->hasFile('business_card_back')) {
                /** @var \Illuminate\Http\UploadedFile $backImage */
                $backImage = $request->file(
                    'business_card_back'
                );

                $newBackPath = $businessCardService
                    ->store($backImage);

                $storedPaths[] = $newBackPath;
                $validated['business_card_back_path'] = $newBackPath;

                if (
                    is_string($oldBackPath)
                    && $oldBackPath !== ''
                ) {
                    $replacedOldPaths[] = $oldBackPath;
                }
            }

            /*
             * Storageへの保存後にDBを更新します。
             *
             * DB処理に失敗した場合はrollbackされ、
             * catch内で新画像をcleanupします。
             */
            DB::transaction(function () use ($customer, $validated) {
                $customer->update($validated);
            });
        } catch (Throwable $exception) {
            /*
             * DB更新に失敗した場合、新しく保存した画像だけを
             * 削除します。
             *
             * 既存画像は削除せず、DBの旧パスも維持されます。
             */
            $businessCardService->deleteMany($storedPaths);

            throw $exception;
        }

        /*
         * DB更新が正常に完了してから、
         * 差し替えた側の旧画像だけを削除します。
         *
         * 削除失敗時はService内でwarningログが記録されます。
         */
        $businessCardService->deleteMany($replacedOldPaths);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', '顧客情報を更新しました。');
    }
}