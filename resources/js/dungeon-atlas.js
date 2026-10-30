export function mountDungeonAtlas(dialog) {
    const form = dialog?.querySelector('[data-atlas-tools]');
    const entries = [...(dialog?.querySelectorAll('[data-dungeon-entry]') ?? [])];
    if (!form || !entries.length) return () => {};

    const search = form.querySelector('[data-atlas-search]');
    const difficulty = form.querySelector('[data-atlas-difficulty]');
    const order = form.querySelector('[data-atlas-order]');
    const unexplored = form.querySelector('[data-atlas-unexplored]');
    const count = form.querySelector('[data-atlas-count]');
    const empty = dialog.querySelector('[data-atlas-empty]');
    const list = dialog.querySelector('[data-atlas-list]');
    const collator = new Intl.Collator('pt-BR', { sensitivity: 'base' });
    const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');

    function update() {
        const term = normalize(search.value.trim());
        let visible = 0;
        [...entries].sort((a, b) => {
            const xp = Number(a.dataset.dungeonXp) - Number(b.dataset.dungeonXp);
            return (order.value === 'desc' ? -xp : xp) || collator.compare(a.dataset.dungeonName, b.dataset.dungeonName);
        }).forEach((entry) => {
            entry.hidden = !normalize(entry.dataset.dungeonName).includes(term)
                || (difficulty.value !== '' && entry.dataset.dungeonDifficulty !== difficulty.value)
                || (unexplored.checked && Number(entry.dataset.dungeonAttempts) > 0);
            if (!entry.hidden) visible++;
            list.insertBefore(entry, empty);
        });
        count.textContent = `${visible} de ${entries.length} destinos`;
        empty.hidden = visible > 0;
    }
    const submit = (event) => event.preventDefault();
    const reset = (event) => { event.preventDefault(); form.reset(); update(); };
    const resetButton = form.querySelector('[data-atlas-reset]');
    form.hidden = false;
    search.addEventListener('input', update);
    difficulty.addEventListener('change', update);
    order.addEventListener('change', update);
    unexplored.addEventListener('change', update);
    form.addEventListener('submit', submit);
    resetButton.addEventListener('click', reset);
    update();

    return () => {
        search.removeEventListener('input', update);
        difficulty.removeEventListener('change', update);
        order.removeEventListener('change', update);
        unexplored.removeEventListener('change', update);
        form.removeEventListener('submit', submit);
        resetButton.removeEventListener('click', reset);
    };
}
