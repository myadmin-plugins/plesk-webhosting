<?php

declare(strict_types=1);

namespace Detain\MyAdminPlesk\Tests;

use Detain\MyAdminPlesk\ApiRequestException;
use Detain\MyAdminPlesk\Plesk;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for the Plesk API client class.
 *
 * Tests cover class structure, pure methods, static helpers,
 * data-returning methods, and XML document builders that do not
 * require a live server connection.
 */
class PleskTest extends TestCase
{
    /**
     * @var Plesk
     */
    private Plesk $plesk;

    /**
     * @var ReflectionClass<Plesk>
     */
    private ReflectionClass $reflection;

    /**
     * Set up a Plesk instance with dummy credentials for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->plesk = new Plesk('test.example.com', 'admin', 'secret');
        $this->reflection = new ReflectionClass(Plesk::class);
    }

    // ---------------------------------------------------------------
    // Class structure tests
    // ---------------------------------------------------------------

    /**
     * Verify the Plesk class exists and is instantiable.
     *
     * @return void
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plesk::class));
    }

    /**
     * Verify the Plesk class resides in the correct namespace.
     *
     * @return void
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\MyAdminPlesk', $this->reflection->getNamespaceName());
    }

    /**
     * Verify the constructor sets private host property.
     *
     * @return void
     */
    public function testConstructorSetsHost(): void
    {
        $prop = $this->reflection->getProperty('host');
        $prop->setAccessible(true);
        $this->assertSame('test.example.com', $prop->getValue($this->plesk));
    }

    /**
     * Verify the constructor sets private login property.
     *
     * @return void
     */
    public function testConstructorSetsLogin(): void
    {
        $prop = $this->reflection->getProperty('login');
        $prop->setAccessible(true);
        $this->assertSame('admin', $prop->getValue($this->plesk));
    }

    /**
     * Verify the constructor sets private password property.
     *
     * @return void
     */
    public function testConstructorSetsPassword(): void
    {
        $prop = $this->reflection->getProperty('password');
        $prop->setAccessible(true);
        $this->assertSame('secret', $prop->getValue($this->plesk));
    }

    /**
     * Verify the debug property defaults to false.
     *
     * @return void
     */
    public function testDebugDefaultsFalse(): void
    {
        $this->assertFalse($this->plesk->debug);
    }

    /**
     * Verify expected public properties exist.
     *
     * @return void
     */
    public function testPublicPropertiesExist(): void
    {
        $this->assertTrue($this->reflection->hasProperty('curl'));
        $this->assertTrue($this->reflection->hasProperty('packet'));
        $this->assertTrue($this->reflection->hasProperty('debug'));
    }

    /**
     * Verify expected private properties exist.
     *
     * @return void
     */
    public function testPrivatePropertiesExist(): void
    {
        $host = $this->reflection->getProperty('host');
        $login = $this->reflection->getProperty('login');
        $password = $this->reflection->getProperty('password');

        $this->assertTrue($host->isPrivate());
        $this->assertTrue($login->isPrivate());
        $this->assertTrue($password->isPrivate());
    }

    // ---------------------------------------------------------------
    // Method existence tests
    // ---------------------------------------------------------------

    /**
     * Verify all expected public methods exist on the Plesk class.
     *
     * @dataProvider publicMethodProvider
     * @param string $method
     * @return void
     */
    public function testPublicMethodExists(string $method): void
    {
        $this->assertTrue(
            $this->reflection->hasMethod($method),
            "Expected public method {$method} not found on Plesk class"
        );
    }

