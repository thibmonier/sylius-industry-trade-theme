// DOM minimal pour exécuter le script du mode d'affichage (US-226, US-278) : lit un scénario JSON sur stdin, écrit le résultat sur stdout.
// Scénario : script, stored (valeur du stockage), systemDark, storageBroken, steps (liste d'étapes jouées après le chargement :
// "dark" | "light" | "auto" = clic sur le choix correspondant, "system-dark" | "system-light" = le système change de réglage).
const chunks = [];
process.stdin.on('data', (chunk) => chunks.push(chunk));
process.stdin.on('end', () => {
    const scenario = JSON.parse(Buffer.concat(chunks).toString());
    const result = {
        mode: null, modeAtScriptEnd: null, group: { hidden: null }, pressed: {}, storage: null, storageKeyPresent: false,
        events: [], error: null,
    };

    const attributes = {};
    const root = {
        setAttribute: (name, value) => { attributes[name] = String(value); },
        getAttribute: (name) => (name in attributes ? attributes[name] : null),
        removeAttribute: (name) => { delete attributes[name]; },
    };

    const makeElement = (initial) => {
        const attrs = { ...initial };

        return {
            setAttribute: (name, value) => { attrs[name] = String(value); },
            getAttribute: (name) => (name in attrs ? attrs[name] : null),
            removeAttribute: (name) => { delete attrs[name]; },
            hasAttribute: (name) => name in attrs,
        };
    };

    const group = makeElement({ hidden: '' });
    const buttons = {};
    for (const choice of ['auto', 'light', 'dark']) {
        const button = makeElement({ 'aria-pressed': 'false', 'data-industry-color-mode-choice': choice });
        button.closest = (selector) => (selector.includes('data-industry-color-mode-choice') ? button : null);
        buttons[choice] = button;
    }

    const listeners = {};
    const document = {
        documentElement: root,
        readyState: 'loading',
        addEventListener: (type, handler) => { (listeners[type] = listeners[type] || []).push(handler); },
        dispatchEvent: (event) => { result.events.push({ type: event.type, detail: event.detail }); return true; },
        querySelectorAll: (selector) => {
            if (selector.includes('data-industry-color-mode-choice')) {
                return Object.values(buttons);
            }

            return selector.includes('data-industry-color-mode-toggle') ? [group] : [];
        },
    };

    const store = {};
    if (scenario.stored !== undefined) {
        store['industry-trade:color-mode'] = scenario.stored;
    }

    let systemDark = Boolean(scenario.systemDark);
    const mediaListeners = [];
    const window = {
        matchMedia: () => ({
            get matches() { return systemDark; },
            addEventListener: (type, handler) => { if (type === 'change') { mediaListeners.push(handler); } },
        }),
        CustomEvent: class { constructor(type, init) { this.type = type; this.detail = init && init.detail; } },
    };
    Object.defineProperty(window, 'localStorage', {
        get() {
            if (scenario.storageBroken) {
                throw new Error('SecurityError: storage is not available');
            }

            return {
                getItem: (key) => (key in store ? store[key] : null),
                setItem: (key, value) => { store[key] = String(value); },
                removeItem: (key) => { delete store[key]; },
            };
        },
    });

    try {
        new Function('window', 'document', scenario.script)(window, document);
        result.modeAtScriptEnd = root.getAttribute('data-industry-mode');
        (listeners.DOMContentLoaded || []).forEach((handler) => handler({}));
        for (const step of scenario.steps || []) {
            if (step.startsWith('system-')) {
                systemDark = step === 'system-dark';
                mediaListeners.forEach((handler) => handler({ matches: systemDark }));
                continue;
            }
            (listeners.click || []).forEach((handler) => handler({ target: buttons[step] }));
        }
    } catch (error) {
        result.error = String(error);
    }

    result.mode = root.getAttribute('data-industry-mode');
    for (const [choice, button] of Object.entries(buttons)) {
        result.pressed[choice] = button.getAttribute('aria-pressed');
    }
    result.group.hidden = group.hasAttribute('hidden');
    result.storage = store['industry-trade:color-mode'] ?? null;
    result.storageKeyPresent = 'industry-trade:color-mode' in store;
    process.stdout.write(JSON.stringify(result));
});
