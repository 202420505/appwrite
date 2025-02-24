<?php

namespace Tests\E2E\Services\Proxy;

use Tests\E2E\Client;
use Tests\E2E\Scopes\ProjectCustom;
use Tests\E2E\Scopes\Scope;
use Tests\E2E\Scopes\SideServer;
use Utopia\App;
use Utopia\Database\Query;

class ProxyCustomServerTest extends Scope
{
    use ProxyBase;
    use ProjectCustom;
    use SideServer;

    public function testCreateRule(): void
    {
        $domain = \uniqid() . '-api.myapp.com';
        $rule = $this->createAPIRule($domain);

        $this->assertEquals(201, $rule['headers']['status-code']);
        $this->assertEquals($domain, $rule['body']['domain']);
        $this->assertArrayHasKey('$id', $rule['body']);
        $this->assertArrayHasKey('type', $rule['body']);
        $this->assertArrayHasKey('value', $rule['body']);
        $this->assertArrayHasKey('automation', $rule['body']);
        $this->assertArrayHasKey('status', $rule['body']);
        $this->assertArrayHasKey('logs', $rule['body']);
        $this->assertArrayHasKey('renewAt', $rule['body']);

        $ruleId = $rule['body']['$id'];

        $rule = $this->createAPIRule($domain);
        $this->assertEquals(409, $rule['headers']['status-code']);

        $rule = $this->deleteRule($ruleId);

        $this->assertEquals(204, $rule['headers']['status-code']);
    }

    public function testCreateRuleSetup(): void
    {
        $ruleId = $this->setupAPIRule(\uniqid() . '-api2.myapp.com');
        $this->cleanupRule($ruleId);
    }

    public function testCreateRuleApex(): void
    {
        $rule = $this->createAPIRule('myapp.com');
        $this->assertEquals(400, $rule['headers']['status-code']);
    }

    public function testCreateRuleVcs(): void
    {
        $domain = \uniqid() . '-vcs.myapp.com';

        $rule = $this->createAPIRule('commit-' . $domain);
        $this->assertEquals(400, $rule['headers']['status-code']);

        $rule = $this->createAPIRule('branch-' . $domain);
        $this->assertEquals(400, $rule['headers']['status-code']);

        $rule = $this->createAPIRule('anything-' . $domain);
        $this->assertEquals(201, $rule['headers']['status-code']);
        $this->cleanupRule($rule['body']['$id']);
    }

    public function testCreateAPIRule(): void
    {
        $domain = \uniqid() . '-api.custom.localhost';

        $proxyClient = new Client();
        $proxyClient->setEndpoint('http://' . $domain);

        // We should ideally assert 400, but server allows unknown domains, and serves API by default
        $response = $proxyClient->call(Client::METHOD_GET, '/versions');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals(APP_VERSION_STABLE, $response['body']['server']);

        $ruleId = $this->setupAPIRule($domain);

        $this->assertNotEmpty($ruleId);

        $response = $proxyClient->call(Client::METHOD_GET, '/versions');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals(APP_VERSION_STABLE, $response['body']['server']);

        $this->cleanupRule($ruleId);

        $rule = $this->createAPIRule('http://' . $domain);
        $this->assertEquals(400, $rule['headers']['status-code']);

        $rule = $this->createAPIRule('https://' . $domain);
        $this->assertEquals(400, $rule['headers']['status-code']);

        // Unexpected I would say, but it is the current behaviour
        $rule = $this->createAPIRule('wss://' . $domain);
        $this->assertEquals(201, $rule['headers']['status-code']);
        $this->cleanupRule($rule['body']['$id']);

        // Unexpected I would say, but it is the current behaviour
        $rule = $this->createAPIRule($domain . '/some-path');
        $this->assertEquals(201, $rule['headers']['status-code']);
        $this->cleanupRule($rule['body']['$id']);
    }

