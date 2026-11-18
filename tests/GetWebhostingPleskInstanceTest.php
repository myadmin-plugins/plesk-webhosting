<?php

declare(strict_types=1);

namespace Detain\MyAdminPlesk\Tests;

use Detain\MyAdminPlesk\ApiRequestException;
use Detain\MyAdminPlesk\Plesk;
use PHPUnit\Framework\TestCase;

/**
 * get_webhosting_plesk_instance() builds a Plesk client from a website_masters row.
 *
 * A row with no ip or no user:password key used to emit undefined-key warnings, a
 * html_entity_decode(null) deprecation and "Undefined array key 1", then hand back a
 * client aimed at https://:8443 that failed on its first call. It now refuses up front
 * with the ApiRequestException that first call would have thrown.
 */
class GetWebhostingPleskInstanceTest extends TestCase
{
    /** @var mixed */
    private $savedModules;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__.'/../src/get_webhosting_plesk_instance.php';
        $this->savedModules = $GLOBALS['modules'] ?? null;
        $GLOBALS['modules'] = ['webhosting' => ['PREFIX' => 'website', 'TABLE' => 'websites']];
    }

    protected function tearDown(): void
    {
        if ($this->savedModules === null) {
            unset($GLOBALS['modules']);
        } else {
            $GLOBALS['modules'] = $this->savedModules;
        }
        parent::tearDown();
    }

    private function privateProperty(Plesk $plesk, string $name)
    {
        $property = new \ReflectionProperty(Plesk::class, $name);
        $property->setAccessible(true);
        return $property->getValue($plesk);
    }

    public function testBuildsClientFromServerRow(): void
    {
        $plesk = get_webhosting_plesk_instance(['website_name' => 'plesk1.example.com', 'website_ip' => '192.0.2.10', 'website_key' => 'admin:s3cret']);
        $this->assertInstanceOf(Plesk::class, $plesk);
        $this->assertSame('192.0.2.10', $this->privateProperty($plesk, 'host'));
        $this->assertSame('admin', $this->privateProperty($plesk, 'login'));
        $this->assertSame('s3cret', $this->privateProperty($plesk, 'password'));
    }

    public function testPasswordMayContainColonsAndEntities(): void
    {
        $plesk = get_webhosting_plesk_instance(['website_ip' => '192.0.2.10', 'website_key' => 'admin:a:b&amp;c']);
        $this->assertSame('admin', $this->privateProperty($plesk, 'login'));
        $this->assertSame('a:b&c', $this->privateProperty($plesk, 'password'));
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function incompleteRows(): array
    {
        return [
            'empty row' => [[], 'has no ip configured'],
            'no ip' => [['website_name' => 'plesk1.example.com', 'website_key' => 'admin:pw'], 'Plesk server plesk1.example.com has no ip configured'],
            'blank ip' => [['website_ip' => '  ', 'website_key' => 'admin:pw'], 'has no ip configured'],
            'no key' => [['website_ip' => '192.0.2.10'], 'has no user:password key configured'],
            'key without colon' => [['website_ip' => '192.0.2.10', 'website_key' => 'justapassword'], 'has no user:password key configured'],
            'empty user' => [['website_ip' => '192.0.2.10', 'website_key' => ':pw'], 'has no user:password key configured'],
            'empty password' => [['website_ip' => '192.0.2.10', 'website_key' => 'admin:'], 'has no user:password key configured'],
        ];
    }

    /**
     * @dataProvider incompleteRows
     * @param array<string, string> $row
     */
    public function testIncompleteRowIsRefusedWithoutWarnings(array $row, string $message): void
    {
        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage($message);
        // failOnWarning in phpunit.xml.dist turns any undefined-key warning or
        // deprecation raised before the throw into a failure
        get_webhosting_plesk_instance($row);
    }
}
