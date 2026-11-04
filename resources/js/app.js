import './bootstrap';

const helpDialog = document.querySelector('.help-dialog');
document.querySelector('[data-open-help]')?.addEventListener('click', () => helpDialog?.showModal());
document.querySelectorAll('[data-close-help]').forEach((button) => {
    button.addEventListener('click', () => helpDialog?.close());
});
helpDialog?.addEventListener('click', (event) => {
    if (event.target === helpDialog) {
        const { left, right, top, bottom } = helpDialog.getBoundingClientRect();
        if (event.clientX < left || event.clientX > right || event.clientY < top || event.clientY > bottom) {
            helpDialog.close();
        }
    }
});
