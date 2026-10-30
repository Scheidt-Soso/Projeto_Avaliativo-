export function mountMemberRoster(root) {
    const form = root?.querySelector('[data-roster-tools]');
    const rows = [...(root?.querySelectorAll('[data-member-row]') ?? [])];
    if (!form || !rows.length) return () => {};

    const search = form.querySelector('[data-roster-search]');
    const classes = form.querySelector('select[data-roster-class]');
    const order = form.querySelector('[data-roster-order]');
    const count = form.querySelector('[data-roster-count]');
    const empty = root.querySelector('[data-roster-empty]');
    const body = root.querySelector('#member-list');
    const collator = new Intl.Collator('pt-BR', { sensitivity: 'base', numeric: true });
    const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');

    function update() {
        const term = normalize(search.value.trim());
        const sorted = [...rows].sort((a, b) => {
            const difference = order.value === 'nivel'
                ? Number(b.dataset.rosterLevel) - Number(a.dataset.rosterLevel)
                : order.value === 'quests'
                    ? Number(b.dataset.rosterQuests) - Number(a.dataset.rosterQuests)
                    : 0;
            return difference || collator.compare(a.dataset.rosterName, b.dataset.rosterName);
        });
        let visible = 0;
        sorted.forEach((row) => {
            row.hidden = !normalize(row.dataset.rosterName).includes(term) || (classes.value !== '' && row.dataset.rosterClass !== classes.value);
            if (!row.hidden) visible++;
            body.insertBefore(row, empty);
        });
        count.textContent = `${visible} de ${rows.length} membros`;
        empty.hidden = visible > 0;
    }

    const submit = (event) => event.preventDefault();
    const reset = () => { form.reset(); update(); };
    const resetButton = form.querySelector('[data-roster-reset]');
    // O reset é tratado diretamente para filtrar depois de restaurar os campos.
    const onResetClick = (event) => { event.preventDefault(); reset(); };
    form.hidden = false;
    search.addEventListener('input', update);
    classes.addEventListener('change', update);
    order.addEventListener('change', update);
    form.addEventListener('submit', submit);
    resetButton.addEventListener('click', onResetClick);
    update();

    return () => {
        search.removeEventListener('input', update);
        classes.removeEventListener('change', update);
        order.removeEventListener('change', update);
        form.removeEventListener('submit', submit);
        resetButton.removeEventListener('click', onResetClick);
    };
}
