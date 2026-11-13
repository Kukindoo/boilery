document.addEventListener('livewire:init', () => {
    Livewire.interceptRequest(({ onError }) => {
        onError(({ response, preventDefault }) => {
            if (response.status === 403) {
                preventDefault();
                window.dispatchEvent(new CustomEvent('access-denied'));
            }
        });
    });
});
