<?php

use Appwrite\Auth\Auth;
use Appwrite\Event\Transcoding;
use Appwrite\Utopia\Database\Validator\CustomId;
use Appwrite\Stats\Stats;
use Appwrite\Utopia\Response;
use Appwrite\Utopia\View;
use Utopia\App;
use Utopia\Database\Database;
use Utopia\Database\Document;
use Utopia\Database\Exception\Duplicate as DuplicateException;
use Utopia\Database\Exception\Structure as StructureException;
use Utopia\Database\Query;
use Utopia\Database\Validator\Authorization;
use Utopia\Database\Validator\UID;
use Appwrite\Extend\Exception;
use Utopia\Storage\Device;
use Utopia\Validator\Boolean;
use Utopia\Validator\Range;
use Utopia\Validator\Text;
use Utopia\Validator\WhiteList;
use Utopia\Swoole\Request;

/**
 * Validate file Permissions
 *
 * @param Database $dbForProject
 * @param string $bucketId
 * @param string $fileId
 * @param string $mode
 * @return Document $file
 * @throws Exception
 */
function validateFilePermissions(Database $dbForProject, string $bucketId, string $fileId, string $mode, Document $user): Document
{

    $bucket = Authorization::skip(fn () => $dbForProject->getDocument('buckets', $bucketId));

    if ($bucket->isEmpty() || (!$bucket->getAttribute('enabled') && $mode !== APP_MODE_ADMIN)) {
        throw new Exception('Bucket not found', 404, Exception::STORAGE_BUCKET_NOT_FOUND);
    }

    // Check bucket permissions when enforced
    $permissionBucket = $bucket->getAttribute('permission') === 'bucket';
    if ($permissionBucket) {
        $validator = new Authorization('read');
        if (!$validator->isValid($bucket->getRead())) {
            throw new Exception('Unauthorized file permissions', 401, Exception::USER_UNAUTHORIZED);
        }
    }

    $read = !$user->isEmpty() ? ['user:' . $user->getId()] : []; // By default set read permissions for user

    // Users can only add their roles to files, API keys and Admin users can add any
    $roles = Authorization::getRoles();

    if (!Auth::isAppUser($roles) && !Auth::isPrivilegedUser($roles)) {
        foreach ($read as $role) {
            if (!Authorization::isRole($role)) {
                throw new Exception('Read permissions must be one of: (' . \implode(', ', $roles) . ')', 400, Exception::USER_UNAUTHORIZED);
            }
        }
    }

    if ($bucket->getAttribute('permission') === 'bucket') {
        // skip authorization
        $file = Authorization::skip(fn () => $dbForProject->getDocument('bucket_' . $bucket->getInternalId(), $fileId));
    } else {
        $file = $dbForProject->getDocument('bucket_' . $bucket->getInternalId(), $fileId);
    }

    return $file;
}


