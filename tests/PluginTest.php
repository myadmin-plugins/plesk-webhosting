<?php

declare(strict_types=1);

namespace Detain\MyAdminPlesk\Tests;

use Detain\MyAdminPlesk\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Unit tests for the Plugin class.
 *
 * Tests cover class structure, static properties, hook registration,
 * and event handler method signatures. Network/database dependent code
 * is tested via static analysis only.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    /**
     * Set up reflection for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    // ---------------------------------------------------------------
    // Class structure tests
    // ---------------------------------------------------------------

    /**
     * Verify the Plugin class exists.
     *
     * @return void
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Verify the Plugin class resides in the correct namespace.
     *
     * @return void
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\MyAdminPlesk', $this->reflection->getNamespaceName());
    }

    /**
     * Verify the Plugin class is not abstract.
     *
     * @return void
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Verify Plugin can be instantiated.
     *
     * @return void
     */
    public function testClassIsInstantiable(): void
    {
        $this->assertTrue($this->reflection->isInstantiable());
    }

    // ---------------------------------------------------------------
    // Static property tests
    // ---------------------------------------------------------------

    /**
     * Verify the $name static property is set correctly.
     *
     * @return void
     */
    public function testNameProperty(): void
    {
        $this->assertSame('Plesk Webhosting', Plugin::$name);
    }

    /**
     * Verify the $description static property is a non-empty string.
     *
     * @return void
     */
    public function testDescriptionProperty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
        $this->assertStringContainsString('plesk', strtolower(Plugin::$description));
    }

    /**
     * Verify the $help static property exists.
     *
     * @return void
     */
    public function testHelpProperty(): void
    {
        $this->assertTrue($this->reflection->hasProperty('help'));
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Verify the $module static property is 'webhosting'.
     *
     * @return void
     */
    public function testModuleProperty(): void
    {
        $this->assertSame('webhosting', Plugin::$module);
    }

    /**
     * Verify the $type static property is 'service'.
     *
     * @return void
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Verify all static properties are public.
     *
     * @dataProvider staticPropertyProvider
     * @param string $property
     * @return void
     */
    public function testStaticPropertiesArePublic(string $property): void
    {
        $prop = $this->reflection->getProperty($property);
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
    }

    /**
     * Data provider for static property checks.
     *
     * @return array<string, array{string}>
     */
    public static function staticPropertyProvider(): array
    {
        return [
            'name' => ['name'],
            'description' => ['description'],
            'help' => ['help'],
            'module' => ['module'],
            'type' => ['type'],
        ];
    }

    // ---------------------------------------------------------------
    // getHooks tests
    // ---------------------------------------------------------------

    /**
     * Verify getHooks returns an array.
     *
     * @return void
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Verify getHooks returns the expected number of hooks.
     *
     * @return void
     */
    public function testGetHooksCount(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(6, $hooks);
    }

    /**
     * Verify getHooks contains the settings hook.
     *
     * @return void
     */
    public function testGetHooksContainsSettings(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('webhosting.settings', $hooks);
        $this->assertSame([Plugin::class, 'getSettings'], $hooks['webhosting.settings']);
    }

    /**
     * Verify getHooks contains the activate hook.
     *
     * @return void
     */
    public function testGetHooksContainsActivate(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('webhosting.activate', $hooks);
        $this->assertSame([Plugin::class, 'getActivate'], $hooks['webhosting.activate']);
    }

    /**
     * Verify getHooks contains the reactivate hook.
     *
     * @return void
     */
    public function testGetHooksContainsReactivate(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('webhosting.reactivate', $hooks);
        $this->assertSame([Plugin::class, 'getReactivate'], $hooks['webhosting.reactivate']);
    }

    /**
     * Verify getHooks contains the deactivate hook.
     *
     * @return void
     */
    public function testGetHooksContainsDeactivate(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('webhosting.deactivate', $hooks);
        $this->assertSame([Plugin::class, 'getDeactivate'], $hooks['webhosting.deactivate']);
    }

    /**
     * Verify getHooks contains the terminate hook.
     *
     * @return void
     */
    public function testGetHooksContainsTerminate(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('webhosting.terminate', $hooks);
        $this->assertSame([Plugin::class, 'getTerminate'], $hooks['webhosting.terminate']);
    }

    /**
     * Verify getHooks contains the requirements hook.
     *
     * @return void
     */
    public function testGetHooksContainsRequirements(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('function.requirements', $hooks);
        $this->assertSame([Plugin::class, 'getRequirements'], $hooks['function.requirements']);
    }

    /**
     * Verify all hook callbacks reference callable static methods.
     *
     * @return void
     */
    public function testGetHooksAllCallbacksAreCallable(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $callback) {
            $this->assertIsArray($callback, "Hook {$event} callback should be array");
            $this->assertCount(2, $callback, "Hook {$event} callback should have 2 elements");
            $this->assertTrue(
                $this->reflection->hasMethod($callback[1]),
                "Method {$callback[1]} should exist on Plugin class"
            );
            $method = $this->reflection->getMethod($callback[1]);
            $this->assertTrue($method->isStatic(), "Method {$callback[1]} should be static");
            $this->assertTrue($method->isPublic(), "Method {$callback[1]} should be public");
        }
    }

    /**
     * Verify hook keys use the correct module prefix.
     *
     * @return void
     */
    public function testGetHooksKeysUseModulePrefix(): void
    {
        $hooks = Plugin::getHooks();
        $modulePrefix = Plugin::$module . '.';
        $modulePrefixedCount = 0;
        foreach (array_keys($hooks) as $key) {
            if (strpos($key, $modulePrefix) === 0) {
                $modulePrefixedCount++;
            }
        }
        $this->assertSame(5, $modulePrefixedCount);
    }

    // ---------------------------------------------------------------
    // Event handler method signature tests
    // ---------------------------------------------------------------

    /**
     * Verify event handler methods accept GenericEvent as first parameter.
     *
     * @dataProvider eventHandlerProvider
     * @param string $method
     * @return void
     */
    public function testEventHandlerAcceptsGenericEvent(string $method): void
    {
        $ref = $this->reflection->getMethod($method);
        $params = $ref->getParameters();
        $this->assertGreaterThanOrEqual(1, count($params), "{$method} should accept at least 1 parameter");
        $type = $params[0]->getType();
        $this->assertNotNull($type, "{$method} first parameter should have a type hint");
        $this->assertSame(
            GenericEvent::class,
            $type->getName(),
            "{$method} first parameter should be GenericEvent"
        );
    }

    /**
     * Data provider for event handler methods.
     *
     * @return array<string, array{string}>
     */
    public static function eventHandlerProvider(): array
    {
        return [
            'getActivate' => ['getActivate'],
            'getReactivate' => ['getReactivate'],
            'getDeactivate' => ['getDeactivate'],
            'getTerminate' => ['getTerminate'],
            'getChangeIp' => ['getChangeIp'],
            'getMenu' => ['getMenu'],
            'getRequirements' => ['getRequirements'],
            'getSettings' => ['getSettings'],
        ];
    }

    /**
     * Verify all event handler methods are static.
     *
     * @dataProvider eventHandlerProvider
     * @param string $method
     * @return void
     */
    public function testEventHandlerMethodsAreStatic(string $method): void
    {
        $ref = $this->reflection->getMethod($method);
        $this->assertTrue($ref->isStatic(), "{$method} should be static");
    }

    /**
     * Verify all event handler methods are public.
     *
     * @dataProvider eventHandlerProvider
     * @param string $method
     * @return void
     */
    public function testEventHandlerMethodsArePublic(string $method): void
    {
        $ref = $this->reflection->getMethod($method);
        $this->assertTrue($ref->isPublic(), "{$method} should be public");
    }

    // ---------------------------------------------------------------
    // Constructor tests
    // ---------------------------------------------------------------

    /**
     * Verify the constructor exists and takes no parameters.
     *
     * @return void
     */
    public function testConstructorTakesNoParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Verify Plugin can be instantiated without errors.
     *
     * @return void
     */
    public function testInstantiation(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    // ---------------------------------------------------------------
    // Additional method existence tests
    // ---------------------------------------------------------------

    /**
     * Verify the getChangeIp method exists on the Plugin class.
     *
     * @return void
     */
    public function testGetChangeIpMethodExists(): void
    {
        $this->assertTrue($this->reflection->hasMethod('getChangeIp'));
    }

    /**
     * Verify the getMenu method exists on the Plugin class.
     *
     * @return void
     */
    public function testGetMenuMethodExists(): void
    {
        $this->assertTrue($this->reflection->hasMethod('getMenu'));
    }

    /**
     * Verify getHooks is a static method.
     *
     * @return void
     */
    public function testGetHooksIsStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isStatic());
    }

    /**
     * Verify getHooks return type includes array.
     *
     * @return void
     */
    public function testGetHooksReturnType(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $returnType = $method->getReturnType();
        if ($returnType !== null) {
            $this->assertSame('array', $returnType->getName());
        } else {
            // No return type declared, verify docblock mentions @return array
            $docComment = $method->getDocComment();
            $this->assertStringContainsString('@return array', $docComment);
        }
    }
}
