import { fontFamily } from 'tailwindcss/defaultTheme';
import preset from './vendor/filament/support/tailwind.config.preset.js';

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...fontFamily.sans],
            },
        },
    },
    corePlugins: {
        preflight: false, // Disable preflight to avoid conflicts with Filament
    },
};