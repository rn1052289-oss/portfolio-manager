const actionMenus = document.querySelectorAll(
    '[data-project-action-menu]'
);

if (actionMenus.length > 0) {
    const VIEWPORT_MARGIN = 8;
    const MENU_GAP = 6;

    /**
     * メニューを閉じます。
     */
    const closeMenu = (menu, focusTrigger = false) => {
        const trigger = menu.querySelector(
            '[data-project-action-trigger]'
        );

        const content = menu.querySelector(
            '[data-project-action-content]'
        );

        if (!trigger || !content) {
            return;
        }

        content.hidden = true;
        content.classList.remove('is-positioned');

        content.style.removeProperty('--project-menu-top');
        content.style.removeProperty('--project-menu-left');

        trigger.setAttribute('aria-expanded', 'false');

        if (focusTrigger) {
            trigger.focus();
        }
    };

    /**
     * 指定したメニュー以外を閉じます。
     */
    const closeOtherMenus = (currentMenu = null) => {
        actionMenus.forEach((menu) => {
            if (menu === currentMenu) {
                return;
            }

            closeMenu(menu);
        });
    };

    /**
     * メニューが画面外へはみ出さない位置を計算します。
     */
    const positionMenu = (menu) => {
        const trigger = menu.querySelector(
            '[data-project-action-trigger]'
        );

        const content = menu.querySelector(
            '[data-project-action-content]'
        );

        if (!trigger || !content || content.hidden) {
            return;
        }

        const triggerRect = trigger.getBoundingClientRect();
        const contentRect = content.getBoundingClientRect();

        let top = triggerRect.bottom + MENU_GAP;
        let left = triggerRect.right - contentRect.width;

        /*
         * 画面右端からはみ出す場合。
         */
        if (
            left + contentRect.width
            > window.innerWidth - VIEWPORT_MARGIN
        ) {
            left =
                window.innerWidth
                - contentRect.width
                - VIEWPORT_MARGIN;
        }

        /*
         * 画面左端からはみ出す場合。
         */
        if (left < VIEWPORT_MARGIN) {
            left = VIEWPORT_MARGIN;
        }

        /*
         * 画面下部に収まらない場合は、
         * 3点ボタンの上へ表示します。
         */
        if (
            top + contentRect.height
            > window.innerHeight - VIEWPORT_MARGIN
        ) {
            top =
                triggerRect.top
                - contentRect.height
                - MENU_GAP;
        }

        /*
         * 上へ出しても画面外になる場合。
         */
        if (top < VIEWPORT_MARGIN) {
            top = VIEWPORT_MARGIN;
        }

        content.style.setProperty(
            '--project-menu-top',
            `${Math.round(top)}px`
        );

        content.style.setProperty(
            '--project-menu-left',
            `${Math.round(left)}px`
        );
    };

    /**
     * メニューを開きます。
     */
    const openMenu = (menu) => {
        const trigger = menu.querySelector(
            '[data-project-action-trigger]'
        );

        const content = menu.querySelector(
            '[data-project-action-content]'
        );

        if (!trigger || !content) {
            return;
        }

        closeOtherMenus(menu);

        content.hidden = false;

        /*
         * 一度サイズを取得してからfixed配置へ切り替えることで、
         * tableのoverflowにメニューが切られないようにします。
         */
        content.getBoundingClientRect();

        content.classList.add('is-positioned');

        trigger.setAttribute('aria-expanded', 'true');

        positionMenu(menu);
    };

    /**
     * 各3点ボタンへイベントを設定します。
     */
    actionMenus.forEach((menu) => {
        const trigger = menu.querySelector(
            '[data-project-action-trigger]'
        );

        if (!trigger) {
            return;
        }

        trigger.addEventListener('click', (event) => {
            event.stopPropagation();

            const isOpen =
                trigger.getAttribute('aria-expanded') === 'true';

            if (isOpen) {
                closeMenu(menu);

                return;
            }

            openMenu(menu);
        });
    });

    /**
     * メニュー外をクリックしたら閉じます。
     */
    document.addEventListener('click', (event) => {
        const clickedInsideMenu = event.target.closest(
            '[data-project-action-menu]'
        );

        if (clickedInsideMenu) {
            return;
        }

        closeOtherMenus();
    });

    /**
     * Escキーで現在開いているメニューを閉じます。
     */
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        actionMenus.forEach((menu) => {
            const trigger = menu.querySelector(
                '[data-project-action-trigger]'
            );

            if (
                trigger?.getAttribute('aria-expanded')
                !== 'true'
            ) {
                return;
            }

            closeMenu(menu, true);
        });
    });

    /**
     * ウィンドウサイズ変更時は位置を再計算します。
     */
    window.addEventListener('resize', () => {
        actionMenus.forEach((menu) => {
            const trigger = menu.querySelector(
                '[data-project-action-trigger]'
            );

            if (
                trigger?.getAttribute('aria-expanded')
                !== 'true'
            ) {
                return;
            }

            positionMenu(menu);
        });
    });

    /**
     * ページ・テーブルをスクロールした場合も
     * 開いているメニューをボタン位置へ追従させます。
     */
    document.addEventListener(
        'scroll',
        () => {
            actionMenus.forEach((menu) => {
                const trigger = menu.querySelector(
                    '[data-project-action-trigger]'
                );

                if (
                    trigger?.getAttribute('aria-expanded')
                    !== 'true'
                ) {
                    return;
                }

                positionMenu(menu);
            });
        },
        true
    );
}