App::post('/v1/videos/profiles')
    ->desc('Create video profile')
    ->groups(['api', 'video'])
    ->label('scope', 'files.read')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'createProfile')
    ->label('sdk.description', '/docs/references/videos/create-profile.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_VIDEO_PROFILE)
    ->param('name', null, new Text(128), 'Video profile name.')
    ->param('videoBitrate', '', new Range(64, 4000), 'Video profile bitrate in Kbps.')
    ->param('audioBitrate', '', new Range(64, 4000), 'Audio profile bit rate in Kbps.')
    ->param('width', '', new Range(100, 2000), 'Video profile width.')
    ->param('height', '', new Range(100, 2000), 'Video  profile height.')
    ->param('stream', false, new WhiteList(['hls', 'dash']), 'Video  profile stream protocol.')
    ->inject('response')
    ->inject('dbForProject')
    ->action(action: function (string $name, string $videoBitrate, string $audioBitrate, string $width, string $height, string $stream, Response $response, Database $dbForProject) {
        try {
            $profile = Authorization::skip(function () use ($dbForProject, $name, $videoBitrate, $audioBitrate, $width, $height, $stream) {
                return $dbForProject->createDocument('videos_profiles', new Document([
                    'name'          => $name,
                    'videoBitrate'  => (int)$videoBitrate,
                    'audioBitrate'  => (int)$audioBitrate,
                    'width'         => (int)$width,
                    'height'        => (int)$height,
                    'stream'        => $stream,
                ]));
            });
        } catch (DuplicateException $exception) {
            throw new Exception('Profile already exists', 409, Exception::VIDEO_PROFILE_ALREADY_EXISTS);
        }

        $response->setStatusCode(Response::STATUS_CODE_CREATED);
        $response->dynamic($profile, Response::MODEL_VIDEO_PROFILE);
    });


App::patch('/v1/videos/profiles/:profileId')
    ->desc('Update video  profile')
    ->groups(['api', 'video'])
    ->label('scope', 'files.write')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'updateProfile')
    ->label('sdk.description', '/docs/references/videos/update-profile.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_VIDEO_PROFILE)
    ->param('profileId', '', new UID(), 'Video profile unique ID.')
    ->param('name', null, new Text(128), 'Video profile name.')
    ->param('videoBitrate', '', new Range(64, 4000), 'Video profile bitrate in Kbps.')
    ->param('audioBitrate', '', new Range(64, 4000), 'Audio profile bit rate in Kbps.')
    ->param('width', '', new Range(100, 2000), 'Video profile width.')
    ->param('height', '', new Range(100, 2000), 'Video  profile height.')
    ->param('stream', false, new WhiteList(['hls', 'dash']), 'Video  profile stream protocol.')
    ->inject('response')
    ->inject('dbForProject')
    ->action(action: function (string $profileId, string $name, string $videoBitrate, string $audioBitrate, string $width, string $height, string $stream, Response $response, Database $dbForProject) {

        $profile = Authorization::skip(fn() => $dbForProject->getDocument('videos_profiles', $profileId));
        ;
        if ($profile->isEmpty()) {
            throw new Exception('Project not found', 404, Exception::PROJECT_NOT_FOUND);
        }

        $profile->setAttribute('name', $name)
                 ->setAttribute('videoBitrate', (int)$videoBitrate)
                ->setAttribute('audioBitrate', (int)$audioBitrate)
                ->setAttribute('width', (int)$width)
                ->setAttribute('height', (int)$height)
                ->setAttribute('stream', $stream);

        $profile = Authorization::skip(fn() => $dbForProject->updateDocument('videos_profiles', $profile->getId(), $profile));

        $response->dynamic($profile, Response::MODEL_VIDEO_PROFILE);
    });


App::get('/v1/videos/profiles/:profileId')
    ->desc('Get video profile')
    ->groups(['api', 'video'])
    ->label('scope', 'files.read')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getProfile')
    ->label('sdk.description', '/docs/references/videos/get-profile.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_VIDEO_PROFILE)
    ->param('profileId', '', new UID(), 'Video profile unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->action(function (string $profileId, Response $response, Database $dbForProject) {

        $profile = Authorization::skip(fn() => $dbForProject->getDocument('videos_profiles', $profileId));

        if ($profile->isEmpty()) {
            throw new Exception('Video profile not found', 404, Exception::VIDEO_PROFILE_NOT_FOUND);
        }

        $response->dynamic($profile, Response::MODEL_VIDEO_PROFILE);
    });


App::get('/v1/videos/profiles')
    ->desc('Get all video profiles')
    ->groups(['api', 'video'])
    ->label('scope', 'files.read')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getProfiles')
    ->label('sdk.description', '/docs/references/videos/get-profiles.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_VIDEO_PROFILE_LIST)
    ->inject('response')
    ->inject('dbForProject')
    ->action(function (Response $response, Database $dbForProject) {

        $profiles = Authorization::skip(fn () => $dbForProject->find('videos_profiles', [], 12, 0, [], ['ASC']));

        if (empty($profiles)) {
            throw new Exception('Video profiles where not found', 404, Exception::VIDEO_PROFILE_NOT_FOUND);
        }

        $response->dynamic(new Document([
            'total' => $dbForProject->count('videos_profiles', [], APP_LIMIT_COUNT),
            'profiles' => $profiles,
        ]), Response::MODEL_VIDEO_PROFILE_LIST);
    });


App::delete('/v1/videos/profiles/:profileId')
    ->desc('Delete video profile')
    ->groups(['api', 'video'])
    ->label('scope', 'files.write')
    ->label('sdk.namespace', 'video')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.method', 'deleteProfile')
    ->label('sdk.description', '/docs/references/videos/delete-profile.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_NOCONTENT)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->param('profileId', '', new UID(), 'Video profile unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->action(function (string $profileId, Response $response, Database $dbForProject) {

        $profile = Authorization::skip(fn() => $dbForProject->getDocument('videos_profiles', $profileId));

        if ($profile->isEmpty()) {
            throw new Exception('Video profile not found', 404, Exception::VIDEO_PROFILE_NOT_FOUND);
        }

        $deleted = $dbForProject->deleteDocument('videos_profiles', $profileId);

        if (!$deleted) {
            throw new Exception('Failed to remove video profile from DB', 500, Exception::GENERAL_SERVER_ERROR);
        }

        $response->noContent();
    });


App::post('/v1/videos/:videoId/subtitles')
    ->desc('Add subtitle to video')
    ->groups(['api', 'video'])
    ->label('scope', 'files.write')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'addSubtitle')
    ->label('sdk.description', '/docs/references/videos/add-subtitle.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->param('bucketId', '', new CustomId(), 'Subtitle bucket unique ID.')
    ->param('fileId', '', new CustomId(), 'Subtitle file unique ID.')
    ->param('name', '', new Text(128), 'Subtitle name.')
    ->param('code', '', new Text(128), 'Subtitle code name.')
    ->param('default', false, new Boolean(true), 'Default subtitle.')
    ->inject('request')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('user')
    ->inject('mode')
    ->action(action: function (string $videoId, string $bucketId, string $fileId, string $name, string $code, bool $default, Request $request, Response $response, Database $dbForProject, Document $user, string $mode) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [new Query('_uid', Query::TYPE_EQUAL, [$videoId])]));

        if (empty($video)) {
            throw new Exception('Video not found', 400, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);
        validateFilePermissions($dbForProject, $bucketId, $fileId, $mode, $user);

        $subtitle = Authorization::skip(function () use ($dbForProject, $videoId, $bucketId, $fileId, $name, $code, $default) {
                return $dbForProject->createDocument('videos_subtitles', new Document([
                    'videoId'   => $videoId,
                    'bucketId'  => $bucketId,
                    'fileId'    => $fileId,
                    'name'      => $name,
                    'code'      => $code,
                    'default'   => $default,
                ]));
        });

        $response->setStatusCode(Response::STATUS_CODE_CREATED);
        $response->dynamic($subtitle, Response::MODEL_VIDEO_SUBTITLE);
    });


App::get('/v1/videos/:videoId/subtitles')
    ->desc('Get all video subtitles')
    ->groups(['api', 'video'])
    ->label('scope', 'files.read')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getSubtitles')
    ->label('sdk.description', '/docs/references/videos/get-subtitles.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_VIDEO_SUBTITLE_LIST)
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->action(function ($videoId, Response $response, Database $dbForProject) {

        $subtitles = Authorization::skip(fn () => $dbForProject->find('videos_subtitles', [new Query('videoId', Query::TYPE_EQUAL, [$videoId])], 12, 0, [], ['ASC']));

        if (empty($subtitles)) {
            throw new Exception('Video subtitles  not found', 404, Exception::VIDEO_SUBTITLE_NOT_FOUND);
        }

        $response->dynamic(new Document([
            'total' => $dbForProject->count('videos_subtitles', [], APP_LIMIT_COUNT),
            'subtitles' => $subtitles,
        ]), Response::MODEL_VIDEO_SUBTITLE_LIST);
    });


App::patch('/v1/videos/:videoId/subtitles/:subtitleId')
    ->desc('Update video subtitle')
    ->groups(['api', 'video'])
    ->label('scope', 'files.write')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'updateSubtitle')
    ->label('sdk.description', '/docs/references/videos/update-subtitle.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_VIDEO_SUBTITLE)
    ->param('subtitleId', null, new UID(), 'Video subtitle unique ID.')
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->param('bucketId', '', new CustomId(), 'Subtitle bucket unique ID.')
    ->param('fileId', '', new CustomId(), 'Subtitle file unique ID.')
    ->param('name', '', new Text(128), 'Subtitle name.')
    ->param('code', '', new Text(128), 'Subtitle code name.')
    ->param('default', false, new Boolean(true), 'Default subtitle.')
    ->inject('response')
    ->inject('dbForProject')
    ->action(action: function (string $subtitleId, string $videoId, string $bucketId, string $fileId, string $name, string $code, bool $default, Response $response, Database $dbForProject) {

        $subtitle = Authorization::skip(fn() => $dbForProject->getDocument('videos_subtitles', $subtitleId));

        if ($subtitle->isEmpty()) {
            throw new Exception('Project not found', 404, Exception::PROJECT_NOT_FOUND);
        }

        $subtitle->setAttribute('videoId', $videoId)
            ->setAttribute('bucketId', $bucketId)
            ->setAttribute('fileId', $fileId)
            ->setAttribute('name', $name)
            ->setAttribute('code', $code)
            ->setAttribute('default', $default);

        $subtitle = Authorization::skip(fn() => $dbForProject->updateDocument('videos_subtitles', $subtitle->getId(), $subtitle));

        $response->dynamic($subtitle, Response::MODEL_VIDEO_SUBTITLE);
    });


App::delete('/v1/videos/:videoId/subtitles/:subtitleId')
    ->desc('Delete video subtitle')
    ->groups(['api', 'video'])
    ->label('scope', 'files.write')
    ->label('sdk.namespace', 'video')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.method', 'deleteSubtitle')
    ->label('sdk.description', '/docs/references/videos/delete-subtitle.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_NOCONTENT)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->param('videoId', '', new UID(), 'Video  unique ID.')
    ->param('subtitleId', '', new UID(), 'Subtitle unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('user')
    ->inject('mode')
    ->action(function (string $videoId, string $subtitleId, Response $response, Database $dbForProject, Document $user, string $mode) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [new Query('_uid', Query::TYPE_EQUAL, [$videoId])]));

        if ($video->isEmpty()) {
            throw new Exception('Video not found', 400, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);


        $subtitle = Authorization::skip(fn() => $dbForProject->getDocument('videos_subtitles', $subtitleId));

        if ($subtitle->isEmpty()) {
            throw new Exception('Video subtitle not found', 404, Exception::VIDEO_PROFILE_NOT_FOUND);
        }

        $deleted = $dbForProject->deleteDocument('videos_subtitles', $subtitleId);

        if (!$deleted) {
            throw new Exception('Failed to remove video subtitle from DB', 500, Exception::GENERAL_SERVER_ERROR);
        }

        $response->noContent();
    });

App::get('/v1/videos/buckets/:bucketId')
    ->desc('Get all backet\'s videos')
    ->groups(['api', 'video'])
    ->label('scope', 'files.read')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getVideos')
    ->label('sdk.description', '/docs/references/videos/get-videos.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_VIDEO_SUBTITLE_LIST)
    ->param('bucketId', null, new UID(), 'bucket unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->action(function ($videoId, Response $response, Database $dbForProject) {

        $subtitles = Authorization::skip(fn () => $dbForProject->find('videos_subtitles', [new Query('videoId', Query::TYPE_EQUAL, [$videoId])], 12, 0, [], ['ASC']));

        if (empty($subtitles)) {
            throw new Exception('Video subtitles  not found', 404, Exception::VIDEO_SUBTITLE_NOT_FOUND);
        }

        $response->dynamic(new Document([
            'total' => $dbForProject->count('videos_subtitles', [], APP_LIMIT_COUNT),
            'subtitles' => $subtitles,
        ]), Response::MODEL_VIDEO_SUBTITLE_LIST);
    });


App::post('/v1/video')
    ->desc('Create Video')
    ->groups(['api', 'video'])
    ->label('scope', 'files.write')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'create')
    ->label('sdk.description', '/docs/references/videos/create.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_VIDEO)
    ->param('bucketId', null, new UID(), 'Storage bucket unique ID. You can create a new storage bucket using the Storage service [server integration](/docs/server/storage#createBucket).')
    ->param('fileId', '', new CustomId(), 'File ID. Choose your own unique ID or pass the string "unique()" to auto generate it. Valid chars are a-z, A-Z, 0-9, period, hyphen, and underscore. Can\'t start with a special char. Max length is 36 chars.')
    ->inject('request')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('user')
    ->inject('mode')
    ->action(action: function (string $bucketId, string $fileId, Request $request, Response $response, Database $dbForProject, Document $user, string $mode) {

        $file = validateFilePermissions($dbForProject, $bucketId, $fileId, $mode, $user);
        $video = Authorization::skip(function () use ($dbForProject, $bucketId, $file) {
                return $dbForProject->createDocument('videos', new Document([
                    'bucketId'  => $bucketId,
                    'fileId'    => $file->getId(),
                    'size'      => $file->getAttribute('sizeOriginal'),
                ]));
        });

        $response->setStatusCode(Response::STATUS_CODE_CREATED);
        $response->dynamic($video, Response::MODEL_VIDEO);
    });

App::delete('/v1/videos/:videoId')
    ->desc('Delete video')
    ->groups(['api', 'video'])
    ->label('scope', 'files.write')
    ->label('sdk.namespace', 'video')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.method', 'delete')
    ->label('sdk.description', '/docs/references/videos/delete.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_NOCONTENT)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->param('videoId', '', new UID(), 'Video unique ID.')
    ->inject('response')
    ->inject('project')
    ->inject('dbForProject')
    ->inject('mode')
    ->inject('user')
    ->inject('deviceVideos')
    ->action(function (string $videoId, string $renditionId, Response $response, Document $project, Database $dbForProject, string $mode, Document $user, Device $deviceVideos) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [new Query('_uid', Query::TYPE_EQUAL, [$videoId])]));

        if ($video->isEmpty()) {
            throw new Exception('Video not found', 400, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);

        $deleted = $dbForProject->deleteDocument('videos', $videoId);

        if (!$deleted) {
            throw new Exception('Failed to remove video from DB', 500, Exception::GENERAL_SERVER_ERROR);
        }

        $renditions = Authorization::skip(fn() => $dbForProject->find('videos_renditions', [
            new Query('videoId', Query::TYPE_EQUAL, [$video->getId()]),
        ], 12, 0, [], ['ASC']));

        foreach ($renditions as $rendition) {
            Authorization::skip(fn() => $dbForProject->deleteDocument('videos_renditions', $rendition->getId()));
        }

        $subtitles = Authorization::skip(fn() => $dbForProject->find('videos_subtitles', [
            new Query('videoId', Query::TYPE_EQUAL, [$video->getId()]),
        ], 12, 0, [], ['ASC']));

        foreach ($subtitles as $subtitle) {
            Authorization::skip(fn() => $dbForProject->deleteDocument('videos_subtitles', $subtitle->getId()));
        }

        foreach ($renditions as $rendition) {
            Authorization::skip(fn() => $dbForProject->deleteDocument('videos_renditions', $rendition->getId()));
        }

        $videoPath  = $this->getVideoDevice($project->getId())->getPath($this->args['videoId']);
        $deviceVideos->deletePath($videoPath);

        $response->noContent();
    });


App::post('/v1/videos/:videoId/rendition')
    ->alias('/v1/videos/:videoId/rendition', [])
    ->desc('Create video rendition')
    ->groups(['api', 'video'])
    ->label('scope', 'files.write')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'createRendition')
    ->label('sdk.description', '/docs/references/videos/create-rendition.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_NOCONTENT)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->param('profileId', '', new CustomId(), 'Profile unique ID.')
    ->inject('request')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('project')
    ->inject('user')
    ->inject('mode')
    ->action(action: function (string $videoId, string $profileId, Request $request, Response $response, Database $dbForProject, Document $project, Document $user, string $mode) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [new Query('_uid', Query::TYPE_EQUAL, [$videoId])]));

        if ($video->isEmpty()) {
            throw new Exception('Video not found', 400, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);

        $profile = Authorization::skip(fn() => $dbForProject->findOne('videos_profiles', [new Query('_uid', Query::TYPE_EQUAL, [$profileId])]));

        if (!$profile) {
            throw new Exception('Video profile not found', 400, Exception::VIDEO_PROFILE_NOT_FOUND);
        }

        $transcoder = new Transcoding();
        $transcoder
           ->setUser($user)
           ->setProject($project)
           ->setVideoId($video->getId())
           ->setProfileId($profile->getId())
           ->trigger();

        $response->noContent();
    });


