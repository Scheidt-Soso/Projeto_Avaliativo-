import { mountGuildScene } from './guild-scene';

const canvas = document.querySelector('#auth-scene');
const motionButton = document.querySelector('[data-auth-motion]');
let paused = localStorage.getItem('guild-motion-paused') === 'true';

function updateMotion() {
    document.documentElement.classList.toggle('motion-paused', paused);
    if (canvas) canvas.dataset.paused = String(paused);
    motionButton?.setAttribute('aria-pressed', String(paused));
    const label = motionButton?.querySelector('span');
    if (label) label.textContent = paused ? 'Retomar animações' : 'Pausar animações';
}
updateMotion();
let cleanupScene = canvas ? mountGuildScene(canvas) : () => {};
motionButton?.addEventListener('click', () => {
    paused = !paused;
    localStorage.setItem('guild-motion-paused', String(paused));
    updateMotion();
});

document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    const input = document.getElementById(button.dataset.togglePassword);
    if (!(input instanceof HTMLInputElement)) return;
    const showLabel = button.getAttribute('aria-label');
    button.hidden = false;
    button.addEventListener('click', () => {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.textContent = visible ? 'OCULTAR' : 'VER';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? showLabel.replace('Mostrar', 'Ocultar') : showLabel);
    });
    window.addEventListener('pageshow', () => {
        input.type = 'password';
        button.textContent = 'VER';
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-label', showLabel);
    });
});

document.querySelectorAll('[data-auth-form]').forEach((form) => {
    const button = form.querySelector('[type="submit"]');
    const label = button.querySelector('span');
    const initialLabel = label.textContent;
    let submitting = false;
    form.addEventListener('submit', (event) => {
        if (submitting) { event.preventDefault(); return; }
        submitting = true;
        button.disabled = true;
        label.textContent = button.dataset.submittingLabel;
    });
    window.addEventListener('pageshow', () => {
        submitting = false;
        button.disabled = false;
        label.textContent = initialLabel;
    });
});

window.addEventListener('pagehide', () => cleanupScene());
window.addEventListener('pageshow', (event) => {
    if (event.persisted && canvas) cleanupScene = mountGuildScene(canvas);
});
if (import.meta.hot) import.meta.hot.dispose(() => cleanupScene());
