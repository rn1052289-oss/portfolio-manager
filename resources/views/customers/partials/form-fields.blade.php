{{-- 基本情報 --}}
<section
    class="customer-form-section"
    aria-labelledby="customer-basic-information-title"
>
    <header class="customer-form-section__header">
        <h3
            id="customer-basic-information-title"
            class="customer-form-section__title"
        >
            基本情報
        </h3>

        <p class="customer-form-section__description">
            顧客の会社名や担当者名を入力します。
        </p>
    </header>

    <div class="customer-form-grid">
        <div class="customer-field customer-field--full">
            <label for="name" class="customer-field__label">
                顧客名

                <span class="customer-field__required" aria-label="必須">
                    *
                </span>
            </label>

            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $customer?->name ?? '') }}"
                class="customer-field__control @error('name') is-error @enderror"
                maxlength="255"
                autocomplete="organization"
                placeholder="例：株式会社サンプル"
                required
            >

            @error('name')
                <p class="customer-field__error">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="customer-field customer-field--full">
            <label for="contact_name" class="customer-field__label">
                担当者名
            </label>

            <input
                type="text"
                id="contact_name"
                name="contact_name"
                value="{{ old('contact_name', $customer?->contact_name ?? '') }}"
                class="customer-field__control @error('contact_name') is-error @enderror"
                maxlength="255"
                autocomplete="name"
                placeholder="例：山田 太郎"
            >

            @error('contact_name')
                <p class="customer-field__error">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>
</section>

{{-- 連絡先 --}}
<section
    class="customer-form-section"
    aria-labelledby="customer-contact-information-title"
>
    <header class="customer-form-section__header">
        <h3
            id="customer-contact-information-title"
            class="customer-form-section__title"
        >
            連絡先
        </h3>

        <p class="customer-form-section__description">
            メールアドレスや電話番号を登録します。
        </p>
    </header>

    <div class="customer-form-grid">
        <div class="customer-field">
            <label for="email" class="customer-field__label">
                メールアドレス
            </label>

            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $customer?->email ?? '') }}"
                class="customer-field__control @error('email') is-error @enderror"
                maxlength="255"
                autocomplete="email"
                inputmode="email"
                placeholder="例：customer@example.com"
            >

            @error('email')
                <p class="customer-field__error">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="customer-field">
            <label for="phone" class="customer-field__label">
                電話番号
            </label>

            <input
                type="tel"
                id="phone"
                name="phone"
                value="{{ old('phone', $customer?->phone ?? '') }}"
                class="customer-field__control @error('phone') is-error @enderror"
                maxlength="50"
                autocomplete="tel"
                inputmode="tel"
                placeholder="例：03-1234-5678"
            >

            @error('phone')
                <p class="customer-field__error">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>
</section>

{{-- 住所 --}}
<section
    class="customer-form-section"
    aria-labelledby="customer-address-information-title"
>
    <header class="customer-form-section__header">
        <h3
            id="customer-address-information-title"
            class="customer-form-section__title"
        >
            住所
        </h3>

        <p class="customer-form-section__description">
            請求書や郵送物に使用する住所を登録できます。
        </p>
    </header>

    <div class="customer-form-grid">
        <div class="customer-field">
            <label for="postal_code" class="customer-field__label">
                郵便番号
            </label>

            <input
                type="text"
                id="postal_code"
                name="postal_code"
                value="{{ old('postal_code', $customer?->postal_code ?? '') }}"
                class="customer-field__control @error('postal_code') is-error @enderror"
                maxlength="20"
                autocomplete="postal-code"
                placeholder="例：100-0001"
            >

            @error('postal_code')
                <p class="customer-field__error">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="customer-field customer-field--full">
            <label for="address" class="customer-field__label">
                住所
            </label>

            <input
                type="text"
                id="address"
                name="address"
                value="{{ old('address', $customer?->address ?? '') }}"
                class="customer-field__control @error('address') is-error @enderror"
                maxlength="255"
                autocomplete="street-address"
                placeholder="例：東京都千代田区千代田1-1"
            >

            @error('address')
                <p class="customer-field__error">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>
</section>

{{-- メモ --}}
<section
    class="customer-form-section"
    aria-labelledby="customer-memo-information-title"
>
    <header class="customer-form-section__header">
        <h3
            id="customer-memo-information-title"
            class="customer-form-section__title"
        >
            メモ
        </h3>

        <p class="customer-form-section__description">
            顧客対応で共有しておきたい情報を記録できます。
        </p>
    </header>

    <div class="customer-field customer-field--full">
        <label for="memo" class="customer-field__label">
            顧客メモ
        </label>

        <textarea
            id="memo"
            name="memo"
            class="customer-field__control customer-field__textarea @error('memo') is-error @enderror"
            rows="6"
            maxlength="2000"
            placeholder="例：連絡はメールを希望。毎週水曜日は定休日。"
        >{{ old('memo', $customer?->memo ?? '') }}</textarea>

        <div class="customer-field__footer">
            <p class="customer-field__help">
                連絡方法や注意事項などを入力してください。
            </p>

            <span class="customer-field__limit">
                2000文字以内
            </span>
        </div>

        @error('memo')
            <p class="customer-field__error">
                {{ $message }}
            </p>
        @enderror
    </div>
</section>
