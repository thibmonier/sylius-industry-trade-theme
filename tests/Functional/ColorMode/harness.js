// DOM minimal pour exécuter le script du mode sombre (US-226) : lit un scénario JSON sur stdin, écrit le résultat sur stdout.
// Scénario : script, stored (valeur du stockage), systemDark, storageBroken, click (nombre de clics sur le commutateur).
const chunks = [];
process.stdin.on('data', (chunk) => chunks.push(chunk));
process.stdin.on('end', () => {
    const scenario = JSON.parse(Buffer.concat(chunks).toString());
    const result = { mode: null, modeAtScriptEnd: null, pressed: null, hidden: null, storage: null, error: null };

    const attributes = {};
    const root = {
        setAttribute: (name, value) => { attributes[name] = String(value); },
        getAttribute: (name) => (name in attributes ? attributes[name] : null),
        removeAttribute: (name) => { delete attributes[name]; },
    };

    const buttonAttributes = { 'aria-pressed': 'false', hidden: '' };
    const button = {
        setAttribute: (name, value) => { buttonAttributes[name] = String(value); },
        getAttribute: (name) => (name in buttonAttributes ? buttonAttributes[name] : null),
        removeAttribute: (name) => { delete buttonAttributes[name]; },
        hasAttribute: (name) => name in buttonAttributes,
    };
    button.closest = (selector) => (selector.includes('data-industry-color-mode-toggle') ? button : null);

    const listeners = {};
    const document = {
        documentElement: root,
        readyState: 'loading',
        addEventListener: (type, handler) => { (listeners[type] = listeners[type] || []).push(handler); },
        querySelectorAll: (selector) => (selector.includes('data-industry-color-mode-toggle') ? [button] : []),
    };

    const store = {};
    if (scenario.stored !== undefined) {
        store['industry-trade:color-mode'] = scenario.stored;
    }
    const window = {
        matchMedia: () => ({ matches: Boolean(scenario.systemDark) }),
        addEventListener: () => {},
    };
    Object.defineProperty(window, 'localStorage', {
        get() {
            if (scenario.storageBroken) {
                throw new Error('SecurityError: storage is not available');
            }

            return {
                getItem: (key) => (key in store ? store[key] : null),
                setItem: (key, value) => { store[key] = String(value); },
            };
        },
    });

    try {
        new Function('window', 'document', scenario.script)(window, document);
        result.modeAtScriptEnd = root.getAttribute('data-industry-mode');
        (listeners.DOMContentLoaded || []).forEach((handler) => handler({}));
        for (let i = 0; i < (scenario.click || 0); i++) {
            (listeners.click || []).forEach((handler) => handler({ target: button }));
        }
    } catch (error) {
        result.error = String(error);
    }

    result.mode = root.getAttribute('data-industry-mode');
    result.pressed = button.getAttribute('aria-pressed');
    result.hidden = button.hasAttribute('hidden');
    result.storage = store['industry-trade:color-mode'] ?? null;
    process.stdout.write(JSON.stringify(result));
});