App::get('/v1/videos/:videoId/rendition/:renditionId')
    ->desc('Get all backet\'s videos')
    ->groups(['api', 'video'])
    ->label('scope', 'files.read')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getRendition')
    ->label('sdk.description', '/docs/references/videos/get-rendition.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_VIDEO_RENDITION)
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->param('renditionId', null, new UID(), 'Rendition unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('mode')
    ->inject('user')
    ->action(function ($videoId, $renditionId, Response $response, Database $dbForProject, string $mode, Document $user) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [new Query('_uid', Query::TYPE_EQUAL, [$videoId])]));

        if ($video->isEmpty()) {
            throw new Exception('Video not found', 400, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);


        $rendition = Authorization::skip(fn () => $dbForProject->findOne('videos_renditions', [new Query('_uid', Query::TYPE_EQUAL, [$renditionId])]));

        if ($rendition->isEmpty()) {
            throw new Exception('Video rendition not found', 404, Exception::VIDEO_RENDITION_NOT_FOUND);
        }

        $response->dynamic($rendition, Response::MODEL_VIDEO_RENDITION);
    });


App::get('/v1/videos/:videoId/renditions')
    ->desc('Get video renditions')
    ->groups(['api', 'video'])
    ->label('scope', 'files.read')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getRenditions')
    ->label('sdk.description', '/docs/references/videos/get-renditions.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('sdk.response.model', Response::MODEL_VIDEO_RENDITION_LIST)
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('mode')
    ->inject('user')
    ->action(function (string $videoId, Response $response, Database $dbForProject, string $mode, Document $user) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [new Query('_uid', Query::TYPE_EQUAL, [$videoId])]));

        if ($video->isEmpty()) {
            throw new Exception('Video not found', 400, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);

        $queries = [
            new Query('videoId', Query::TYPE_EQUAL, [$video->getId()]),
            new Query('endedAt', Query::TYPE_GREATER, [0]),
            new Query('status', Query::TYPE_EQUAL, ['ready']),
        ];

        $renditions = Authorization::skip(fn () => $dbForProject->find('videos_renditions', $queries, 18, 0, [], ['ASC']));

        $response->dynamic(new Document([
            'total'      => $dbForProject->count('videos_renditions', $queries, APP_LIMIT_COUNT),
            'renditions' => $renditions,
        ]), Response::MODEL_VIDEO_RENDITION_LIST);
    });


