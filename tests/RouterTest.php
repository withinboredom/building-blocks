<?php

namespace Withinboredom\BuildingBlocks\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Withinboredom\BuildingBlocks\Result;
use Withinboredom\BuildingBlocks\Router;
use Withinboredom\ResponseCode\HttpResponseCode;

class RouterTest extends TestCase
{
    public function testSimpleGet()
    {
        $router = new Router('GET', '/');
        $called = false;
        $router->registerRoute('GET', '/', function () use (&$called) {
            $called = true;
            return new Result(HttpResponseCode::NoContent);
        });
        $this->assertEquals(new Result(HttpResponseCode::NoContent), $router->doRouting());
        $this->assertTrue($called);
    }

    public function testMultiGet()
    {
        $router = new Router('GET', '/test/my/space/');
        $called = false;
        $router->registerRoute('GET', '/test/my/space', function () use (&$called) {
            $called = true;
            return new Result(HttpResponseCode::NoContent);
        });
        $router->registerRoute('PUT', '/test/my/other', static fn() => throw new LogicException());
        $this->assertEquals(new Result(HttpResponseCode::NoContent), $router->doRouting());
        $this->assertTrue($called);
    }

    public function testParameters()
    {
        $router = new Router('GET', '/test/user/123');
        $called = false;
        $router->registerRoute('GET', '/test/ex/:id', static fn($id) => throw new LogicException());
        $router->registerRoute('GET', '/test/user/:id', function (string $id) use (&$called) {
            $called = true;
            $this->assertSame('123', $id);
            return new Result(HttpResponseCode::NoContent);
        });
        $router->registerRoute('GET', '/test/user', static fn() => throw new LogicException());
        $router->registerRoute('GET', '/test/site/:id', static fn($id) => throw new LogicException());
        $this->assertEquals(new Result(HttpResponseCode::NoContent), $router->doRouting());
        $this->assertTrue($called);
    }

    public function testMultiple()
    {
        $router = new Router('GET', '/login/test/123');
        $router->registerRoute('GET', '/login/has-credentials', function () {
            $this->assertTrue(false);
            return new Result(HttpResponseCode::NoContent);
        });
        $router->registerRoute('GET', '/login/test/:id', function (string $id) {
            $this->assertTrue(true);
            return new Result(HttpResponseCode::NoContent);
        });
        $this->assertEquals(HttpResponseCode::NoContent, $router->doRouting()->code);
    }
}
