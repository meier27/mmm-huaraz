import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Windows: si la terminal arranca en "c:\..." (minúscula), Vite toma esa raíz
// pero pnpm resuelve los paquetes como "C:\..."; Vite cree entonces que las
// fuentes están fuera del proyecto y no las copia. Se normaliza la unidad.
const root = process.cwd().replace(/^[a-z]:/, (drive) => drive.toUpperCase());
process.chdir(root);

export default defineConfig({
    root,
    // URLs relativas dentro del CSS (fuentes): funcionan también si el sitio
    // está en un subdirectorio (XAMPP). @vite usa el manifiesto, no esta base.
    base: './',
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/reveal.js', 'resources/js/games.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
