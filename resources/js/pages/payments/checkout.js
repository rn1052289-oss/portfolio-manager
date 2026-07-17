const paymentForm = document.querySelector('[data-payment-checkout]');

if (paymentForm instanceof HTMLFormElement) {
    initializeStripeCheckout(paymentForm);
}

/**
 * Stripe.jsとPayment Elementを初期化し、
 * 入力された支払い情報をStripeへ送信する。
 *
 * @param {HTMLFormElement} form
 * @returns {Promise<void>}
 */
async function initializeStripeCheckout(form) {
    if (form.dataset.stripeInitialized === 'true') {
        return;
    }

    const publishableKey = form.dataset.stripePublishableKey;
    const clientSecret = form.dataset.clientSecret;
    const returnUrl = form.dataset.returnUrl;

    const paymentElementContainer = form.querySelector('#payment-element');
    const initializationStatus = form.querySelector(
        '#payment-initialization-status',
    );
    const paymentMessage = form.querySelector('#payment-message');
    const submitButton = form.querySelector('#payment-submit');
    const submitText = form.querySelector('#payment-submit-text');
    const submitSpinner = form.querySelector('#payment-submit-spinner');

    /*
     * Payment Elementの準備が完了するまでは、
     * 支払いボタンを無効にする。
     */
    setSubmitButtonEnabled(submitButton, false);

    if (
        typeof publishableKey !== 'string'
        || publishableKey === ''
        || typeof clientSecret !== 'string'
        || clientSecret === ''
        || !isSameOriginReturnUrl(returnUrl)
        || !(paymentElementContainer instanceof HTMLElement)
        || !(submitButton instanceof HTMLButtonElement)
        || !(submitText instanceof HTMLElement)
        || !(submitSpinner instanceof HTMLElement)
    ) {
        showInitializationError(
            paymentMessage,
            initializationStatus,
            submitButton,
            '決済画面に必要な情報を取得できませんでした。画面を再読み込みしてください。',
        );

        return;
    }

    try {
        /*
         * 決済画面でのみStripe.jsを遅延読み込みする。
         */
        const { loadStripe } = await import('@stripe/stripe-js');

        /*
         * ブラウザへ渡してよいStripe公開鍵で初期化する。
         *
         * Stripe秘密鍵は絶対にJavaScriptへ渡さない。
         */
        const stripe = await loadStripe(publishableKey);

        if (stripe === null) {
            throw new Error('Stripe.js initialization failed.');
        }

        const appearance = {
            theme: 'stripe',
        };

        /*
         * Payment Elementの作成と決済確定の両方で使う
         * Elementsインスタンスを保持する。
         */
        const elements = stripe.elements({
            clientSecret,
            appearance,
        });

        const paymentElement = elements.create('payment', {
            layout: 'accordion',
        });

        let paymentElementReady = false;

        paymentElement.on('loaderstart', () => {
            setInitializationStatus(
                initializationStatus,
                'Stripeの決済フォームを読み込んでいます。',
            );
        });

        paymentElement.on('ready', () => {
            paymentElementReady = true;

            setInitializationStatus(
                initializationStatus,
                '支払い情報入力欄と決済後の戻り先の準備ができました。',
            );

            setSubmitButtonEnabled(submitButton, true);
        });

        paymentElement.on('change', (event) => {
            if (
                event.error
                && typeof event.error.message === 'string'
                && event.error.message !== ''
            ) {
                showPaymentMessage(
                    paymentMessage,
                    event.error.message,
                );

                return;
            }

            hidePaymentMessage(paymentMessage);
        });

        paymentElement.on('loaderror', () => {
            paymentElementReady = false;

            setSubmitButtonEnabled(submitButton, false);

            showInitializationError(
                paymentMessage,
                initializationStatus,
                submitButton,
                '決済フォームを読み込めませんでした。画面を再読み込みしてください。',
            );
        });

        /*
         * Stripeが管理する決済入力欄を表示する。
         */
        paymentElement.mount(paymentElementContainer);

        form.dataset.stripeInitialized = 'true';

        /*
         * 支払いボタンが押されたときの決済確定処理。
         */
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!paymentElementReady) {
                showPaymentMessage(
                    paymentMessage,
                    '決済フォームの準備が完了するまでお待ちください。',
                );

                return;
            }

            /*
             * ボタン連打やEnterキーの連続入力による
             * 二重送信を防止する。
             */
            if (form.dataset.paymentSubmitting === 'true') {
                return;
            }

            hidePaymentMessage(paymentMessage);

            setPaymentSubmitting(
                form,
                submitButton,
                submitText,
                submitSpinner,
                true,
            );

            let navigationStarted = false;

            try {
                /*
                 * Payment Element内の入力内容を事前検証する。
                 *
                 * 入力不足があればStripeからエラーが返り、
                 * confirmPaymentは実行しない。
                 */
                const { error: submitError } = await elements.submit();

                if (submitError) {
                    showStripeError(
                        paymentMessage,
                        submitError,
                    );

                    return;
                }

                /*
                 * StripeへPaymentIntentの確定を依頼する。
                 *
                 * redirect: 'if_required'により、
                 * 3Dセキュアなどリダイレクトが必要な場合だけ
                 * Stripe側の認証画面へ移動する。
                 */
                const {
                    error,
                    paymentIntent,
                } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        return_url: returnUrl,
                    },
                    redirect: 'if_required',
                });

                if (error) {
                    showStripeError(
                        paymentMessage,
                        error,
                    );

                    return;
                }

                /*
                 * 通常のカード決済などでStripeによる
                 * 自動リダイレクトが発生しなかった場合は、
                 * Laravelの結果URLへ移動する。
                 */
                if (paymentIntent) {
                    navigationStarted = true;

                    window.location.assign(returnUrl);

                    return;
                }

                showPaymentMessage(
                    paymentMessage,
                    '決済結果を確認できませんでした。画面を再読み込みして、決済状況を確認してください。',
                );
            } catch {
                /*
                 * client_secret・カード情報・内部例外は
                 * Consoleや画面へ出さない。
                 */
                showPaymentMessage(
                    paymentMessage,
                    '決済処理中に通信エラーが発生しました。時間を置いて再度お試しください。',
                );
            } finally {
                /*
                 * 画面移動を開始した場合は、
                 * ボタンを有効状態へ戻さない。
                 */
                if (!navigationStarted) {
                    setPaymentSubmitting(
                        form,
                        submitButton,
                        submitText,
                        submitSpinner,
                        false,
                    );
                }
            }
        });
    } catch {
        /*
         * 公開鍵・client_secret・内部例外の詳細は、
         * Consoleや画面へ出さない。
         */
        showInitializationError(
            paymentMessage,
            initializationStatus,
            submitButton,
            '決済画面を初期化できませんでした。時間を置いて再度お試しください。',
        );
    }
}