    /**
     * Data provider for public method existence checks.
     *
     * @return array<string, array{string}>
     */
    public static function publicMethodProvider(): array
    {
        return [
            'updateCurl' => ['updateCurl'],
            'curlInit' => ['curlInit'],
            'sendRequest' => ['sendRequest'],
            'parseResponse' => ['parseResponse'],
            'checkResponse' => ['checkResponse'],
            'getErrorCodes' => ['getErrorCodes'],
            'getError' => ['getError'],
            'randomString' => ['randomString'],
            'createWebUser' => ['createWebUser'],
            'createCustomer' => ['createCustomer'],
            'createMailAccount' => ['createMailAccount'],
            'getServerInfoTypes' => ['getServerInfoTypes'],
            'varExport' => ['varExport'],
            'fixResult' => ['fixResult'],
            'getObjectStatusList' => ['getObjectStatusList'],
            'getSiteFilters' => ['getSiteFilters'],
            'getSiteDatasets' => ['getSiteDatasets'],
            'getSiteGenSetups' => ['getSiteGenSetups'],
            'getHtypes' => ['getHtypes'],
            'getSubscriptionFilters' => ['getSubscriptionFilters'],
            'getSubscriptionDatasets' => ['getSubscriptionDatasets'],
            'createClient' => ['createClient'],
            'deleteClient' => ['deleteClient'],
            'getClient' => ['getClient'],
            'updateClient' => ['updateClient'],
            'listClients' => ['listClients'],
            'createSubscription' => ['createSubscription'],
            'deleteSubscription' => ['deleteSubscription'],
            'listSubscriptions' => ['listSubscriptions'],
            'getSubscription' => ['getSubscription'],
            'createSite' => ['createSite'],
            'updateSite' => ['updateSite'],
            'deleteSite' => ['deleteSite'],
            'getSites' => ['getSites'],
            'getSite' => ['getSite'],
            'listSites' => ['listSites'],
            'listIpAddresses' => ['listIpAddresses'],
            'listServicePlans' => ['listServicePlans'],
            'listUsers' => ['listUsers'],
            'getTraffic' => ['getTraffic'],
            'getCustomers' => ['getCustomers'],
            'getWebspaces' => ['getWebspaces'],
            'listDatabaseServers' => ['listDatabaseServers'],
        ];
    }

    /**
     * Verify randomString is a static method.
     *
     * @return void
     */
    public function testRandomStringIsStatic(): void
    {
        $method = $this->reflection->getMethod('randomString');
        $this->assertTrue($method->isStatic());
    }

    // ---------------------------------------------------------------
    // Pure method tests: randomString
    // ---------------------------------------------------------------

    /**
     * Verify randomString returns a string of the default length (8).
     *
     * @return void
     */
    public function testRandomStringDefaultLength(): void
    {
        $result = Plesk::randomString();
        $this->assertSame(8, mb_strlen($result));
    }

    /**
     * Verify randomString returns a string of a custom length.
     *
     * @return void
     */
    public function testRandomStringCustomLength(): void
    {
        $result = Plesk::randomString(16);
        $this->assertSame(16, mb_strlen($result));
    }

    /**
     * Verify randomString returns only alphabetic characters.
     *
     * @return void
     */
    public function testRandomStringContainsOnlyAlpha(): void
    {
        $result = Plesk::randomString(50);
        $this->assertMatchesRegularExpression('/^[a-zA-Z]+$/', $result);
    }

    /**
     * Verify randomString with length 1 returns a single character.
     *
     * @return void
     */
    public function testRandomStringLengthOne(): void
    {
        $result = Plesk::randomString(1);
        $this->assertSame(1, mb_strlen($result));
        $this->assertMatchesRegularExpression('/^[a-zA-Z]$/', $result);
    }

    /**
     * Verify randomString returns different values on subsequent calls.
     *
     * @return void
     */
    public function testRandomStringIsRandom(): void
    {
        $results = [];
        for ($i = 0; $i < 20; $i++) {
            $results[] = Plesk::randomString(8);
        }
        $this->assertGreaterThan(1, count(array_unique($results)));
    }

    // ---------------------------------------------------------------
    // Pure method tests: getErrorCodes
    // ---------------------------------------------------------------

