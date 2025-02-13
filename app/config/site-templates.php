<?php

/**
 * List of Appwrite Sites templates
 */

// TODO: @Meldiron Angular

const TEMPLATE_FRAMEWORKS = [
    'SVELTEKIT' => [
        'key' => 'sveltekit',
        'name' => 'SvelteKit',
        'installCommand' => 'npm install',
        'buildCommand' => 'npm run build',
        'outputDirectory' => './build',
        'buildRuntime' => 'ssr-22',
        'adapter' => 'ssr',
        'fallbackFile' => null,
    ],
    'NEXTJS' => [
        'key' => 'nextjs',
        'name' => 'Next.js',
        'installCommand' => 'npm install',
        'buildCommand' => 'npm run build',
        'outputDirectory' => './.next',
        'buildRuntime' => 'ssr-22',
        'adapter' => 'ssr',
        'fallbackFile' => null,
    ],
    'NUXT' => [
        'key' => 'nuxt',
        'name' => 'Nuxt',
        'installCommand' => 'npm install',
        'buildCommand' => 'npm run build',
        'outputDirectory' => './.output',
        'buildRuntime' => 'ssr-22',
        'adapter' => 'ssr',
        'fallbackFile' => null,
    ],
    'REMIX' => [
        'key' => 'remix',
        'name' => 'Remix',
        'installCommand' => 'npm install',
        'buildCommand' => 'npm run build',
        'outputDirectory' => './build',
        'buildRuntime' => 'ssr-22',
        'adapter' => 'ssr',
        'fallbackFile' => null,
    ],
    'ASTRO' => [
        'key' => 'astro',
        'name' => 'Astro',
        'installCommand' => 'npm install',
        'buildCommand' => 'npm run build',
        'outputDirectory' => './dist',
        'buildRuntime' => 'ssr-22',
        'adapter' => 'ssr',
        'fallbackFile' => null,
    ],
    'FLUTTER' => [
        'key' => 'flutter',
        'name' => 'Flutter',
        'installCommand' => '',
        'buildCommand' => 'flutter build web',
        'outputDirectory' => './build/web',
        'buildRuntime' => 'flutter-3.24',
        'adapter' => 'static',
        'fallbackFile' => null,
    ],
];

function getFramework(string $frameworkEnum, array $overrides)
{
    $settings = \array_merge(TEMPLATE_FRAMEWORKS[$frameworkEnum], $overrides);
    return $settings;
}

return [
    [
        'key' => 'nextjs-starter',
        'name' => 'Next.js starter website',
        'useCases' => ['starter'],
        'demoUrl' => 'https://nextjs-starter.sites.qa17.appwrite.org/',
        'demoImage' => 'https://qa17.appwrite.org/console/images/sites/templates/nextjs-starter.png',
        'frameworks' => [
            getFramework('NEXTJS', [
                'providerRootDirectory' => './nextjs/starter',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'templates-for-sites',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.2.*',
        'variables' => [],
    ],
    [
        'key' => 'nuxt-starter',
        'name' => 'Nuxt starter website',
        'useCases' => ['starter'],
        'demoUrl' => 'https://nuxt-starter.sites.qa17.appwrite.org/',
        'demoImage' => 'https://qa17.appwrite.org/console/images/sites/templates/nuxt-starter.png',
        'frameworks' => [
            getFramework('NUXT', [
                'providerRootDirectory' => './nuxt/starter',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'templates-for-sites',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.2.*',
        'variables' => [],
    ],
    [
        'key' => 'sveltekit-starter',
        'name' => 'SvelteKit starter website',
        'useCases' => ['starter'],
        'demoUrl' => 'https://sveltekit-starter.sites.qa17.appwrite.org/',
        'demoImage' => 'https://qa17.appwrite.org/console/images/sites/templates/sveltekit-starter.png',
        'frameworks' => [
            getFramework('SVELTEKIT', [
                'providerRootDirectory' => './sveltekit/starter',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'templates-for-sites',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.2.*',
        'variables' => [],
    ],
    [
        'key' => 'sveltekit-with-appwrite',
        'name' => 'SvelteKit with Appwrite',
        'useCases' => ['starter'],
        'demoUrl' => '',
        'demoImage' => '',
        'frameworks' => [
            getFramework('SVELTEKIT', [
                'providerRootDirectory' => './',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'starter-for-svelte',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.1.*',
        'variables' => [
            [
                'name' => 'PUBLIC_APPWRITE_ENDPOINT',
                'description' => 'Endpoint of Appwrite server.',
                'value' => 'https://cloud.appwrite.io/v1',
                'placeholder' => 'https://cloud.appwrite.io/v1',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'PUBLIC_APPWRITE_PROJECT_ID',
                'description' => 'Your Appwrite project ID.',
                'value' => '',
                'placeholder' => '67ab5b6d000db4f5f59d',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'PUBLIC_APPWRITE_PROJECT_NAME',
                'description' => 'Your Appwrite project name.',
                'value' => '',
                'placeholder' => 'My project',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'PUBLIC_APPWRITE_VERSION',
                'description' => 'Version of Appwrite server.',
                'value' => '1.7.0',
                'placeholder' => '1.7.0',
                'required' => true,
                'type' => 'text'
            ],
        ],
    ],
    [
        'key' => 'astro-starter',
        'name' => 'Astro starter website',
        'useCases' => ['starter'],
        'demoUrl' => 'https://astro-starter.sites.qa17.appwrite.org/',
        'demoImage' => 'https://qa17.appwrite.org/console/images/sites/templates/astro-starter.png',
        'frameworks' => [
            getFramework('ASTRO', [
                'providerRootDirectory' => './astro/starter',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'templates-for-sites',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.2.*',
        'variables' => [],
    ],
    [
        'key' => 'remix-starter',
        'name' => 'Remix starter website',
        'useCases' => ['starter'],
        'demoUrl' => 'https://remix-starter.sites.qa17.appwrite.org/',
        'demoImage' => 'https://qa17.appwrite.org/console/images/sites/templates/remix-starter.png',
        'frameworks' => [
            getFramework('REMIX', [
                'providerRootDirectory' => './remix/starter',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'templates-for-sites',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.2.*',
        'variables' => [],
    ],
    [
        'key' => 'flutter-starter',
        'name' => 'Flutter starter website',
        'useCases' => ['starter'],
        'demoUrl' => 'https://flutter-starter.sites.qa17.appwrite.org/',
        'demoImage' => 'https://qa17.appwrite.org/console/images/sites/templates/flutter-starter.png',
        'frameworks' => [
            getFramework('FLUTTER', [
                'providerRootDirectory' => './flutter/starter',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'templates-for-sites',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.2.*',
        'variables' => [],
    ],
];
