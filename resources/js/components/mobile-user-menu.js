const initMobileUserMenu = () => {
    const menus = document.querySelectorAll('.mobile-user-menu');

    if (menus.length === 0) {
        return;
    }

    const closeMenu = (menu, { restoreFocus = false } = {}) => {
        if (!menu.open) {
            return;
        }

        menu.open = false;

        if (restoreFocus) {
            const trigger = menu.querySelector(
                '.mobile-user-menu__trigger'
            );

            trigger?.focus();
        }
    };

    document.addEventListener('pointerdown', (event) => {
        menus.forEach((menu) => {
            if (!menu.open) {
                return;
            }

            if (menu.contains(event.target)) {
                return;
            }

            closeMenu(menu);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        menus.forEach((menu) => {
            closeMenu(menu, {
                restoreFocus: true,
            });
        });
    });
};

initMobileUserMenu();