App::delete('/v1/videos/:videoId/renditions/:renditionId')
    ->desc('Delete video subtitle')
    ->groups(['api', 'video'])
    ->label('scope', 'files.write')
    ->label('sdk.namespace', 'video')
    ->label('sdk.auth', [APP_AUTH_TYPE_KEY])
    ->label('sdk.method', 'deleteRendition')
    ->label('sdk.description', '/docs/references/videos/delete-rendition.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_NOCONTENT)
    ->label('sdk.response.model', Response::MODEL_NONE)
    ->param('videoId', '', new UID(), 'Video unique ID.')
    ->param('renditionId', '', new UID(), 'Video rendition unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('mode')
    ->inject('user')
    ->inject('deviceVideos')
    ->action(function (string $videoId, string $renditionId, Response $response, Database $dbForProject, string $mode, Document $user, Device $deviceVideos) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [new Query('_uid', Query::TYPE_EQUAL, [$videoId])]));

        if ($video->isEmpty()) {
            throw new Exception('Video not found', 400, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);

        $rendition = Authorization::skip(fn() => $dbForProject->getDocument('videos_renditions', $renditionId));

        if ($rendition->isEmpty()) {
            throw new Exception('Video rendition not found', 404, Exception::VIDEO_RENDITION_NOT_FOUND);
        }

        $deleted = $dbForProject->deleteDocument('videos_renditions', $renditionId);

        if (!$deleted) {
            throw new Exception('Failed to remove video rendition from DB', 500, Exception::GENERAL_SERVER_ERROR);
        }

        Authorization::skip(fn() => $dbForProject->deleteDocument('videos_renditions', $rendition->getId()));
        if (!empty($rendition['path'])) {
            $deviceVideos->deletePath($rendition['path']);
        }

        $response->noContent();
    });


