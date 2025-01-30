<?php

namespace Appwrite\Utopia\Response\Model;

use Appwrite\Utopia\Response;
use Appwrite\Utopia\Response\Model;

class Platform extends Model
{
    /**
     * @var bool
     */
    protected bool $public = false;

    public function __construct()
    {
        $this
            ->addRule('$id', [
                'type' => self::TYPE_STRING,
                'description' => 'Platform ID.',
                'default' => '',
                'example' => '5e5ea5c16897e',
            ])
            ->addRule('$createdAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Platform creation date in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('$updatedAt', [
                'type' => self::TYPE_DATETIME,
                'description' => 'Platform update date in ISO 8601 format.',
                'default' => '',
                'example' => self::TYPE_DATETIME_EXAMPLE,
            ])
            ->addRule('name', [
                'type' => self::TYPE_STRING,
                'description' => 'Platform name.',
                'default' => '',
                'example' => 'My Web App',
            ])
            ->addRule('type', [
                'type' => self::TYPE_STRING,
                'description' => 'Platform type. Possible values are: web, flutter-web, flutter-ios, flutter-android, ios, android, react-native-android, react-native-ios, scheme and unity.',
                'default' => '',
                'example' => 'web',
            ])
            ->addRule('key', [
                'type' => self::TYPE_STRING,
                'description' => 'Platform Key. iOS bundle ID or Android package name or app scheme.  Empty string for other platforms.',
                'default' => '',
                'example' => 'com.company.appname',
            ])
            ->addRule('store', [
                'type' => self::TYPE_STRING,
                'description' => 'App store or Google Play store ID.',
                'example' => '',
            ])
            ->addRule('hostname', [
                'type' => self::TYPE_STRING,
                'description' => 'Web app hostname. Empty string for other platforms.',
                'default' => '',
                'example' => true,
            ])
            ->addRule('httpUser', [
                'type' => self::TYPE_STRING,
                'description' => 'HTTP basic authentication username.',
                'default' => '',
                'example' => 'username',
            ])
            ->addRule('httpPass', [
                'type' => self::TYPE_STRING,
                'description' => 'HTTP basic authentication password.',
                'default' => '',
                'example' => 'password',
            ])
        ;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Platform';
    }

    /**
     * Get Type
     *
     * @return string
     */
    public function getType(): string
    {
        return Response::MODEL_PLATFORM;
    }
}
