import Sortable from 'sortablejs';

const taskList = document.querySelector('[data-task-list]');

if (taskList) {
    const reorderUrl = taskList.dataset.reorderUrl;

    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content;

    const orderStatus = document.querySelector(
        '[data-task-order-status]'
    );

    let statusTimer = null;

    /**
     * 現在のDOM順からTask ID配列を取得する。
     *
     * @returns {number[]}
     */
    const getTaskIds = () => {
        return [
            ...taskList.querySelectorAll('[data-task-id]'),
        ].map((taskRow) => Number(taskRow.dataset.taskId));
    };

    /**
     * 指定されたTask ID順へDOMを戻す。
     *
     * @param {number[]} taskIds
     */
    const restoreTaskOrder = (taskIds) => {
        const taskRowsById = new Map(
            [...taskList.querySelectorAll('[data-task-id]')]
                .map((taskRow) => [
                    Number(taskRow.dataset.taskId),
                    taskRow,
                ])
        );

        taskIds.forEach((taskId) => {
            const taskRow = taskRowsById.get(taskId);

            if (taskRow) {
                taskList.appendChild(taskRow);
            }
        });
    };

    /**
     * 並び替え保存状態を画面へ表示する。
     *
     * @param {'saving'|'success'|'error'} type
     * @param {string} message
     */
    const showOrderStatus = (type, message) => {
        if (!orderStatus) {
            return;
        }

        if (statusTimer) {
            window.clearTimeout(statusTimer);
            statusTimer = null;
        }

        orderStatus.classList.remove(
            'task-order-status--saving',
            'task-order-status--success',
            'task-order-status--error'
        );

        orderStatus.classList.add(
            `task-order-status--${type}`
        );

        orderStatus.textContent = message;
        orderStatus.hidden = false;

        if (type === 'success') {
            statusTimer = window.setTimeout(() => {
                orderStatus.hidden = true;
            }, 2500);
        }
    };

    if (!reorderUrl || !csrfToken) {
        console.error(
            'タスク並び替えの初期化に必要なURLまたはCSRFトークンが見つかりません。'
        );
    } else {
        let previousTaskIds = [];

        const sortable = Sortable.create(taskList, {
            animation: 180,
            handle: '.task-drag-handle',

            onStart() {
                previousTaskIds = getTaskIds();
            },

            async onEnd(event) {
                if (event.oldIndex === event.newIndex) {
                    previousTaskIds = [];

                    return;
                }

                const taskIds = getTaskIds();

                sortable.option('disabled', true);

                showOrderStatus(
                    'saving',
                    '保存中...'
                );

                try {
                    const response = await fetch(reorderUrl, {
                        method: 'PATCH',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            task_ids: taskIds,
                        }),
                    });

                    const responseBody = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            responseBody.message
                                ?? 'タスクの並び順を保存できませんでした。'
                        );
                    }

                    showOrderStatus(
                        'success',
                        '保存しました'
                    );

                    console.log(
                        'タスクの並び順を保存しました。',
                        responseBody
                    );
                } catch (error) {
                    restoreTaskOrder(previousTaskIds);

                    showOrderStatus(
                        'error',
                        '保存に失敗しました'
                    );

                    console.error(
                        'タスク並び替え保存エラー:',
                        error
                    );
                } finally {
                    previousTaskIds = [];

                    sortable.option('disabled', false);
                }
            },
        });
    }
}