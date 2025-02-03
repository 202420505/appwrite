<?php

return [
    'web/home' => [
        'key' => 'web/home',
        'name' => 'Home',
        'subtitle' => '',
        'description' => '',
        'controller' => 'web/home.php',
        'sdk' => false,
        'docs' => false,
        'docsUrl' => '',
        'tests' => false,
        'optional' => false,
        'icon' => '',
    ],
    'web/console' => [
        'key' => 'web/console',
        'name' => 'Console',
        'subtitle' => '',
        'description' => '',
        'controller' => 'web/console.php',
        'sdk' => false,
        'docs' => false,
        'docsUrl' => '',
        'tests' => false,
        'optional' => false,
        'icon' => '',
    ],
    'account' => [
        'key' => 'account',
        'name' => 'Account',
        'subtitle' => 'The Account service allows you to authenticate and manage a user account.',
        'description' => '/docs/services/account.md',
        'controller' => 'api/account.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/client/account',
        'tests' => false,
        'optional' => true,
        'icon' => '/images/services/account.png',
    ],
    'avatars' => [
        'key' => 'avatars',
        'name' => 'Avatars',
        'subtitle' => 'The Avatars service aims to help you complete everyday tasks related to your app image, icons, and avatars.',
        'description' => '/docs/services/avatars.md',
        'controller' => 'api/avatars.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/client/avatars',
        'tests' => false,
        'optional' => true,
        'icon' => '/images/services/avatars.png',
    ],
    'databases' => [
        'key' => 'databases',
        'name' => 'Databases',
        'subtitle' => 'The Databases service allows you to create structured collections of documents, query and filter lists of documents',
        'description' => '/docs/services/databases.md',
        'controller' => 'api/databases.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/client/databases',
        'tests' => false,
        'optional' => true,
        'icon' => '/images/services/databases.png',
        'globalAttributes' => [
            'databaseId'
        ]
    ],
    'locale' => [
        'key' => 'locale',
        'name' => 'Locale',
        'subtitle' => 'The Locale service allows you to customize your app based on your users\' location.',
        'description' => '/docs/services/locale.md',
        'controller' => 'api/locale.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/client/locale',
        'tests' => false,
        'optional' => true,
        'icon' => '/images/services/locale.png',
    ],
    'health' => [
        'key' => 'health',
        'name' => 'Health',
        'subtitle' => 'The Health service allows you to both validate and monitor your Appwrite server\'s health.',
        'description' => '/docs/services/health.md',
        'controller' => 'api/health.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/server/health',
        'tests' => false,
        'optional' => true,
        'icon' => '/images/services/health.png',
    ],
    'projects' => [
        'key' => 'projects',
        'name' => 'Projects',
        'subtitle' => 'The Project service allows you to manage all the projects in your Appwrite server.',
        'description' => '',
        'controller' => 'api/projects.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => '',
        'tests' => false,
        'optional' => false,
        'icon' => '',
    ],
    'project' => [
        'key' => 'project',
        'name' => 'Project',
        'subtitle' => 'The Project service allows you to manage all the projects in your Appwrite server.',
        'description' => '',
        'controller' => 'api/project.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => '',
        'tests' => false,
        'optional' => false,
        'icon' => '',
    ],
    'storage' => [
        'key' => 'storage',
        'name' => 'Storage',
        'subtitle' => 'The Storage service allows you to manage your project files.',
        'description' => '/docs/services/storage.md',
        'controller' => 'api/storage.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/client/storage',
        'tests' => false,
        'optional' => true,
        'icon' => '/images/services/storage.png',
    ],
    'teams' => [
        'key' => 'teams',
        'name' => 'Teams',
        'subtitle' => 'The Teams service allows you to group users of your project and to enable them to share read and write access to your project resources',
        'description' => '/docs/services/teams.md',
        'controller' => 'api/teams.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/client/teams',
        'tests' => false,
        'optional' => true,
        'icon' => '/images/services/teams.png',
    ],
    'users' => [
        'key' => 'users',
        'name' => 'Users',
        'subtitle' => 'The Users service allows you to manage your project users.',
        'description' => '/docs/services/users.md',
        'controller' => 'api/users.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/server/users',
        'tests' => false,
        'optional' => true,
        'icon' => '/images/services/users.png',
    ],
    'vcs' => [
        'key' => 'vcs',
        'name' => 'VCS',
        'subtitle' => 'The VCS service allows you to interact with providers like GitHub, GitLab etc.',
        'description' => '',
        'controller' => 'api/vcs.php',
        'sdk' => false,
        'docs' => false,
        'docsUrl' => '',
        'tests' => false,
        'optional' => false,
        'icon' => '',
    ],
    'sites' => [
        'key' => 'sites',
        'name' => 'Sites',
        'subtitle' => 'The Sites Service allows you view, create and manage your web applications.',
        'description' => '/docs/services/sites.md',
        'controller' => '', // Uses modules
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/sites',
        'tests' => false,
        'optional' => true,
        'icon' => '', // TODO: Update icon later
    ],
    'functions' => [
        'key' => 'functions',
        'name' => 'Functions',
        'subtitle' => 'The Functions Service allows you view, create and manage your Cloud Functions.',
        'description' => '/docs/services/functions.md',
        'controller' => 'api/functions.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/functions',
        'tests' => false,
        'optional' => true,
        'icon' => '/images/services/functions.png',
    ],
    'proxy' => [
        'key' => 'proxy',
        'name' => 'Proxy',
        'subtitle' => 'The Proxy Service allows you to configure actions for your domains beyond DNS configuration.',
        'description' => '/docs/services/proxy.md',
        'controller' => 'api/proxy.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/proxy',
        'tests' => false,
        'optional' => false,
        'icon' => '/images/services/proxy.png',
    ],
    'mock' => [
        'key' => 'mock',
        'name' => 'Mock',
        'subtitle' => '',
        'description' => '',
        'controller' => 'mock.php',
        'sdk' => false,
        'docs' => false,
        'docsUrl' => '',
        'tests' => true,
        'optional' => false,
        'icon' => '',
    ],
    'graphql' => [
        'key' => 'graphql',
        'name' => 'GraphQL',
        'subtitle' => 'The GraphQL API allows you to query and mutate your Appwrite server using GraphQL.',
        'description' => '/docs/services/graphql.md',
        'controller' => 'api/graphql.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/graphql',
        'tests' => true,
        'optional' => true,
        'icon' => '/images/services/graphql.png',
    ],
    'console' => [
        'key' => 'console',
        'name' => 'Console',
        'subtitle' => 'The Console service allows you to interact with console relevant informations.',
        'description' => '',
        'controller' => 'api/console.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => '',
        'tests' => false,
        'optional' => false,
        'icon' => '',
    ],
    'migrations' => [
        'key' => 'migrations',
        'name' => 'Migrations',
        'subtitle' => 'The Migrations service allows you to migrate third-party data to your Appwrite project.',
        'description' => '/docs/services/migrations.md',
        'controller' => 'api/migrations.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/migrations',
        'tests' => true,
        'optional' => false,
        'icon' => '/images/services/migrations.png',
    ],
    'messaging' => [
        'key' => 'messaging',
        'name' => 'Messaging',
        'subtitle' => 'The Messaging service allows you to send messages to any provider type (SMTP, push notification, SMS, etc.).',
        'description' => '/docs/services/messaging.md',
        'controller' => 'api/messaging.php',
        'sdk' => true,
        'docs' => true,
        'docsUrl' => 'https://appwrite.io/docs/server/messaging',
        'tests' => true,
        'optional' => true,
        'icon' => '/images/services/messaging.png',
    ]
];
