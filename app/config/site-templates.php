<?php

use Utopia\System\System;

/**
 * List of Appwrite Sites templates
 */

$protocol = System::getEnv('_APP_OPTIONS_FORCE_HTTPS') == 'disabled' ? 'http' : 'https';
$hostname = System::getEnv('_APP_DOMAIN');

// TODO: Development override
if (System::getEnv('_APP_ENV') === 'development') {
    $hostname = 'localhost';
}

$url = $protocol . '://' . $hostname;

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
    'OTHER' => [
        'key' => 'other',
        'name' => 'Other',
        'installCommand' => 'npm install',
        'buildCommand' => 'npm run build',
        'buildRuntime' => 'ssr-22',
        'adapter' => 'static',
        'fallbackFile' => 'index.html',
    ],
    'REACT' => [
        'key' => 'react',
        'name' => 'React',
        'installCommand' => 'npm install',
        'buildCommand' => 'npm run build',
        'buildRuntime' => 'ssr-22',
        'adapter' => 'static',
        'outputDirectory' => './dist',
        'fallbackFile' => 'index.html',
    ],
    'VUE' => [
        'key' => 'vue',
        'name' => 'Vue.js',
        'installCommand' => 'npm install',
        'buildCommand' => 'npm run build',
        'buildRuntime' => 'ssr-22',
        'adapter' => 'static',
        'outputDirectory' => './dist',
        'fallbackFile' => 'index.html',
    ],
];

function getFramework(string $frameworkEnum, array $overrides)
{
    $settings = \array_merge(TEMPLATE_FRAMEWORKS[$frameworkEnum], $overrides);
    return $settings;
}

