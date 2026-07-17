const paymentForm = document.querySelector('[data-payment-checkout]');

if (paymentForm instanceof HTMLFormElement) {
    initializeStripeCheckout(paymentForm);
}

/**
 * Stripe.jsとPayment Elementを初期化する。
 *
 * このステップでは次の処理だけを行う。
 *
 * 1. Stripe.jsを読み込む
 * 2. Elementsインスタンスを作成する
 * 3. Payment Elementを作成する
 * 4. Payment Elementを画面へ表示する
 * 5. ready後に支払いボタンを有効化する
 *
 * stripe.confirmPayment()は、まだ実行しない。
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

    const paymentElementContainer = form.querySelector('#payment-element');
    const initializationStatus = form.querySelector(
        '#payment-initialization-status',
    );
    const paymentMessage = form.querySelector('#payment-message');
    const submitButton = form.querySelector('#payment-submit');

    /*
     * Payment Elementのreadyイベントが発生するまでは、
     * 支払いボタンを操作できない状態にする。
     */
    setSubmitButtonEnabled(submitButton, false);

    if (
        typeof publishableKey !== 'string'
        || publishableKey === ''
        || typeof clientSecret !== 'string'
        || clientSecret === ''
        || !(paymentElementContainer instanceof HTMLElement)
        || !(submitButton instanceof HTMLButtonElement)
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
         * 決済画面にフォームが存在する場合だけ、
         * @stripe/stripe-jsを遅延読み込みする。
         *
         * 案件一覧や顧客一覧などではStripe.jsを読み込まない。
         */
        const { loadStripe } = await import('@stripe/stripe-js');

        /*
         * Stripe公開鍵を使ってStripe.jsを初期化する。
         *
         * sk_test_やsk_live_から始まる秘密鍵は、
         * JavaScriptへ絶対に渡さない。
         */
        const stripe = await loadStripe(publishableKey);

        if (stripe === null) {
            throw new Error('Stripe.js initialization failed.');
        }

        /*
         * Payment Elementの外観設定。
         *
         * 今回はStripe標準テーマを使用する。
         * 色やフォントの本格的な調整は、
         * 決済処理が安定してから行う。
         */
        const appearance = {
            theme: 'stripe',
        };

        /*
         * Elementsインスタンスを作成し、変数へ保持する。
         *
         * このelementsは、次のステップで
         * stripe.confirmPayment()へ渡すためにも必要になる。
         */
        const elements = stripe.elements({
            clientSecret,
            appearance,
        });

        /*
         * Payment Elementを作成する。
         *
         * layout: 'accordion'により、
         * 利用可能な支払い方法を縦方向の形式で表示する。
         */
        const paymentElement = elements.create('payment', {
            layout: 'accordion',
        });

        let paymentElementReady = false;

        /*
         * StripeのローディングUIが、
         * DOMへ配置されたタイミングで発生する。
         */
        paymentElement.on('loaderstart', () => {
            setInitializationStatus(
                initializationStatus,
                'Stripeの決済フォームを読み込んでいます。',
            );
        });

        /*
         * Payment Elementが完全に表示されたタイミングで発生する。
         *
         * 表示が完了してから支払いボタンを有効化する。
         */
        paymentElement.on('ready', () => {
            paymentElementReady = true;

            setInitializationStatus(
                initializationStatus,
                '支払い情報入力欄の準備ができました。',
            );

            setSubmitButtonEnabled(submitButton, true);
        });

        /*
         * Payment Element内の入力内容や状態が変化したときに発生する。
         *
         * カード番号や有効期限の入力エラーがある場合は、
         * Stripeから渡された安全なエラーメッセージを表示する。
         */
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

        /*
         * Payment Element自体の読み込みに失敗した場合に発生する。
         *
         * エラー詳細やclient_secretは画面・Consoleへ出さず、
         * ユーザー向けの安全な文言だけを表示する。
         */
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
         * 空の#payment-elementへ、
         * Stripeが管理する安全な決済入力欄を表示する。
         *
         * カード入力欄のiframeはStripe側から挿入されるため、
         * 自分でカード番号用inputを作成しない。
         */
        paymentElement.mount(paymentElementContainer);

        form.dataset.stripeInitialized = 'true';

        /*
         * 今回はstripe.confirmPayment()をまだ実装しない。
         *
         * ボタンを押した際にブラウザ標準のフォーム送信が発生して
         * ページが再読み込みされるのを防ぐ。
         */
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!paymentElementReady) {
                showPaymentMessage(
                    paymentMessage,
                    '決済フォームの準備が完了するまでお待ちください。',
                );

                return;
            }

            showPaymentMessage(
                paymentMessage,
                '入力欄の表示確認は完了しています。決済確定処理は次のステップで実装します。',
            );
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

    setSubmitButtonEnabled(submitButton, false);
    showPaymentMessage(paymentMessage, message);
}