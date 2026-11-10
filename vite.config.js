import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

/**
 * The fonts plugin writes every font twice, as woff2 and as woff, in separate @font-face
 * rules. Browsers then download both and use the woff, the later rule, so the preloaded woff2
 * went to waste and each page loaded about 170 KB of fonts it did not need. Every browser the
 * store supports reads woff2, so only the woff2 rules and files are kept.
 */
function onlyWoff2Fonts() {
    const withoutWoffRules = (css) => css.replace(/@font-face\s*\{[^}]*format\("woff"\)[^}]*\}\s*/g, '');

    return {
        name: 'hade-only-woff2-fonts',
        apply: 'build',
        generateBundle: {
            order: 'post',
            handler(options, bundle) {
                const manifestAsset = bundle['fonts-manifest.json'];

                if (!manifestAsset) {
                    return;
                }

                const manifest = JSON.parse(manifestAsset.source);
                const woffFiles = [];

                for (const family of Object.values(manifest.families)) {
                    for (const variant of Object.values(family.variants)) {
                        woffFiles.push(...variant.files.filter((file) => file.format === 'woff').map((file) => file.file));
                        variant.files = variant.files.filter((file) => file.format !== 'woff');
                    }
                }

                for (const [alias, css] of Object.entries(manifest.style.familyStyles)) {
                    manifest.style.familyStyles[alias] = withoutWoffRules(css);
                }

                manifestAsset.source = JSON.stringify(manifest, null, 2);

                const styleAsset = bundle[manifest.style.file];
                styleAsset.source = withoutWoffRules(String(styleAsset.source));

                for (const file of woffFiles) {
                    delete bundle[file];
                }
            },
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/manager/theme.css'],
            refresh: true,
            fonts: [
                bunny('Montserrat', {
                    weights: [400, 500, 600, 700],
                }),
                bunny('Cormorant Garamond', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
        onlyWoff2Fonts(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