App::get('/v1/videos/:videoId/streams/:streamId')
    ->desc('Get video master renditions manifest')
    ->groups(['api', 'video'])
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getMasterManifest')
    ->label('sdk.description', '/docs/references/videos/get-master-manifest.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    ->label('scope', 'files.read')
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->param('streamId', '', new WhiteList(['hls', 'dash']), 'stream protocol name')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('mode')
    ->inject('user')
    ->action(function (string $videoId, string $streamId, Response $response, Database $dbForProject, string $mode, Document $user) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [
            new Query('_uid', Query::TYPE_EQUAL, [$videoId])
        ]));

        if ($video->isEmpty()) {
            throw new Exception('Video not found', 400, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);

        $renditions = Authorization::skip(fn () => $dbForProject->find('videos_renditions', [
            new Query('videoId', Query::TYPE_EQUAL, [$video->getId()]),
            new Query('endedAt', Query::TYPE_GREATER, [0]),
            new Query('status', Query::TYPE_EQUAL, ['ready']),
            new Query('stream', Query::TYPE_EQUAL, [$streamId]),
        ], 12, 0, [], ['ASC']));

        if (empty($renditions)) {
            throw new Exception('Rendition  not found', 404, Exception::VIDEO_RENDITION_NOT_FOUND);
        }

        $baseUrl = 'http://127.0.0.1/v1/videos/' . $videoId . '/streams/' . $streamId;

        if ($streamId === 'hls') {
            $subtitles = Authorization::skip(fn() => $dbForProject->find('videos_subtitles', [new Query('videoId', Query::TYPE_EQUAL, [$video->getId()])], 12, 0, [], ['ASC']));
            $paramsSubtitles = [];
            foreach ($subtitles as $subtitle) {
                $paramsSubtitles[] = [
                    'name' => $subtitle->getAttribute('name'),
                    'code' => $subtitle->getAttribute('code'),
                    'default' => !empty($subtitle->getAttribute('default')) ? 'YES' : 'NO',
                    'uri' => $baseUrl . '/subtitles/' . $subtitle->getId(),
                ];
            }

            $paramsRenditions = [];
            foreach ($renditions as $rendition) {
                $paramsRenditions[] = [
                    'bandwidth' => ($rendition->getAttribute('videoBitrate') + $rendition->getAttribute('audioBitrate')),
                    'resolution' => $rendition->getAttribute('width') . 'X' . $rendition->getAttribute('height'),
                    'name' => $rendition->getAttribute('name'),
                    'uri' => $baseUrl . '/renditions/' . $rendition->getId(),
                    'subs' => !empty($paramsSubtitles) ? ' SUBTITLES="subs"' : '',
                ];
            }

            $template = new View(__DIR__ . '/../../views/videos/hls-master.phtml');
            $template->setParam('paramsSubtitles', $paramsSubtitles);
            $template->setParam('paramsRenditions', $paramsRenditions);
            $response->setContentType('application/x-mpegurl')->send($template->render(false));
        } else {
            $adaptations = [];
            $adaptationId = 0;
            foreach ($renditions as $rendition) {
                $metadata = $rendition->getAttribute('metadata');
                $xml = simplexml_load_string($metadata['xml']);
                $representationId = 0;
                foreach ($xml->Period->AdaptationSet as $adaptation) {
                    $representation = [];
                    $representation['id'] = $representationId;
                    $attributes = (array)$adaptation->Representation->attributes();
                    $representation['attributes'] = $attributes['@attributes'] ?? [];
                    $attributes = (array)$adaptation->Representation->SegmentList->attributes();
                    $representation['SegmentList']['attributes'] = $attributes['@attributes'] ?? [];
                    $segments = Authorization::skip(fn() => $dbForProject->find('videos_renditions_segments', [
                        new Query('renditionId', Query::TYPE_EQUAL, [$rendition->getId()]),
                        new Query('representationId', Query::TYPE_EQUAL, [$representationId]),
                        ], 1000, 0, ['representationId'], ['ASC']));

                    foreach ($segments ?? [] as $segment) {
                        if ($segment->getAttribute('isInit')) {
                            $representation['SegmentList']['Initialization'] = $segment->getId();
                            continue;
                        }
                        $representation['SegmentList']['media'][] = $segment->getId();
                    }
                     $attributes = (array)$adaptation->attributes();
                     $adaptations[] =  ['attributes' => $attributes['@attributes'] ?? [],
                        'id' => $adaptationId,
                        'baseUrl' => $baseUrl . '/renditions/' . $rendition->getId() . '/' . 'segments/',
                        'Representation' => $representation,
                     ];
                     $adaptationId++;
                     $representationId++;
                }
            }

            $template = new View(__DIR__ . '/../../views/videos/dash.phtml');
            $template->setParam('params', $adaptations);
            $response->setContentType('application/dash+xml')->send($template->render(false));
        }
    });


