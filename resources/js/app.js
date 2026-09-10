import './bootstrap';

document.addEventListener('alpine:init', () => {
    // Um único store para os dois botões de tema (desktop e menu mobile)
    // ficarem sempre em sincronia com a classe do <html>.
    Alpine.store('tema', {
        escuro: document.documentElement.classList.contains('dark'),

        alternar() {
            this.escuro = !this.escuro;
            document.documentElement.classList.toggle('dark', this.escuro);
            localStorage.setItem('tema', this.escuro ? 'escuro' : 'claro');
        },
    });
});