    /**
     * Verify getErrorCodes returns a non-empty array.
     *
     * @return void
     */
    public function testGetErrorCodesReturnsArray(): void
    {
        $codes = $this->plesk->getErrorCodes();
        $this->assertIsArray($codes);
        $this->assertNotEmpty($codes);
    }

    /**
     * Verify getErrorCodes contains known error code 1001.
     *
     * @return void
     */
    public function testGetErrorCodesContains1001(): void
    {
        $codes = $this->plesk->getErrorCodes();
        $this->assertArrayHasKey(1001, $codes);
    }

    /**
     * Verify getErrorCodes contains known error code 1007.
     *
     * @return void
     */
    public function testGetErrorCodesContains1007(): void
    {
        $codes = $this->plesk->getErrorCodes();
        $this->assertArrayHasKey(1007, $codes);
        $this->assertStringContainsString('already exists', $codes[1007]);
    }

    /**
     * Verify all error code keys are integers and values are strings.
     *
     * @return void
     */
    public function testGetErrorCodesTypes(): void
    {
        $codes = $this->plesk->getErrorCodes();
        foreach ($codes as $key => $value) {
            $this->assertIsInt($key);
            $this->assertIsString($value);
        }
    }

    // ---------------------------------------------------------------
    // Pure method tests: getError
    // ---------------------------------------------------------------

    /**
     * Verify getError returns the correct message for a known code.
     *
     * @return void
     */
    public function testGetErrorReturnsCorrectMessage(): void
    {
        $msg = $this->plesk->getError(1001);
        $this->assertStringContainsString('Authentication failed', $msg);
    }

    // ---------------------------------------------------------------
    // Pure method tests: getServerInfoTypes
    // ---------------------------------------------------------------

    /**
     * Verify getServerInfoTypes returns an array with expected keys.
     *
     * @return void
     */
    public function testGetServerInfoTypesReturnsArray(): void
    {
        $types = $this->plesk->getServerInfoTypes();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('key', $types);
        $this->assertArrayHasKey('gen_info', $types);
        $this->assertArrayHasKey('components', $types);
        $this->assertArrayHasKey('stat', $types);
        $this->assertArrayHasKey('admin', $types);
        $this->assertArrayHasKey('interfaces', $types);
        $this->assertArrayHasKey('services_state', $types);
        $this->assertArrayHasKey('certificates', $types);
    }

    /**
     * Verify all getServerInfoTypes values are strings.
     *
     * @return void
     */
    public function testGetServerInfoTypesValuesAreStrings(): void
    {
        $types = $this->plesk->getServerInfoTypes();
        foreach ($types as $value) {
            $this->assertIsString($value);
        }
    }

    // ---------------------------------------------------------------
    // Pure method tests: getObjectStatusList
    // ---------------------------------------------------------------

    /**
     * Verify getObjectStatusList returns expected statuses.
     *
     * @return void
     */
    public function testGetObjectStatusListReturnsExpectedStatuses(): void
    {
        $statuses = $this->plesk->getObjectStatusList();
        $this->assertIsArray($statuses);
        $this->assertArrayHasKey('0', $statuses);
        $this->assertSame('active', $statuses['0']);
        $this->assertArrayHasKey('16', $statuses);
        $this->assertArrayHasKey('256', $statuses);
    }

    // ---------------------------------------------------------------
    // Pure method tests: getSiteFilters
    // ---------------------------------------------------------------

    /**
     * Verify getSiteFilters returns an array of strings containing expected values.
     *
     * @return void
     */
    public function testGetSiteFiltersContainsExpected(): void
    {
        $filters = $this->plesk->getSiteFilters();
        $this->assertIsArray($filters);
        $this->assertContains('id', $filters);
        $this->assertContains('name', $filters);
        $this->assertContains('parent-id', $filters);
    }

    // ---------------------------------------------------------------
    // Pure method tests: getSiteDatasets
    // ---------------------------------------------------------------

