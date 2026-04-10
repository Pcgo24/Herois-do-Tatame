import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Detecta automaticamente a URL do GitHub Codespace
const host = process.env.CODESPACE_NAME
    ? `${process.env.CODESPACE_NAME}-5173.app.github.dev`
    : 'localhost';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0', // Permite conexões externas
        port: 5173,
        hmr: {
            host: host,
            clientPort: 443,
            protocol: 'wss', // Usa websocket seguro na nuvem
        },
    },
});