return [
    [
        'key' => 'starter-for-svelte',
        'name' => 'Svelte starter',
        'useCases' => ['starter'],
        'demoImage' => $url . '/console/images/sites/templates/starter-for-svelte.png',
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
                'description' => 'Endpoint of Appwrite server',
                'value' => '{apiEndpoint}',
                'placeholder' => '{apiEndpoint}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'PUBLIC_APPWRITE_PROJECT_ID',
                'description' => 'Your Appwrite project ID',
                'value' => '{projectId}',
                'placeholder' => '{projectId}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'PUBLIC_APPWRITE_PROJECT_NAME',
                'description' => 'Your Appwrite project name',
                'value' => '{projectName}',
                'placeholder' => '{projectName}',
                'required' => true,
                'type' => 'text'
            ],
        ]
    ],
    [
        'key' => 'starter-for-react',
        'name' => 'React starter',
        'useCases' => ['starter'],
        'demoImage' => $url . '/console/images/sites/templates/starter-for-react.png',
        'frameworks' => [
            getFramework('REACT', [
                'providerRootDirectory' => './',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'starter-for-react',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.1.*',
        'variables' => [
            [
                'name' => 'VITE_APPWRITE_ENDPOINT',
                'description' => 'Endpoint of Appwrite server',
                'value' => '{apiEndpoint}',
                'placeholder' => '{apiEndpoint}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'VITE_APPWRITE_PROJECT_ID',
                'description' => 'Your Appwrite project ID',
                'value' => '{projectId}',
                'placeholder' => '{projectId}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'VITE_APPWRITE_PROJECT_NAME',
                'description' => 'Your Appwrite project name',
                'value' => '{projectName}',
                'placeholder' => '{projectName}',
                'required' => true,
                'type' => 'text'
            ],
        ]
    ],
    [
        'key' => 'starter-for-vue',
        'name' => 'Vue starter',
        'useCases' => ['starter'],
        'demoImage' => $url . '/console/images/sites/templates/starter-for-vue.png',
        'frameworks' => [
            getFramework('VUE', [
                'providerRootDirectory' => './',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'starter-for-vue',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.1.*',
        'variables' => [
            [
                'name' => 'VITE_APPWRITE_ENDPOINT',
                'description' => 'Endpoint of Appwrite server',
                'value' => '{apiEndpoint}',
                'placeholder' => '{apiEndpoint}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'VITE_APPWRITE_PROJECT_ID',
                'description' => 'Your Appwrite project ID',
                'value' => '{projectId}',
                'placeholder' => '{projectId}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'VITE_APPWRITE_PROJECT_NAME',
                'description' => 'Your Appwrite project name',
                'value' => '{projectName}',
                'placeholder' => '{projectName}',
                'required' => true,
                'type' => 'text'
            ],
        ]
    ],
    [
        'key' => 'starter-for-react-native',
        'name' => 'React Native starter',
        'useCases' => ['starter'],
        'demoImage' => $url . '/console/images/sites/templates/starter-for-react-native.png',
        'frameworks' => [
            getFramework('REACT', [
                'providerRootDirectory' => './',
                'fallbackFile' => '+not-found.html',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'starter-for-react-native',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.1.*',
        'variables' => [
            [
                'name' => 'EXPO_PUBLIC_APPWRITE_ENDPOINT',
                'description' => 'Endpoint of Appwrite server',
                'value' => '{apiEndpoint}',
                'placeholder' => '{apiEndpoint}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'EXPO_PUBLIC_APPWRITE_PROJECT_ID',
                'description' => 'Your Appwrite project ID',
                'value' => '{projectId}',
                'placeholder' => '{projectId}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'EXPO_PUBLIC_APPWRITE_PROJECT_NAME',
                'description' => 'Your Appwrite project name',
                'value' => '{projectName}',
                'placeholder' => '{projectName}',
                'required' => true,
                'type' => 'text'
            ],
        ]
    ],
    [
        'key' => 'starter-for-nextjs',
        'name' => 'Next.js starter',
        'useCases' => ['starter'],
        'demoImage' => $url . '/console/images/sites/templates/starter-for-nextjs.png',
        'frameworks' => [
            getFramework('NEXTJS', [
                'providerRootDirectory' => './',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'starter-for-nextjs',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.1.*',
        'variables' => [
            [
                'name' => 'NEXT_PUBLIC_APPWRITE_ENDPOINT',
                'description' => 'Endpoint of Appwrite server',
                'value' => '{apiEndpoint}',
                'placeholder' => '{apiEndpoint}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'NEXT_PUBLIC_APPWRITE_PROJECT_ID',
                'description' => 'Your Appwrite project ID',
                'value' => '{projectId}',
                'placeholder' => '{projectId}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'NEXT_PUBLIC_APPWRITE_PROJECT_NAME',
                'description' => 'Your Appwrite project name',
                'value' => '{projectName}',
                'placeholder' => '{projectName}',
                'required' => true,
                'type' => 'text'
            ],
        ]
    ],
    [
        'key' => 'starter-for-nuxt',
        'name' => 'Nuxt starter',
        'useCases' => ['starter'],
        'demoImage' => $url . '/console/images/sites/templates/starter-for-nuxt.png',
        'frameworks' => [
            getFramework('NUXT', [
                'providerRootDirectory' => './',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'starter-for-nuxt',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.1.*',
        'variables' => [
            [
                'name' => 'NUXT_PUBLIC_APPWRITE_ENDPOINT',
                'description' => 'Endpoint of Appwrite server',
                'value' => '{apiEndpoint}',
                'placeholder' => '{apiEndpoint}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'NUXT_PUBLIC_APPWRITE_PROJECT_ID',
                'description' => 'Your Appwrite project ID',
                'value' => '{projectId}',
                'placeholder' => '{projectId}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'NUXT_PUBLIC_APPWRITE_PROJECT_NAME',
                'description' => 'Your Appwrite project name',
                'value' => '{projectName}',
                'placeholder' => '{projectName}',
                'required' => true,
                'type' => 'text'
            ],
        ]
    ],
    [
        'key' => 'template-for-event',
        'name' => 'Event template',
        'useCases' => ['starter'],
        'demoImage' => $url . '/console/images/sites/templates/template-for-event.png',
        'frameworks' => [
            getFramework('NEXTJS', [
                'providerRootDirectory' => './',
                'installCommand' => 'pnpm install',
                'buildCommand' => 'npm run build',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'template-for-event',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.1.*',
        'variables' => [
            [
                'name' => 'NEXT_PUBLIC_APPWRITE_FUNCTION_PROJECT_ID',
                'description' => 'Endpoint of Appwrite server',
                'value' => '{apiEndpoint}',
                'placeholder' => '{apiEndpoint}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'NEXT_PUBLIC_APPWRITE_FUNCTION_API_ENDPOINT',
                'description' => 'Your Appwrite project ID',
                'value' => '{projectId}',
                'placeholder' => '{projectId}',
                'required' => true,
                'type' => 'text'
            ],
        ]
    ],
    [
        'key' => 'template-for-portfolio',
        'name' => 'Portfolio template',
        'useCases' => ['starter'],
        'demoImage' => $url . '/console/images/sites/templates/template-for-portfolio.png',
        'frameworks' => [
            getFramework('NEXTJS', [
                'providerRootDirectory' => './',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'template-for-portfolio',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.1.*',
        'variables' => []
    ],
    [
        'key' => 'template-for-store',
        'name' => 'Store template',
        'useCases' => ['starter'],
        'demoImage' => $url . '/console/images/sites/templates/template-for-store.png',
        'frameworks' => [
            getFramework('SVELTEKIT', [
                'providerRootDirectory' => './',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'template-for-store',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.1.*',
        'variables' => [
            [
                'name' => 'STRIPE_SECRET_KEY',
                'description' => 'Your Stripe secret key',
                'value' => 'disabled',
                'placeholder' => 'sk_.....',
                'required' => false,
                'type' => 'password'
            ],
            [
                'name' => 'PUBLIC_APPWRITE_ENDPOINT',
                'description' => 'Endpoint of Appwrite server',
                'value' => '{apiEndpoint}',
                'placeholder' => '{apiEndpoint}',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'PUBLIC_APPWRITE_PROJECT_ID',
                'description' => 'Your Appwrite project ID',
                'value' => '{projectId}',
                'placeholder' => '{projectId}',
                'required' => true,
                'type' => 'text'
            ],
        ]
    ],
    [
        'key' => 'template-for-blog',
        'name' => 'Blog template',
        'useCases' => ['starter'],
        'demoImage' => $url . '/console/images/sites/templates/template-for-blog.png',
        'frameworks' => [
            getFramework('SVELTEKIT', [
                'providerRootDirectory' => './',
            ]),
        ],
        'vcsProvider' => 'github',
        'providerRepositoryId' => 'template-for-blog',
        'providerOwner' => 'appwrite',
        'providerVersion' => '0.1.*',
        'variables' => []
    ],
    [
        'key' => 'astro-starter',
        'name' => 'Astro starter',
        'useCases' => ['starter'],
        'demoImage' => '',
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
        'name' => 'Remix starter',
        'useCases' => ['starter'],
        'demoImage' => '',
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
        'name' => 'Flutter starter',
        'useCases' => ['starter'],
        'demoImage' => '',
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