    /**
     * Verify getSiteDatasets returns expected dataset fields.
     *
     * @return void
     */
    public function testGetSiteDatasetsContainsExpected(): void
    {
        $datasets = $this->plesk->getSiteDatasets();
        $this->assertIsArray($datasets);
        $this->assertContains('gen_info', $datasets);
        $this->assertContains('hosting', $datasets);
        $this->assertContains('stat', $datasets);
    }

    // ---------------------------------------------------------------
    // Pure method tests: getSiteGenSetups
    // ---------------------------------------------------------------

    /**
     * Verify getSiteGenSetups returns expected gen_setup fields.
     *
     * @return void
     */
    public function testGetSiteGenSetupsContainsExpected(): void
    {
        $setups = $this->plesk->getSiteGenSetups();
        $this->assertIsArray($setups);
        $this->assertContains('name', $setups);
        $this->assertContains('htype', $setups);
        $this->assertContains('status', $setups);
        $this->assertContains('webspace-id', $setups);
    }

    // ---------------------------------------------------------------
    // Pure method tests: getHtypes
    // ---------------------------------------------------------------

    /**
     * Verify getHtypes returns the expected hosting type values.
     *
     * @return void
     */
    public function testGetHtypesReturnsExpected(): void
    {
        $htypes = $this->plesk->getHtypes();
        $this->assertSame(['vrt_hst', 'std_fwd', 'frm_fwd', 'none'], $htypes);
    }

    // ---------------------------------------------------------------
    // Pure method tests: getSubscriptionFilters
    // ---------------------------------------------------------------

    /**
     * Verify getSubscriptionFilters returns expected filter fields.
     *
     * @return void
     */
    public function testGetSubscriptionFiltersContainsExpected(): void
    {
        $filters = $this->plesk->getSubscriptionFilters();
        $this->assertIsArray($filters);
        $this->assertContains('id', $filters);
        $this->assertContains('owner-id', $filters);
        $this->assertContains('name', $filters);
        $this->assertContains('owner-login', $filters);
    }

    // ---------------------------------------------------------------
    // Pure method tests: getSubscriptionDatasets
    // ---------------------------------------------------------------

    /**
     * Verify getSubscriptionDatasets returns expected dataset fields.
     *
     * @return void
     */
    public function testGetSubscriptionDatasetsContainsExpected(): void
    {
        $datasets = $this->plesk->getSubscriptionDatasets();
        $this->assertIsArray($datasets);
        $this->assertContains('gen_info', $datasets);
        $this->assertContains('hosting', $datasets);
        $this->assertContains('limits', $datasets);
        $this->assertContains('stat', $datasets);
        $this->assertContains('permissions', $datasets);
    }

    // ---------------------------------------------------------------
    // Pure method tests: varExport
    // ---------------------------------------------------------------

    /**
     * Verify varExport converts arrays to shorthand notation.
     *
     * @return void
     */
    public function testVarExportConvertsToShorthandArrays(): void
    {
        $input = ['key' => 'value', 'nested' => ['a', 'b']];
        $result = $this->plesk->varExport($input);
        $this->assertIsString($result);
        $this->assertStringContainsString("'key'", $result);
        $this->assertStringContainsString("'value'", $result);
    }

    /**
     * Verify varExport handles empty arrays.
     *
     * @return void
     */
    public function testVarExportHandlesEmptyArray(): void
    {
        $result = $this->plesk->varExport([]);
        $this->assertIsString($result);
    }

    // ---------------------------------------------------------------
    // Pure method tests: fixResult
    // ---------------------------------------------------------------

    /**
     * Verify fixResult compacts name/value pairs.
     *
     * @return void
     */
    public function testFixResultCompactsNameValuePairs(): void
    {
        $input = [
            ['name' => 'hostname', 'value' => 'server1.example.com'],
            ['name' => 'ip', 'value' => '10.0.0.1'],
        ];
        $result = $this->plesk->fixResult($input);
        $this->assertArrayHasKey('hostname', $result);
        $this->assertSame('server1.example.com', $result['hostname']);
        $this->assertArrayHasKey('ip', $result);
        $this->assertSame('10.0.0.1', $result['ip']);
    }

