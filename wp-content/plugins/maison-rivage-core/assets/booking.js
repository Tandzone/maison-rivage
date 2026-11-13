/* Keep native date controls and keyboard entry; make the entire field open its picker. */
document.addEventListener('click', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement)
        || input.type !== 'date'
        || !input.closest('.rivage-search-form')
        || typeof input.showPicker !== 'function'
        || input.disabled || input.readOnly) {
        return;
    }
    try {
        input.showPicker();
    } catch {
        // Unsupported contexts retain the native calendar icon and keyboard entry.
    }
});
