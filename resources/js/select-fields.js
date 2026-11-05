let nextId = 0;

export function mountSelectFields(root = document) {
    if (!('showPopover' in HTMLElement.prototype)) return () => {};
    const cleanups = [];
    root.querySelectorAll('select:not([multiple])').forEach((select) => {
        if (select.hidden || select.dataset.choiceMounted || select.size > 1) return;
        select.dataset.choiceMounted = 'true';
        const id = `choice-${++nextId}`;
        const label = select.labels?.[0];
        const originalFor = label?.htmlFor;
        const fieldName = select.getAttribute('aria-label') || label?.querySelector('.sr-only')?.textContent
            || [...(label?.childNodes ?? [])].filter((node) => node.nodeType === Node.TEXT_NODE).map((node) => node.textContent).join(' ').trim() || 'uma opção';
        const outer = select.parentElement;
        const wrapper = document.createElement('div');
        wrapper.className = 'choice-field';
        select.before(wrapper);
        wrapper.append(select);
        select.hidden = true;
        if (outer.matches('.pixel-select')) outer.classList.add('has-choice-field');

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.id = `${id}-button`;
        trigger.className = 'choice-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-controls', `${id}-list`);
        trigger.disabled = select.disabled;
        const value = document.createElement('span');
        value.className = 'choice-value';
        trigger.append(value);
        if (label && originalFor) label.htmlFor = trigger.id;

        const popup = document.createElement('div');
        popup.className = 'choice-popup';
        popup.setAttribute('popover', 'auto');
        const search = document.createElement('input');
        search.type = 'search';
        search.className = 'choice-search';
        search.placeholder = 'Buscar uma opção...';
        search.setAttribute('aria-label', `Buscar ${fieldName}`);
        search.setAttribute('role', 'combobox');
        search.setAttribute('aria-autocomplete', 'list');
        search.setAttribute('aria-expanded', 'true');
        search.setAttribute('aria-controls', `${id}-list`);
        search.autocomplete = 'off';
        const list = document.createElement('ul');
        list.id = `${id}-list`;
        list.className = 'choice-options';
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', fieldName);
        const empty = document.createElement('p');
        empty.className = 'choice-empty';
        empty.setAttribute('role', 'status');
        empty.textContent = 'Nenhuma opção encontrada.';
        empty.hidden = true;
        popup.append(search, list, empty);
        const error = document.createElement('span');
        error.id = `${id}-error`;
        error.className = 'choice-error';
        error.hidden = true;
        error.textContent = 'Escolha uma opção para continuar.';
        wrapper.append(trigger, popup, error);

        const normalize = (text) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');
        let active;
        const entries = [...select.options].map((option, index) => {
            const item = document.createElement('li');
            item.id = `${id}-option-${index}`;
            item.className = 'choice-option';
            item.setAttribute('role', 'option');
            const [title, ...metadata] = option.textContent.split(' · ');
            const heading = document.createElement('strong');
            heading.textContent = title;
            item.append(heading);
            if (metadata.length) {
                const meta = document.createElement('small');
                meta.textContent = metadata.join(' · ');
                item.append(meta);
            }
            const disabled = option.disabled || (option.parentElement.tagName === 'OPTGROUP' && option.parentElement.disabled);
            item.setAttribute('aria-disabled', String(Boolean(disabled)));
            const entry = { option, item, disabled, text: normalize(option.textContent) };
            item.addEventListener('pointermove', () => { if (!disabled) activate(entry); });
            item.addEventListener('click', () => { if (!disabled) choose(entry); });
            list.append(item);
            return entry;
        });

        function visible() { return entries.filter((entry) => !entry.item.hidden && !entry.disabled); }
        function activate(entry) {
            if (active) active.item.classList.remove('is-active');
            active = entry;
            if (active) {
                active.item.classList.add('is-active');
                search.setAttribute('aria-activedescendant', active.item.id);
            } else search.removeAttribute('aria-activedescendant');
        }
        function sync() {
            value.textContent = select.selectedOptions[0]?.textContent || 'Escolha uma opção';
            trigger.setAttribute('aria-label', `${fieldName}: ${value.textContent}`);
            entries.forEach(({ option, item }) => item.setAttribute('aria-selected', String(option.selected)));
            if (select.validity.valid) {
                error.hidden = true;
                trigger.removeAttribute('aria-invalid');
                trigger.removeAttribute('aria-describedby');
            }
        }
        function filter() {
            const term = normalize(search.value.trim());
            entries.forEach(({ item, text }) => { item.hidden = !text.includes(term); });
            empty.hidden = entries.some(({ item }) => !item.hidden);
            activate(visible().find(({ option }) => option.selected) ?? visible()[0]);
        }
        function position() {
            const rect = trigger.getBoundingClientRect();
            const below = window.innerHeight - rect.bottom - 16;
            const above = rect.top - 16;
            const flip = below < 220 && above > below;
            const height = Math.min(360, Math.max(100, flip ? above : below));
            popup.style.setProperty('--choice-height', `${height}px`);
            popup.style.width = `${Math.min(rect.width, window.innerWidth - 24)}px`;
            popup.style.left = `${Math.max(12, Math.min(rect.left, window.innerWidth - popup.offsetWidth - 12))}px`;
            popup.style.top = `${flip ? Math.max(12, rect.top - popup.offsetHeight - 6) : rect.bottom + 6}px`;
        }
        function open() {
            if (trigger.disabled) return;
            search.value = '';
            filter();
            if (!popup.matches(':popover-open')) popup.showPopover();
            trigger.setAttribute('aria-expanded', 'true');
            position();
            search.focus({ preventScroll: true });
            active?.item.scrollIntoView({ block: 'nearest' });
        }
        function close(focus = false) {
            if (popup.matches(':popover-open')) popup.hidePopover();
            trigger.setAttribute('aria-expanded', 'false');
            if (focus) trigger.focus({ preventScroll: true });
        }
        function choose(entry) {
            select.selectedIndex = entries.indexOf(entry);
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close(true);
        }
        const onTrigger = () => popup.matches(':popover-open') ? close() : open();
        const onTriggerKey = (event) => {
            if (['ArrowDown', 'ArrowUp'].includes(event.key)) { event.preventDefault(); open(); }
        };
        const onSearchKey = (event) => {
            if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                event.preventDefault();
                const options = visible();
                if (!options.length) return;
                const delta = event.key === 'ArrowUp' ? -1 : 1;
                const index = event.key === 'Home' ? 0 : event.key === 'End' ? options.length - 1 : (options.indexOf(active) + delta + options.length) % options.length;
                activate(options[index]);
                active.item.scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Enter') { event.preventDefault(); if (active) choose(active); }
            else if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); close(true); }
            else if (event.key === 'Tab') close();
        };
        const onInvalid = (event) => {
            event.preventDefault();
            error.hidden = false;
            trigger.setAttribute('aria-invalid', 'true');
            trigger.setAttribute('aria-describedby', error.id);
            open();
        };
        const onReset = () => requestAnimationFrame(sync);
        const onToggle = () => trigger.setAttribute('aria-expanded', String(popup.matches(':popover-open')));
        const onPosition = () => { if (popup.matches(':popover-open')) position(); };
        const onScroll = (event) => { if (!popup.contains(event.target)) onPosition(); };
        trigger.addEventListener('click', onTrigger);
        trigger.addEventListener('keydown', onTriggerKey);
        search.addEventListener('input', filter);
        search.addEventListener('keydown', onSearchKey);
        popup.addEventListener('toggle', onToggle);
        select.addEventListener('change', sync);
        select.addEventListener('invalid', onInvalid);
        select.form?.addEventListener('reset', onReset);
        window.addEventListener('resize', onPosition);
        document.addEventListener('scroll', onScroll, true);
        sync();
        cleanups.push(() => {
            close();
            select.form?.removeEventListener('reset', onReset);
            select.removeEventListener('change', sync);
            select.removeEventListener('invalid', onInvalid);
            window.removeEventListener('resize', onPosition);
            document.removeEventListener('scroll', onScroll, true);
            if (label && originalFor) label.htmlFor = originalFor;
            wrapper.before(select);
            select.hidden = false;
            delete select.dataset.choiceMounted;
            outer.classList.remove('has-choice-field');
            wrapper.remove();
        });
    });
    return () => cleanups.forEach((cleanup) => cleanup());
}