    /**
     * Verify fixResult compacts name/version pairs.
     *
     * @return void
     */
    public function testFixResultCompactsNameVersionPairs(): void
    {
        $input = [
            ['name' => 'php', 'version' => '8.2.0'],
            ['name' => 'mysql', 'version' => '8.0.30'],
        ];
        $result = $this->plesk->fixResult($input);
        $this->assertArrayHasKey('php', $result);
        $this->assertSame('8.2.0', $result['php']);
        $this->assertArrayHasKey('mysql', $result);
        $this->assertSame('8.0.30', $result['mysql']);
    }

    /**
     * Verify fixResult handles nested arrays recursively.
     *
     * @return void
     */
    public function testFixResultHandlesNestedArrays(): void
    {
        $input = [
            'server' => [
                ['name' => 'os', 'value' => 'linux'],
            ],
        ];
        $result = $this->plesk->fixResult($input);
        $this->assertArrayHasKey('server', $result);
        $this->assertArrayHasKey('os', $result['server']);
        $this->assertSame('linux', $result['server']['os']);
    }

    /**
     * Verify fixResult returns non-array input unchanged.
     *
     * @return void
     */
    public function testFixResultReturnsNonArrayUnchanged(): void
    {
        $this->assertSame('hello', $this->plesk->fixResult('hello'));
        $this->assertSame(42, $this->plesk->fixResult(42));
        $this->assertNull($this->plesk->fixResult(null));
    }

    /**
     * Verify fixResult does not modify arrays that are not name/value or name/version.
     *
     * @return void
     */
    public function testFixResultSkipsNonMatchingArrays(): void
    {
        $input = [
            ['id' => 1, 'status' => 'ok'],
            ['id' => 2, 'status' => 'error'],
        ];
        $result = $this->plesk->fixResult($input);
        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
    }

    // ---------------------------------------------------------------
    // XML builder tests: createWebUser
    // ---------------------------------------------------------------

    /**
     * Verify createWebUser returns a DOMDocument with correct structure.
     *
     * @return void
     */
    public function testCreateWebUserReturnsDomDocument(): void
    {
        $params = [
            'site-id' => 1,
            'login' => 'testuser',
            'password' => 'testpass',
        ];
        $result = $this->plesk->createWebUser($params);
        $this->assertInstanceOf(\DOMDocument::class, $result);
        $xml = $result->saveXML();
        $this->assertStringContainsString('<webuser>', $xml);
        $this->assertStringContainsString('<add>', $xml);
        $this->assertStringContainsString('<login>testuser</login>', $xml);
        $this->assertStringContainsString('<password>testpass</password>', $xml);
        $this->assertStringContainsString('<site-id>1</site-id>', $xml);
        $this->assertStringContainsString('<ftp-quota>100</ftp-quota>', $xml);
    }

    // ---------------------------------------------------------------
    // XML builder tests: createCustomer
    // ---------------------------------------------------------------

    /**
     * Verify createCustomer returns a DOMDocument with correct gen_info fields.
     *
     * @return void
     */
    public function testCreateCustomerReturnsDomDocument(): void
    {
        $data = [
            'name' => 'John Doe',
            'company' => 'ACME Inc',
            'account_lid' => 'john@example.com',
            'zip' => '12345',
        ];
        $result = $this->plesk->createCustomer('jdoe', 'password123', $data);
        $this->assertInstanceOf(\DOMDocument::class, $result);
        $xml = $result->saveXML();
        $this->assertStringContainsString('<customer>', $xml);
        $this->assertStringContainsString('<add>', $xml);
        $this->assertStringContainsString('<gen_info>', $xml);
        $this->assertStringContainsString('<login>jdoe</login>', $xml);
        $this->assertStringContainsString('<passwd>password123</passwd>', $xml);
    }

    // ---------------------------------------------------------------
    // XML builder tests: createMailAccount
    // ---------------------------------------------------------------

