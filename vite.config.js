import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Verifica se está rodando no GitHub Codespaces
const isCodespace = !!process.env.CODESPACE_NAME;

const host = isCodespace
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
        host: '0.0.0.0', // Permite conexões do Docker para o Windows
        port: 5173,
        hmr: {
            host: host,
            // Se for Codespace usa 443/wss, se for local usa 5173/ws
            clientPort: isCodespace ? 443 : 5173,
            protocol: isCodespace ? 'wss' : 'ws',
        },
    },
});