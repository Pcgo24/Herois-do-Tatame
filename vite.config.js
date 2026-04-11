import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const codespacesHost = env.VITE_CODESPACES_HOST;

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            cors: true,
            ...(codespacesHost ? {
                origin: `https://${codespacesHost}`,
                hmr: {
                    host: codespacesHost,
                    protocol: 'wss',
                    clientPort: 443
                }
            } : {})
        },
    };
});