    /**
     * Verify createMailAccount returns a DOMDocument with mail structure.
     *
     * @return void
     */
    public function testCreateMailAccountReturnsDomDocument(): void
    {
        $params = [
            'site-id' => 1,
            'mailname' => 'info',
            'password' => 'mailpass',
            'password-type' => 'plain',
        ];
        $result = $this->plesk->createMailAccount($params);
        $this->assertInstanceOf(\DOMDocument::class, $result);
        $xml = $result->saveXML();
        $this->assertStringContainsString('<mail>', $xml);
        $this->assertStringContainsString('<create>', $xml);
        $this->assertStringContainsString('<name>info</name>', $xml);
        $this->assertStringContainsString('<value>mailpass</value>', $xml);
    }

    // ---------------------------------------------------------------
    // XML builder tests: createSite2
    // ---------------------------------------------------------------

    /**
     * Verify createSite2 returns a DOMDocument with site structure.
     *
     * @return void
     */
    public function testCreateSite2ReturnsDomDocument(): void
    {
        $params = [
            'name' => 'example.com',
            'webspace-id' => 5,
        ];
        $result = $this->plesk->createSite2($params);
        $this->assertInstanceOf(\DOMDocument::class, $result);
        $xml = $result->saveXML();
        $this->assertStringContainsString('<site>', $xml);
        $this->assertStringContainsString('<add>', $xml);
        $this->assertStringContainsString('<gen_setup>', $xml);
        $this->assertStringContainsString('<name>example.com</name>', $xml);
        $this->assertStringContainsString('<webspace-id>5</webspace-id>', $xml);
        $this->assertStringContainsString('<vrt_hst>', $xml);
    }

    // ---------------------------------------------------------------
    // parseResponse tests
    // ---------------------------------------------------------------

    /**
     * Verify parseResponse returns a SimpleXMLElement for valid XML.
     *
     * @return void
     */
    public function testParseResponseReturnsSimpleXmlElement(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><packet><system><status>ok</status></system></packet>';
        $result = $this->plesk->parseResponse($xml);
        $this->assertInstanceOf(\SimpleXMLElement::class, $result);
    }

    /**
     * Verify parseResponse throws on invalid XML input.
     *
     * The underlying code calls myadmin_log() which may not exist in
     * the test environment, so we accept either ApiRequestException
     * or Error (from the undefined function call).
     *
     * @return void
     */
    public function testParseResponseThrowsOnInvalidXml(): void
    {
        $thrown = false;
        try {
            $this->plesk->parseResponse('not xml at all <<<');
        } catch (ApiRequestException $e) {
            $thrown = true;
        } catch (\Error $e) {
            // myadmin_log() undefined in test context; this is expected
            $thrown = true;
        }
        $this->assertTrue($thrown, 'parseResponse should throw on invalid XML');
    }

    // ---------------------------------------------------------------
    // checkResponse tests
    // ---------------------------------------------------------------

    /**
     * Verify checkResponse throws ApiRequestException on error status.
     *
     * @return void
     */
    public function testCheckResponseThrowsOnError(): void
    {
        $xml = simplexml_load_string(
            '<packet><domain><get><result><status>error</status><result><errtext>Test error</errtext></result></result></get></domain></packet>'
        );
        $this->expectException(ApiRequestException::class);
        $this->plesk->checkResponse($xml);
    }

    /**
     * Verify checkResponse returns the result node on success.
     *
     * @return void
     */
    public function testCheckResponseReturnsResultOnSuccess(): void
    {
        $xml = simplexml_load_string(
            '<packet><domain><get><result><status>ok</status><result><errtext></errtext></result></result></get></domain></packet>'
        );
        $result = $this->plesk->checkResponse($xml);
        $this->assertSame('ok', (string) $result->status);
    }

    // ---------------------------------------------------------------
    // Constructor parameter tests
    // ---------------------------------------------------------------

