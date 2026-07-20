import './bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        toasts: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
    });

    Alpine.store('confirm', {
        show: false,
        title: '',
        message: '',
        type: 'danger',
        confirmLabel: 'Confirm',
        denyLabel: 'Cancel',
        _callback: null,
        open(title, message, type = 'danger', callback = null, confirmLabel = 'Confirm', denyLabel = 'Cancel') {
            this.title = title;
            this.message = message;
            this.type = type;
            this.confirmLabel = confirmLabel;
            this.denyLabel = denyLabel;
            this._callback = callback;
            this.show = true;
        },
        confirm() {
            if (typeof this._callback === 'function') this._callback();
            this.close();
        },
        deny() {
            this.close();
        },
        close() {
            this.show = false;
            this._callback = null;
        },
    });

    Alpine.store('sidebar', {
        open: localStorage.getItem('sidebar-open') !== 'false',
        toggle() {
            this.open = !this.open;
            localStorage.setItem('sidebar-open', this.open);
        },
    });
});