App::get('/v1/videos/:videoId/streams/:streamId/renditions/:renditionId')
    ->desc('Get video rendition manifest')
    ->groups(['api', 'video'])
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getManifest')
    ->label('sdk.description', '/docs/references/videos/get-manifest.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    // TODO: Response model
    ->label('scope', 'files.read')
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->param('streamId', '', new WhiteList(['hls', 'dash']), 'stream protocol name')
    ->param('renditionId', '', new UID(), 'Rendition unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('mode')
    ->inject('user')
    ->action(function (string $videoId, string $streamId, string $renditionId, Response $response, Database $dbForProject, string $mode, Document $user) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [
            new Query('_uid', Query::TYPE_EQUAL, [$videoId])
        ]));

        if (empty($video)) {
            throw new Exception('Video not found', 404, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);

        $rendition = Authorization::skip(fn () => $dbForProject->findOne('videos_renditions', [
            new Query('_uid', Query::TYPE_EQUAL, [$renditionId]),
            new Query('videoId', Query::TYPE_EQUAL, [$video->getId()]),
            new Query('endedAt', Query::TYPE_GREATER, [0]),
            new Query('status', Query::TYPE_EQUAL, ['ready']),
            new Query('stream', Query::TYPE_EQUAL, [$streamId]),
        ]));

        if (empty($rendition)) {
            throw new Exception('Rendition  not found', 404, Exception::VIDEO_RENDITION_NOT_FOUND);
        }

            $segments = Authorization::skip(fn() => $dbForProject->find('videos_renditions_segments', [
                new Query('renditionId', Query::TYPE_EQUAL, [$renditionId]),
                ], 4000));

        if (empty($segments)) {
            throw new Exception('Rendition segments not found', 404, Exception::VIDEO_RENDITION_SEGMENT_NOT_FOUND);
        }

            $paramsSegments = [];
        foreach ($segments as $segment) {
            $paramsSegments[] = [
                'duration' => $segment->getAttribute('duration'),
                'url' => 'http://127.0.0.1/v1/videos/' . $videoId . '/streams/' . $streamId . '/renditions/' . $renditionId . '/segments/' . $segment->getId(),
            ];
        }

            $template = new View(__DIR__ . '/../../views/videos/hls.phtml');
            $template->setParam('targetDuration', $rendition->getAttribute('targetDuration'));
            $template->setParam('paramsSegments', $paramsSegments);
            $response->setContentType('application/x-mpegurl')->send($template->render(false));
    });


