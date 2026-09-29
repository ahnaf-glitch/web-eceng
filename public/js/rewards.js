const rewardSelect = document.getElementById('reward');
const providerField = document.getElementById('wallet-provider-field');
const providerSelect = document.getElementById('provider');

if (rewardSelect && providerField && providerSelect) {
    const syncProviderField = () => {
        const isEwallet = rewardSelect.value === 'e-wallet';

        providerField.hidden = !isEwallet;
        providerSelect.disabled = !isEwallet;
        providerSelect.required = isEwallet;

        if (!isEwallet) {
            providerSelect.value = '';
        }
    };

    rewardSelect.addEventListener('change', syncProviderField);
    syncProviderField();
}