    public function testCreateRedirectRule(): void
    {
        $domain = \uniqid() . '-redirect.custom.localhost';

        $proxyClient = new Client();
        $proxyClient->setEndpoint('http://appwrite');
        $proxyClient->addHeader('x-appwrite-hostname', $domain);

        $response = $proxyClient->call(Client::METHOD_GET, '/todos/1');
        $this->assertEquals(404, $response['headers']['status-code']);

        $ruleId = $this->setupRedirectRule($domain, 'jsonplaceholder.typicode.com');
        $this->assertNotEmpty($ruleId);

        $response = $proxyClient->call(Client::METHOD_GET, '/todos/1');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals(1, $response['body']['id']);

        $this->cleanupRule($ruleId);
    }

    public function testCreateFunctionRule(): void
    {
        $domain = \uniqid() . '-function.custom.localhost';

        $proxyClient = new Client();
        $proxyClient->setEndpoint('http://appwrite');
        $proxyClient->addHeader('x-appwrite-hostname', $domain);

        $response = $proxyClient->call(Client::METHOD_GET, '/ping');
        $this->assertEquals(404, $response['headers']['status-code']);

        $functionId = $this->setupFunction();
        $this->assertNotEmpty($functionId);

        $ruleId = $this->setupFunctionRule($domain, $functionId);
        $this->assertNotEmpty($ruleId);

        $response = $proxyClient->call(Client::METHOD_GET, '/ping');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertEquals($functionId, $response['body']['APPWRITE_FUNCTION_ID']);

        $this->cleanupRule($ruleId);
    }

    public function testCreateSiteRule(): void
    {
        $domain = \uniqid() . '-site.custom.localhost';

        $proxyClient = new Client();
        $proxyClient->setEndpoint('http://appwrite');
        $proxyClient->addHeader('x-appwrite-hostname', $domain);

        $response = $proxyClient->call(Client::METHOD_GET, '/contact');
        $this->assertEquals(404, $response['headers']['status-code']);

        $siteId = $this->setupSite();
        $this->assertNotEmpty($siteId);

        $ruleId = $this->setupSiteRule($domain, $siteId);
        $this->assertNotEmpty($ruleId);

        $response = $proxyClient->call(Client::METHOD_GET, '/contact');
        $this->assertEquals(200, $response['headers']['status-code']);
        $this->assertStringContainsString('Contact page', $response['body']);

        $this->cleanupRule($ruleId);
    }

    public function testCreatSiteBranchRule(): void
    {
        $domain = \uniqid() . '-site-branch.custom.localhost';

        $siteId = $this->setupSite();
        $this->assertNotEmpty($siteId);

        $ruleId = $this->setupSiteRule($domain, $siteId, 'dev');
        $this->assertNotEmpty($ruleId);

        $rule = $this->getRule($ruleId);
        $this->assertEquals(200, $rule['headers']['status-code']);
        $this->assertEquals('branch=dev', $rule['body']['automation']);

        $this->cleanupRule($ruleId);
    }

    public function testUpdateRule(): void
    {
        // Create function appwrite-network domain
        $domain = \uniqid() . '-cname-api.' . App::getEnv('_APP_DOMAIN_FUNCTIONS');

        $rule = $this->createAPIRule($domain);
        $this->assertEquals(201, $rule['headers']['status-code']);
        $this->assertEquals('verified', $rule['body']['status']);

        $this->cleanupRule($rule['body']['$id']);

        // Create site appwrite-network domain
        $domain = \uniqid() . '-cname-api.' . App::getEnv('_APP_DOMAIN_SITES');

        $rule = $this->createAPIRule($domain);
        $this->assertEquals(201, $rule['headers']['status-code']);
        $this->assertEquals('verified', $rule['body']['status']);

        $this->cleanupRule($rule['body']['$id']);

        // Create + update
        $domain = \uniqid() . '-cname-api.custom.localhost';

        $rule = $this->createAPIRule($domain);
        $this->assertEquals(201, $rule['headers']['status-code']);
        $this->assertEquals('created', $rule['body']['status']);

        $ruleId = $rule['body']['$id'];

        $rule = $this->updateRuleVerification($ruleId);
        $this->assertEquals(401, $rule['headers']['status-code']);

        $this->cleanupRule($ruleId);
    }