App::get('/v1/videos/:videoId/streams/:streamId/renditions/:renditionId/segments/:segmentId')
    ->desc('Get video rendition segment')
    ->groups(['api', 'video'])
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getRenditionSegment')
    ->label('sdk.description', '/docs/references/videos/get-rendition-segment.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    // TODO: Response model
    ->label('scope', 'files.read')
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->param('streamId', '', new WhiteList(['hls', 'dash']), 'stream protocol name')
    ->param('renditionId', '', new UID(), 'Rendition unique ID.')
    ->param('segmentId', '', new UID(), 'Segment unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('deviceVideos')
    ->inject('mode')
    ->inject('user')
    ->action(function (string $videoId, string $streamId, string $renditionId, string $segmentId, Response $response, Database $dbForProject, Device $deviceVideos, string $mode, Document $user) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [
            new Query('_uid', Query::TYPE_EQUAL, [$videoId])
        ]));

        if (empty($video)) {
            throw new Exception('Video not found', 404, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);

        $rendition = Authorization::skip(fn() => $dbForProject->findOne('videos_renditions', [
            new Query('_uid', Query::TYPE_EQUAL, [$renditionId]),
            new Query('videoId', Query::TYPE_EQUAL, [$video->getId()]),
            new Query('endedAt', Query::TYPE_GREATER, [0]),
            new Query('status', Query::TYPE_EQUAL, ['ready']),
            new Query('stream', Query::TYPE_EQUAL, [$streamId]),
        ]));

        if (empty($rendition)) {
            throw new Exception('Rendition not found', 404, Exception::VIDEO_RENDITION_NOT_FOUND);
        }

        $segment = Authorization::skip(fn () => $dbForProject->findOne('videos_renditions_segments', [
            new Query('_uid', Query::TYPE_EQUAL, [$segmentId])]));

        if (empty($segment)) {
            throw new Exception('Rendition segments not found', 404, Exception::VIDEO_RENDITION_SEGMENT_NOT_FOUND);
        }

        $output = $deviceVideos->read($segment->getAttribute('path') .  $segment->getAttribute('fileName'));

        if ($streamId === 'hls') {
            $response->setContentType('video/MP2T')->send($output);
        } else {
            $response->setContentType('video/iso.segment')->send($output);
        }
    });


