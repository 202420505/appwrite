<?php

/**
 * List of Appwrite Sites supported frameworks
 */

// TODO: @Meldiron Angular

use Utopia\Config\Config;

$templateRuntimes = Config::getParam('template-runtimes');

function getVersions(array $versions, string $prefix)
{
    return array_map(function ($version) use ($prefix) {
        return $prefix . '-' . $version;
    }, $versions);
}

return [
    'nextjs' => [
        'key' => 'nextjs',
        'name' => 'Next.js',
        'buildRuntime' => 'ssr-22',
        'runtimes' => getVersions($templateRuntimes['NODE']['versions'], 'node'),
        'adapters' => [
            'ssr' => [
                'key' => 'ssr',
                'buildCommand' => 'npm run build',
                'installCommand' => 'npm install',
                'outputDirectory' => './.next',
                'startCommand' => 'sh helpers/next-js/server.sh',
                'bundleCommand' => 'sh /usr/local/server/helpers/next-js/bundle.sh',
                'envCommand' => 'source /usr/local/server/helpers/next-js/env.sh',
            ],
            'static' => [
                'key' => 'static',
                'buildCommand' => 'npm run build',
                'installCommand' => 'npm install',
                'outputDirectory' => './out',
                'startCommand' => 'sh helpers/server.sh',
                'bundleCommand' => '',
                'envCommand' => '',
            ]
        ]
    ],
    'react' => [
        'key' => 'react',
        'name' => 'React',
        'buildRuntime' => 'ssr-22',
        'runtimes' => getVersions($templateRuntimes['NODE']['versions'], 'node'),
        'adapters' => [
            'static' => [
                'key' => 'static',
                'buildCommand' => 'npm run build',
                'installCommand' => 'npm install',
                'outputDirectory' => './dist',
                'startCommand' => 'sh helpers/server.sh',
                'bundleCommand' => '',
                'envCommand' => '',
                'fallbackFile' => 'index.html'
            ]
        ]
    ],
    'nuxt' => [
        'key' => 'nuxt',
        'name' => 'Nuxt',
        'buildRuntime' => 'ssr-22',
        'runtimes' => getVersions($templateRuntimes['NODE']['versions'], 'node'),
        'adapters' => [
            'ssr' => [
                'key' => 'ssr',
                'buildCommand' => 'npm run build',
                'installCommand' => 'npm install',
                'outputDirectory' => './.output',
                'startCommand' => 'sh helpers/nuxt/server.sh',
                'bundleCommand' => 'sh /usr/local/server/helpers/nuxt/bundle.sh',
                'envCommand' => 'source /usr/local/server/helpers/nuxt/env.sh',
            ],
            'static' => [
                'key' => 'static',
                'buildCommand' => 'npm run generate',
                'installCommand' => 'npm install',
                'outputDirectory' => './dist',
                'startCommand' => 'sh helpers/server.sh',
                'bundleCommand' => '',
                'envCommand' => '',
            ]
        ]
    ],
    'sveltekit' => [
        'key' => 'sveltekit',
        'name' => 'SvelteKit',
        'buildRuntime' => 'ssr-22',
        'runtimes' => getVersions($templateRuntimes['NODE']['versions'], 'node'),
        'adapters' => [
            'ssr' => [
                'key' => 'ssr',
                'buildCommand' => 'npm run build',
                'installCommand' => 'npm install',
                'outputDirectory' => './build',
                'startCommand' => 'sh helpers/sveltekit/server.sh',
                'bundleCommand' => 'sh /usr/local/server/helpers/sveltekit/bundle.sh',
                'envCommand' => 'source /usr/local/server/helpers/sveltekit/env.sh',
            ],
            'static' => [
                'key' => 'static',
                'buildCommand' => 'npm run build',
                'installCommand' => 'npm install',
                'outputDirectory' => './build',
                'startCommand' => 'sh helpers/server.sh',
                'bundleCommand' => '',
                'envCommand' => '',
            ]
        ]
    ],
    'astro' => [
        'key' => 'astro',
        'name' => 'Astro',
        'buildRuntime' => 'ssr-22',
        'runtimes' => getVersions($templateRuntimes['NODE']['versions'], 'node'),
        'adapters' => [
            'ssr' => [
                'key' => 'ssr',
                'buildCommand' => 'npm run build',
                'installCommand' => 'npm install',
                'outputDirectory' => './dist',
                'startCommand' => 'sh helpers/astro/server.sh',
                'bundleCommand' => 'sh /usr/local/server/helpers/astro/bundle.sh',
                'envCommand' => 'source /usr/local/server/helpers/astro/env.sh',
            ],
            'static' => [
                'key' => 'static',
                'buildCommand' => 'npm run build',
                'installCommand' => 'npm install',
                'outputDirectory' => './dist',
                'startCommand' => 'sh helpers/server.sh',
                'bundleCommand' => '',
                'envCommand' => '',
            ]
        ]
    ],
    'remix' => [
        'key' => 'remix',
        'name' => 'Remix',
        'buildRuntime' => 'ssr-22',
        'runtimes' => getVersions($templateRuntimes['NODE']['versions'], 'node'),
        'adapters' => [
            'ssr' => [
                'key' => 'ssr',
                'buildCommand' => 'npm run build',
                'installCommand' => 'npm install',
                'outputDirectory' => './build',
                'startCommand' => 'sh helpers/remix/server.sh',
                'bundleCommand' => 'sh /usr/local/server/helpers/remix/bundle.sh',
                'envCommand' => 'source /usr/local/server/helpers/remix/env.sh',
            ],
            'static' => [
                'key' => 'static',
                'buildCommand' => 'npm run build',
                'installCommand' => 'npm install',
                'outputDirectory' => './build/client',
                'startCommand' => 'sh helpers/server.sh',
                'bundleCommand' => '',
                'envCommand' => '',
            ]
        ]
    ],
    'flutter' => [
        'key' => 'flutter',
        'name' => 'Flutter',
        'buildRuntime' => 'flutter-3.24',
        'runtimes' => getVersions($templateRuntimes['FLUTTER']['versions'], 'flutter'),
        'adapters' => [
            'static' => [
                'key' => 'static',
                'buildCommand' => 'flutter build web',
                'installCommand' => '',
                'outputDirectory' => './build/web',
                'startCommand' => 'sh helpers/server.sh',
                'bundleCommand' => '',
                'envCommand' => '',
            ],
        ],
    ],
    'other' => [
        'key' => 'other',
        'name' => 'Other',
        'buildRuntime' => 'ssr-22',
        'runtimes' => getVersions($templateRuntimes['NODE']['versions'], 'node'),
        'adapters' => [
            'static' => [
                'key' => 'static',
                'buildCommand' => '',
                'installCommand' => '',
                'outputDirectory' => './',
                'startCommand' => 'sh helpers/server.sh',
                'bundleCommand' => '',
                'envCommand' => '',
            ],
        ]
    ],
];
