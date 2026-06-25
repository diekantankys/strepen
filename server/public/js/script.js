window.initializeStrepenNavbar = () => {
    const navbarBurger = document.querySelector('.navbar-burger');
    const navbarMenu = document.querySelector('.navbar-menu');
    if (navbarBurger == null || navbarMenu == null || navbarBurger.dataset.initialized === 'true') {
        return;
    }

    navbarBurger.dataset.initialized = 'true';
    navbarBurger.addEventListener('click', event => {
        event.preventDefault();
        navbarBurger.classList.toggle('is-active');
        navbarMenu.classList.toggle('is-active');
    });
};

window.initializeStrepenNavbar();

if (window.strepenNavbarNavigateListenerAdded !== true) {
    window.strepenNavbarNavigateListenerAdded = true;
    document.addEventListener('livewire:navigated', window.initializeStrepenNavbar);
}

window.strepenChooserComponent = input => {
    const componentElement = input.closest('[wire\\:id]');

    if (componentElement == null || window.Livewire == null) {
        return null;
    }

    return window.Livewire.find(componentElement.getAttribute('wire:id'));
};

window.strepenChooserOptions = input => {
    const dropdown = document.getElementById(input.dataset.chooserDropdown);

    if (dropdown == null) {
        return [];
    }

    return Array.from(dropdown.querySelectorAll('[data-chooser-option]'));
};

window.strepenChooserSelectedIndex = input => Number.parseInt(input.dataset.chooserSelectedIndex ?? '-1', 10);

window.strepenChooserSetSelectedIndex = (input, index) => {
    const options = window.strepenChooserOptions(input);

    options.forEach(option => option.classList.remove('is-active'));
    input.dataset.chooserSelectedIndex = index.toString();

    if (index >= 0 && options[index] != null) {
        options[index].classList.add('is-active');
    }
};

window.strepenChooserSelect = (input, option) => {
    const component = window.strepenChooserComponent(input);

    if (component == null) {
        return;
    }

    if (option != null) {
        const value = option.dataset.chooserValue;

        if (input.dataset.chooserType === 'user') {
            component.call('selectUser', value);
        } else if (input.dataset.chooserType === 'product') {
            component.call('selectProduct', value);
        } else {
            component.call('addProduct', value);
        }

        return;
    }

    if (input.dataset.chooserType === 'user') {
        component.call('selectFirstUser');
    } else if (input.dataset.chooserType === 'product') {
        component.call('selectFirstProduct');
    } else {
        component.call('addFirstProduct');
    }
};

if (window.strepenChooserListenersAdded !== true) {
    window.strepenChooserListenersAdded = true;

    document.addEventListener('keydown', event => {
        const input = event.target.closest('[data-chooser-input]');

        if (input == null) {
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            event.stopImmediatePropagation();
        }

        const options = window.strepenChooserOptions(input);

        if (event.key === 'Enter' || event.key === 'Tab') {
            event.preventDefault();
            event.stopImmediatePropagation();
            const selectedIndex = window.strepenChooserSelectedIndex(input);

            window.strepenChooserSelect(input, selectedIndex >= 0 ? options[selectedIndex] : null);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            event.stopImmediatePropagation();
            const selectedIndex = window.strepenChooserSelectedIndex(input);
            const nextIndex = selectedIndex > -1 ? selectedIndex - 1 : options.length - 1;

            window.strepenChooserSetSelectedIndex(input, nextIndex);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            event.stopImmediatePropagation();
            const selectedIndex = window.strepenChooserSelectedIndex(input);
            const nextIndex = selectedIndex < options.length - 1 ? selectedIndex + 1 : -1;

            window.strepenChooserSetSelectedIndex(input, nextIndex);
        } else {
            window.strepenChooserSetSelectedIndex(input, -1);
        }
    }, true);

    document.addEventListener('focusout', event => {
        const input = event.target.closest('[data-chooser-input]');

        if (input == null) {
            return;
        }

        setTimeout(() => {
            const component = window.strepenChooserComponent(input);

            if (component != null) {
                component.set('isOpen', false);
            }

            window.strepenChooserSetSelectedIndex(input, -1);
        }, 100);
    });
}