App::get('/v1/videos/:videoId/streams/:streamId/subtitles/:subtitleId')
    ->desc('Get video subtitle')
    ->groups(['api', 'video'])
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getSubtitle')
    ->label('sdk.description', '/docs/references/videos/get-subtitle.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    // TODO: Response model
    ->label('scope', 'files.read')
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->param('streamId', '', new WhiteList(['hls', 'dash']), 'stream protocol name')
    ->param('subtitleId', '', new UID(), 'Subtitle unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('deviceVideos')
    ->inject('mode')
    ->inject('user')
    ->action(function (string $videoId, string $streamId, string $subtitleId, Response $response, Database $dbForProject, Device $deviceVideos, string $mode, Document $user) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [
            new Query('_uid', Query::TYPE_EQUAL, [$videoId])
        ]));

        if (empty($video)) {
            throw new Exception('Video not found', 404, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);

        $subtitle = Authorization::skip(fn() => $dbForProject->findOne('videos_subtitles', [
            new Query('_uid', Query::TYPE_EQUAL, [$subtitleId]),
            new Query('status', Query::TYPE_EQUAL, ['ready'])
        ]));

        if (empty($subtitle)) {
            throw new Exception('subtitle not found', 404, Exception::VIDEO_SUBTITLE_NOT_FOUND);
        }

        if ($streamId == 'hls') {
            $segments = Authorization::skip(fn() => $dbForProject->find('videos_subtitles_segments', [
                new Query('subtitleId', Query::TYPE_EQUAL, [$subtitleId]),
            ], 4000));

            if (empty($segments)) {
                throw new Exception('Subtitle segments not found', 404, Exception::VIDEO_SUBTITLE_SEGMENT_NOT_FOUND);
            }

            $paramsSegments = [];
            foreach ($segments as $segment) {
                $paramsSegments[] = [
                    'duration' => $segment->getAttribute('duration'),
                    'url' => 'http://127.0.0.1/v1/videos/' . $videoId . '/streams/' . $streamId . '/subtitles/' . $subtitleId . '/segments/' . $segment->getId(),
                ];
            }

            $template = new View(__DIR__ . '/../../views/videos/hls-subtitles.phtml');
            $template->setParam('targetDuration', $subtitle->getAttribute('targetDuration'));
            $template->setParam('paramsSegments', $paramsSegments);
            $response->setContentType('application/x-mpegurl')->send($template->render(false));
        } else {
            $output = $deviceVideos->read($deviceVideos->getPath($subtitle->getAttribute('videoId') .  $subtitle->getAttribute('fileId') . '.vtt'));
            $response->setContentType('text/vtt')->send($output);
        }
    });


App::get('/v1/videos/:videoId/streams/:streamId/subtitles/:subtitleId/segments/:segmentId')
    ->desc('Get video subtitle segment')
    ->groups(['api', 'video'])
    ->label('sdk.auth', [APP_AUTH_TYPE_SESSION, APP_AUTH_TYPE_KEY, APP_AUTH_TYPE_JWT])
    ->label('sdk.namespace', 'video')
    ->label('sdk.method', 'getSubtitleSegment')
    ->label('sdk.description', '/docs/references/videos/get-subtitle-segment.md') // TODO: Create markdown
    ->label('sdk.response.code', Response::STATUS_CODE_OK)
    ->label('sdk.response.type', Response::CONTENT_TYPE_JSON)
    // TODO: Response model
    ->label('scope', 'files.read')
    ->param('videoId', null, new UID(), 'Video unique ID.')
    ->param('streamId', '', new WhiteList(['hls', 'dash']), 'stream protocol name')
    ->param('subtitleId', '', new UID(), 'Subtitle unique ID.')
    ->param('segmentId', '', new UID(), 'Segment unique ID.')
    ->inject('response')
    ->inject('dbForProject')
    ->inject('deviceVideos')
    ->inject('mode')
    ->inject('user')
    ->action(function (string $videoId, string $streamId, string $subtitleId, string $segmentId, Response $response, Database $dbForProject, Device $deviceVideos, string $mode, Document $user) {

        $video = Authorization::skip(fn() => $dbForProject->findOne('videos', [
            new Query('_uid', Query::TYPE_EQUAL, [$videoId])
        ]));

        if (empty($video)) {
            throw new Exception('Video not found', 404, Exception::VIDEO_NOT_FOUND);
        }

        validateFilePermissions($dbForProject, $video['bucketId'], $video['fileId'], $mode, $user);

        $subtitle = Authorization::skip(fn() => $dbForProject->findOne('videos_subtitles', [
            new Query('_uid', Query::TYPE_EQUAL, [$subtitleId]),
            new Query('status', Query::TYPE_EQUAL, ['ready'])
        ]));

        if (empty($subtitle)) {
            throw new Exception('subtitle not found', 404, Exception::VIDEO_SUBTITLE_NOT_FOUND);
        }

        $segment = Authorization::skip(fn () => $dbForProject->findOne('videos_subtitles_segments', [
            new Query('_uid', Query::TYPE_EQUAL, [$segmentId])]));

        if (empty($segment)) {
            throw new Exception('Subtitle segments not found', 404, Exception::VIDEO_SUBTITLE_SEGMENT_NOT_FOUND);
        }

        $output = $deviceVideos->read($segment->getAttribute('path') .  $segment->getAttribute('fileName'));
        $response->setContentType('text/vtt')->send($output);
    });