    /**
     * Verify the constructor accepts three string parameters.
     *
     * @return void
     */
    public function testConstructorParameterCount(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(3, $constructor->getParameters());
    }

    /**
     * Verify the constructor parameter names are correct.
     *
     * @return void
     */
    public function testConstructorParameterNames(): void
    {
        $constructor = $this->reflection->getConstructor();
        $params = $constructor->getParameters();
        $this->assertSame('host', $params[0]->getName());
        $this->assertSame('login', $params[1]->getName());
        $this->assertSame('password', $params[2]->getName());
    }

    // ---------------------------------------------------------------
    // Alias method tests
    // ---------------------------------------------------------------

    /**
     * Verify getSite is a public alias method.
     *
     * @return void
     */
    public function testGetSiteMethodExists(): void
    {
        $method = $this->reflection->getMethod('getSite');
        $this->assertTrue($method->isPublic());
    }

    /**
     * Verify getSubscription is a public alias method.
     *
     * @return void
     */
    public function testGetSubscriptionMethodExists(): void
    {
        $method = $this->reflection->getMethod('getSubscription');
        $this->assertTrue($method->isPublic());
    }

    /**
     * Verify listSites is a public alias method.
     *
     * @return void
     */
    public function testListSitesMethodExists(): void
    {
        $method = $this->reflection->getMethod('listSites');
        $this->assertTrue($method->isPublic());
    }

    // ---------------------------------------------------------------
    // Static analysis: network methods
    // ---------------------------------------------------------------

    /**
     * Verify methods that require network I/O exist and are public.
     *
     * @dataProvider networkMethodProvider
     * @param string $method
     * @return void
     */
    public function testNetworkMethodIsPublic(string $method): void
    {
        $ref = $this->reflection->getMethod($method);
        $this->assertTrue($ref->isPublic());
    }

    /**
     * Data provider for methods that perform network operations.
     *
     * @return array<string, array{string}>
     */
    public static function networkMethodProvider(): array
    {
        return [
            'getServerInfo' => ['getServerInfo'],
            'createSession' => ['createSession'],
            'getCustomers' => ['getCustomers'],
            'getWebspaces' => ['getWebspaces'],
            'createClient' => ['createClient'],
            'deleteClient' => ['deleteClient'],
            'getClient' => ['getClient'],
            'updateClient' => ['updateClient'],
            'listClients' => ['listClients'],
            'listUsers' => ['listUsers'],
            'createSubscription' => ['createSubscription'],
            'deleteSubscription' => ['deleteSubscription'],
            'listSubscriptions' => ['listSubscriptions'],
            'createSite' => ['createSite'],
            'updateSite' => ['updateSite'],
            'deleteSite' => ['deleteSite'],
            'getSites' => ['getSites'],
            'listIpAddresses' => ['listIpAddresses'],
            'listServicePlans' => ['listServicePlans'],
            'listDatabaseServers' => ['listDatabaseServers'],
            'getTraffic' => ['getTraffic'],
        ];
    }

    // ---------------------------------------------------------------
    // Edge case tests
    // ---------------------------------------------------------------

    /**
     * Verify fixResult with an empty array returns an empty array.
     *
     * @return void
     */
    public function testFixResultEmptyArray(): void
    {
        $this->assertSame([], $this->plesk->fixResult([]));
    }

    /**
     * Verify fixResult with mixed numeric and string keys.
     *
     * @return void
     */
    public function testFixResultMixedKeys(): void
    {
        $input = [
            'keep' => 'this',
            0 => ['name' => 'test', 'value' => '123'],
        ];
        $result = $this->plesk->fixResult($input);
        $this->assertArrayHasKey('keep', $result);
        $this->assertArrayHasKey('test', $result);
        $this->assertSame('123', $result['test']);
    }

    /**
     * Verify randomString with length 0 returns empty string.
     *
     * @return void
     */
    public function testRandomStringLengthZero(): void
    {
        $result = Plesk::randomString(0);
        $this->assertSame('', $result);
    }
}
