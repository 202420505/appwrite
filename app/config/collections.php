<?php


$common = include __DIR__ . '/collections/common.php';
$projects = include __DIR__ . '/collections/projects.php';
$databases = include __DIR__ . '/collections/databases.php';
$platform = include __DIR__ . '/collections/platform.php';

$buckets = $common['files'];

// no more required.
unset($common['files']);

/**
 * $collection => id of the parent collection where this will be inserted
 * $id => id of this collection
 * name => name of this collection
 * project => whether this collection should be created per project
 * attributes => list of attributes
 * indexes => list of indexes
 */

$collections = [
    'buckets' => $buckets,
    'databases' => $databases,
    'projects' => array_merge($projects, $common),
    'console' => array_merge($platform, $common),
];

return $collections;
