function disableRestrictedSelectors() {
    const weightRestrictionHints = window.data.weightRestrictionHints || [];

    if (weightRestrictionHints.length === 0) {
        return;
    }

    for (const gatewayId of Object.keys(weightRestrictionHints)) {
        for (const input of window.document.querySelectorAll(`input[value="${gatewayId}`)) {
            input.checked = false;
            input.removeAttribute('checked');
            input.setAttribute('disabled', 'disabled');
        }
    }
}

disableRestrictedSelectors();