    public function testGetRule()
    {
        $domain = \uniqid() . '-get.custom.localhost';
        $ruleId = $this->setupAPIRule($domain);

        $this->assertNotEmpty($ruleId);

        $rule = $this->getRule($ruleId);
        $this->assertEquals(200, $rule['headers']['status-code']);
        $this->assertEquals($domain, $rule['body']['domain']);
        $this->assertArrayHasKey('$id', $rule['body']);
        $this->assertArrayHasKey('type', $rule['body']);
        $this->assertArrayHasKey('value', $rule['body']);
        $this->assertArrayHasKey('automation', $rule['body']);
        $this->assertArrayHasKey('status', $rule['body']);
        $this->assertArrayHasKey('logs', $rule['body']);
        $this->assertArrayHasKey('renewAt', $rule['body']);

        $this->cleanupRule($ruleId);
    }

    public function testListRules()
    {
        $rules = $this->listRules();
        $this->assertEquals(200, $rules['headers']['status-code']);
        foreach ($rules['body']['rules'] as $rule) {
            $rule = $this->deleteRule($rule['$id']);
            $this->assertEquals(204, $rule['headers']['status-code']);
        }

        $rules = $this->listRules();
        $this->assertEquals(200, $rules['headers']['status-code']);
        $this->assertEquals(0, $rules['body']['total']);
        $this->assertCount(0, $rules['body']['rules']);

        $rule1Domain = \uniqid() . '-list1.custom.localhost';
        $rule1Id = $this->setupAPIRule($rule1Domain);
        $this->assertNotEmpty($rule1Id);

        $rules = $this->listRules();
        $this->assertEquals(200, $rules['headers']['status-code']);
        $this->assertEquals(1, $rules['body']['total']);
        $this->assertCount(1, $rules['body']['rules']);
        $this->assertEquals($rule1Domain, $rules['body']['rules'][0]['domain']);
        $this->assertArrayHasKey('$id', $rules['body']['rules'][0]);
        $this->assertArrayHasKey('type', $rules['body']['rules'][0]);
        $this->assertArrayHasKey('value', $rules['body']['rules'][0]);
        $this->assertArrayHasKey('automation', $rules['body']['rules'][0]);
        $this->assertArrayHasKey('status', $rules['body']['rules'][0]);
        $this->assertArrayHasKey('logs', $rules['body']['rules'][0]);
        $this->assertArrayHasKey('renewAt', $rules['body']['rules'][0]);

        $rule2Domain = \uniqid() . '-list1.custom.localhost';
        $rule2Id = $this->setupAPIRule($rule2Domain);
        $this->assertNotEmpty($rule2Id);

        $rules = $this->listRules();
        $this->assertEquals(200, $rules['headers']['status-code']);
        $this->assertEquals(2, $rules['body']['total']);
        $this->assertCount(2, $rules['body']['rules']);

        $rules = $this->listRules([
            'queries' => [
                Query::limit(1)->toString()
            ]
        ]);
        $this->assertEquals(200, $rules['headers']['status-code']);
        $this->assertEquals(2, $rules['body']['total']);
        $this->assertCount(1, $rules['body']['rules']);

        $rules = $this->listRules([
            'queries' => [
                Query::equal('$id', [$rule1Id])->toString()
            ]
        ]);
        $this->assertEquals(200, $rules['headers']['status-code']);
        $this->assertCount(1, $rules['body']['rules']);
        $this->assertEquals($rule1Domain, $rules['body']['rules'][0]['domain']);

        $rules = $this->listRules([
            'queries' => [
                Query::orderDesc('$id')->toString()
            ]
        ]);
        $this->assertEquals(200, $rules['headers']['status-code']);
        $this->assertCount(2, $rules['body']['rules']);
        $this->assertEquals($rule2Id, $rules['body']['rules'][0]['$id']);


        $rules = $this->listRules([
            'queries' => [
                Query::equal('domain', [$rule2Domain])->toString()
            ]
        ]);
        $this->assertEquals(200, $rules['headers']['status-code']);
        $this->assertCount(1, $rules['body']['rules']);
        $this->assertEquals($rule2Id, $rules['body']['rules'][0]['$id']);
    }
}
