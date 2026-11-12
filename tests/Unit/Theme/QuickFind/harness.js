// Banc d'essai du script de saisie rapide : un DOM minimal, sans dépendance, reproduit la structure rendue par le thème.
const chunks = [];
process.stdin.on('data', (chunk) => chunks.push(chunk));
process.stdin.on('end', () => {
    const scenario = JSON.parse(Buffer.concat(chunks).toString());
    const result = { error: null };
    try {
        run(scenario, result);
    } catch (error) {
        result.error = String(error && error.stack ? error.stack : error);
    }
    process.stdout.write(JSON.stringify(result));
});

function parseSimple(selector) {
    const tag = (selector.match(/^[a-z]+/) || [null])[0];
    const attrs = [];
    const re = /\[([a-z-]+)(?:="([^"]*)")?\]/g;
    let m;
    while ((m = re.exec(selector)) !== null) {
        attrs.push([m[1], m[2]]);
    }
    return { tag, attrs };
}

function matches(el, selector) {
    return selector.split(',').some((part) => {
        const { tag, attrs } = parseSimple(part.trim());
        if (tag && el.tagName.toLowerCase() !== tag) {
            return false;
        }
        return attrs.every(([name, value]) => (value === undefined ? name in el.attributes : el.attributes[name] === value));
    });
}

function run(scenario, result) {
    const listeners = [];
    let active = null;

    class El {
        constructor(tag, attributes, children) {
            this.tagName = tag.toUpperCase();
            this.attributes = Object.assign({}, attributes || {});
            this.children = children || [];
            this.parent = null;
            this.textContent = '';
            this.value = '';
            this.children.forEach((child) => { child.parent = this; });
            this.listeners = {};
        }
        setAttribute(name, value) { this.attributes[name] = String(value); }
        getAttribute(name) { return name in this.attributes ? this.attributes[name] : null; }
        removeAttribute(name) { delete this.attributes[name]; }
        hasAttribute(name) { return name in this.attributes; }
        addEventListener(type, handler) { (this.listeners[type] = this.listeners[type] || []).push(handler); }
        focus() { active = this; }
        click() { dispatch(this, { type: 'click' }); }
        closest(selector) {
            for (let el = this; el; el = el.parent) {
                if (el.tagName !== 'DOCUMENT' && matches(el, selector)) {
                    return el;
                }
            }
            return null;
        }
        querySelectorAll(selector) {
            const found = [];
            const walk = (node) => node.children.forEach((child) => {
                if (matches(child, selector)) { found.push(child); }
                walk(child);
            });
            walk(this);
            return found;
        }
        querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
    }

    const document = new El('document', {}, []);
    document.getElementById = (id) => {
        const all = document.querySelectorAll('[id]');
        return all.find((el) => el.attributes.id === id) || null;
    };
    document.readyState = 'complete';

    const rows = scenario.rows;
    const input = new El('input', { 'data-industry-quick-find-input': '', role: 'combobox', 'aria-expanded': 'false' });
    const status = new El('div', { 'data-industry-quick-find-status': '', role: 'status' });
    const options = rows.map((row, i) => new El('li', {
        id: 'opt-' + i,
        role: 'option',
        'data-search': (row.ref + ' ' + row.name).toLowerCase(),
        'data-target': 'link-' + i,
        hidden: '',
    }));
    const list = new El('ul', { 'data-industry-quick-find-list': '', role: 'listbox', hidden: '' }, options);
    const root = new El('div', {
        'data-industry-quick-find': '',
        'data-message-none': 'Aucune référence trouvée',
        'data-message-one': '1 référence trouvée',
        'data-message-many': '%count% références trouvées',
    }, [input, list, status]);

    const trs = rows.map((row, i) => new El('tr', { 'data-industry': 'product-row' }, [
        new El('td', {}, [new El('a', { href: '/p/' + i, id: 'link-' + i })]),
        new El('td', {}, [new El('button', { type: 'button', id: 'add-' + i })]),
    ]));
    const table = new El('table', {}, [new El('tbody', {}, trs)]);
    const productList = new El('div', { 'data-industry': 'product-list' }, [table]);
    document.children.push(productList);
    if (scenario.withField !== false) {
        document.children.push(root);
    }
    root.parent = document;
    productList.parent = document;

    function dispatch(target, event) {
        event.target = target;
        event.defaultPrevented = false;
        event.preventDefault = () => { event.defaultPrevented = true; };
        for (let node = target; node; node = node.parent) {
            ((node.listeners || {})[event.type] || []).forEach((handler) => handler(event));
        }
        return event;
    }

    const window = {};
    new Function('window', 'document', scenario.script)(window, document);

    const byId = (id) => document.getElementById(id);
    (scenario.steps || []).forEach((step) => {
        const target = step.on === 'input' ? input : byId(step.on);
        if (step.type === 'input') {
            input.value = step.value;
            dispatch(input, { type: 'input' });
        } else if (step.type === 'key') {
            result.lastPrevented = dispatch(target, { type: 'keydown', key: step.key }).defaultPrevented;
        } else if (step.type === 'click') {
            result.lastPrevented = dispatch(target, { type: 'click' }).defaultPrevented;
        } else if (step.type === 'focus') {
            target.focus();
        }
    });

    result.visible = options.filter((o) => !('hidden' in o.attributes)).map((o) => o.attributes.id);
    result.selected = options.filter((o) => o.attributes['aria-selected'] === 'true').map((o) => o.attributes.id);
    result.listHidden = 'hidden' in list.attributes;
    result.expanded = input.attributes['aria-expanded'];
    result.activedescendant = input.attributes['aria-activedescendant'] || null;
    result.status = status.textContent;
    result.focus = active ? (active === input ? 'input' : active.attributes.id) : null;
    result.inputValue = input.value;
}