/**
 * Laravelから渡された戻り先URLが、
 * 現在のアプリと同じオリジンか確認する。
 *
 * @param {unknown} returnUrl
 * @returns {boolean}
 */
function isSameOriginReturnUrl(returnUrl) {
    if (typeof returnUrl !== 'string' || returnUrl === '') {
        return false;
    }

    try {
        const parsedUrl = new URL(
            returnUrl,
            window.location.origin,
        );

        return parsedUrl.origin === window.location.origin;
    } catch {
        return false;
    }
}

/**
 * 決済送信中のフォーム表示を切り替える。
 *
 * @param {HTMLFormElement} form
 * @param {HTMLButtonElement} submitButton
 * @param {HTMLElement} submitText
 * @param {HTMLElement} submitSpinner
 * @param {boolean} isSubmitting
 */
function setPaymentSubmitting(
    form,
    submitButton,
    submitText,
    submitSpinner,
    isSubmitting,
) {
    form.dataset.paymentSubmitting = String(isSubmitting);
    form.setAttribute(
        'aria-busy',
        String(isSubmitting),
    );

    setSubmitButtonEnabled(
        submitButton,
        !isSubmitting,
    );

    submitText.textContent = isSubmitting
        ? '決済処理中...'
        : '支払いを確定する';

    submitSpinner.hidden = !isSubmitting;
}

/**
 * 支払いボタンの有効・無効を切り替える。
 *
 * @param {Element|null} submitButton
 * @param {boolean} enabled
 */
function setSubmitButtonEnabled(submitButton, enabled) {
    if (!(submitButton instanceof HTMLButtonElement)) {
        return;
    }

    submitButton.disabled = !enabled;
    submitButton.setAttribute(
        'aria-disabled',
        String(!enabled),
    );
}

/**
 * 初期化状態の文言を更新する。
 *
 * @param {Element|null} initializationStatus
 * @param {string} message
 */
function setInitializationStatus(initializationStatus, message) {
    if (initializationStatus instanceof HTMLElement) {
        initializationStatus.textContent = message;
    }
}

/**
 * 決済フォームのメッセージを表示する。
 *
 * @param {Element|null} paymentMessage
 * @param {string} message
 */
function showPaymentMessage(paymentMessage, message) {
    if (!(paymentMessage instanceof HTMLElement)) {
        return;
    }

    paymentMessage.textContent = message;
    paymentMessage.hidden = false;
}

/**
 * 決済フォームのメッセージを非表示にする。
 *
 * @param {Element|null} paymentMessage
 */
function hidePaymentMessage(paymentMessage) {
    if (!(paymentMessage instanceof HTMLElement)) {
        return;
    }

    paymentMessage.textContent = '';
    paymentMessage.hidden = true;
}

/**
 * Stripeから返されたエラーを、
 * ユーザー向けメッセージへ変換して表示する。
 *
 * @param {Element|null} paymentMessage
 * @param {unknown} error
 */
function showStripeError(paymentMessage, error) {
    showPaymentMessage(
        paymentMessage,
        stripeErrorMessage(error),
    );
}

/**
 * Stripeのエラーを安全な表示文言へ変換する。
 *
 * カード入力エラーとバリデーションエラーだけは、
 * ユーザー自身で修正できるためStripeの文言を表示する。
 *
 * @param {unknown} error
 * @returns {string}
 */
function stripeErrorMessage(error) {
    if (
        typeof error === 'object'
        && error !== null
        && 'type' in error
        && 'message' in error
        && (
            error.type === 'card_error'
            || error.type === 'validation_error'
        )
        && typeof error.message === 'string'
        && error.message !== ''
    ) {
        return error.message;
    }

    return '決済を完了できませんでした。入力内容を確認して、もう一度お試しください。';
}

/**
 * Stripe.jsまたはPayment Elementの初期化エラーを、
 * 安全な文言で表示する。
 *
 * @param {Element|null} paymentMessage
 * @param {Element|null} initializationStatus
 * @param {Element|null} submitButton
 * @param {string} message
 */
function showInitializationError(
    paymentMessage,
    initializationStatus,
    submitButton,
    message,
) {
    setInitializationStatus(
        initializationStatus,
        'Stripe決済フォームの初期化に失敗しました。',
    );

    setSubmitButtonEnabled(
        submitButton,
        false,
    );

    showPaymentMessage(
        paymentMessage,
        message,
    );
}