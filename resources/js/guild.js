import { mountGuildScene } from './guild-scene';
import { mountMemberRoster } from './member-roster';

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const canvas = document.querySelector('#guild-scene');
const motionButton = document.querySelector('[data-motion-toggle]');
let paused = localStorage.getItem('guild-motion-paused') === 'true';

function setMotionState() {
    document.documentElement.classList.toggle('motion-paused', paused);
    if (canvas) canvas.dataset.paused = String(paused);
    motionButton?.setAttribute('aria-pressed', String(paused));
    const label = motionButton?.querySelector('span');
    if (label) label.textContent = paused ? 'Retomar animações' : 'Pausar animações';
}
setMotionState();
let cleanupScene = canvas ? mountGuildScene(canvas) : () => {};
window.addEventListener('pagehide', () => cleanupScene());
window.addEventListener('pageshow', (event) => {
    if (event.persisted && canvas) cleanupScene = mountGuildScene(canvas);
});
motionButton?.addEventListener('click', () => {
    paused = !paused;
    localStorage.setItem('guild-motion-paused', String(paused));
    setMotionState();
});

document.querySelector('[data-open-guilds]')?.addEventListener('click', () => document.querySelector('.guild-picker')?.showModal());
document.querySelectorAll('[data-close-dialog]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        const rect = dialog.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
    });
});

let selectedMemberId;
const memberDialog = document.querySelector('.member-dialog');
const cleanupRoster = mountMemberRoster(document.querySelector('[data-member-roster]'));
document.querySelectorAll('[data-member-id]').forEach((button) => button.addEventListener('click', () => {
    selectedMemberId = button.dataset.memberId;
    ['name', 'class', 'level', 'quests'].forEach((field) => {
        const target = memberDialog?.querySelector(`[data-detail-${field}]`);
        if (target) target.textContent = button.dataset[`member${field[0].toUpperCase()}${field.slice(1)}`];
    });
    memberDialog?.showModal();
}));
document.querySelector('[data-choose-member]')?.addEventListener('click', () => {
    const select = document.querySelector('#expedition-member');
    if (select) select.value = selectedMemberId;
    memberDialog.close();
    document.querySelector('#expedicoes')?.scrollIntoView({ behavior: paused || reducedMotion.matches ? 'instant' : 'smooth', block: 'center' });
    select?.focus({ preventScroll: true });
});

const loading = document.querySelector('.expedition-loading');
let submitting = false;
document.querySelectorAll('[data-expedition-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (submitting) { event.preventDefault(); return; }
        submitting = true;
        form.querySelector('button[type="submit"]').disabled = true;
        if (loading) {
            loading.querySelector('p').textContent = 'CONSULTANDO O DESTINO...';
            loading.hidden = false;
        }
    });
});
window.addEventListener('pageshow', () => {
    submitting = false;
    if (loading) loading.hidden = true;
    document.querySelectorAll('[data-expedition-form] button').forEach((button) => { button.disabled = false; });
});

const revealedOffer = document.querySelector('[data-new-offer]');
if (revealedOffer) {
    revealedOffer.scrollIntoView({ behavior: paused || reducedMotion.matches ? 'instant' : 'smooth', block: 'center' });
    revealedOffer.focus({ preventScroll: true });
}

const navItems = [...document.querySelectorAll('.nav-item')];
navItems.forEach((item) => item.addEventListener('click', () => {
    navItems.forEach((link) => link.classList.toggle('is-active', link === item));
}));
const activeSections = new Map();
const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => activeSections.set(entry.target.id, entry.isIntersecting));
    const active = [...activeSections].find(([, visible]) => visible)?.[0];
    if (active) navItems.forEach((link) => link.classList.toggle('is-active', link.hash === `#${active}`));
}, { rootMargin: '-10% 0px -55% 0px' });
['quartel', 'membros', 'expedicoes', 'cronicas'].forEach((id) => {
    const section = document.getElementById(id);
    if (section) observer.observe(section);
});

const typewriter = document.querySelector('[data-typewriter]');
let typewriterTimer;
if (typewriter && !paused && !reducedMotion.matches) {
    const text = typewriter.dataset.typewriter;
    typewriter.setAttribute('aria-label', text);
    const visual = document.createElement('span');
    visual.setAttribute('aria-hidden', 'true');
    typewriter.replaceChildren(visual);
    let index = 0;
    const typeNext = () => {
        visual.textContent = text.slice(0, ++index);
        if (index < text.length && !paused && !reducedMotion.matches) typewriterTimer = setTimeout(typeNext, 32);
        else visual.textContent = text;
    };
    typeNext();
    window.addEventListener('pagehide', () => clearTimeout(typewriterTimer), { once: true });
}
if (import.meta.hot) import.meta.hot.dispose(() => { cleanupScene(); cleanupRoster(); observer.disconnect(); clearTimeout(typewriterTimer); });
