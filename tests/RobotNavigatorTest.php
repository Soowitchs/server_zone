<?php

namespace RobotNavigatorTest;

require_once __DIR__ . '/../vendor/autoload.php';

use App\RobotNavigator;
use PHPUnit\Framework\TestCase;
use phpmock\MockBuilder;

class RobotNavigatorTest extends TestCase
{
    private RobotNavigator $robot;
    private array $mocks = [];

    private const TEST_ID = 'abc123';
    private const TEST_EMAIL = 'test@test.com';

    protected function tearDown(): void
    {
        foreach ($this->mocks as $mock) {
            $mock->disable();
        }
        $this->mocks = [];
    }

    private function mockRobot(array $methods): RobotNavigator
    {
        return $this->getMockBuilder(RobotNavigator::class)
            ->onlyMethods($methods)
            ->getMock();
    }

    private function assertApiCallMatches(array $expected, $actualMethod, $actualUrl, $actualData): void
    {
        $this->assertSame($expected[0], $actualMethod);
        if ($expected[1] !== null) {
            $this->assertStringContainsString($expected[1], $actualUrl);
        }
        $this->assertEquals($expected[2], $actualData);
    }

    public function testMove(): void
    {
        $this->robot = $this->mockRobot(['apiRequest']);
        $this->robot->expects($this->once())
            ->method('apiRequest')
            ->with('PUT', $this->stringContains('/move'), ['direction' => 'right', 'distance' => 5])
            ->willReturn(['distance' => 5]);

        $distance = $this->robot->move(self::TEST_ID, 'right', 5);
        $this->assertEquals(5, $distance);
    }

    public function testMoveUntilBlocked(): void
    {
        $this->robot = $this->mockRobot(['move']);
        $this->robot->expects($this->exactly(3))
            ->method('move')
            ->with(self::TEST_ID, 'right', 5)
            ->willReturnOnConsecutiveCalls(5, 5, 0);

        $result = $this->robot->moveUntilBlocked(self::TEST_ID, 'right');
        $this->assertEquals(10, $result);
    }

    public function testMoveToCenter(): void
    {
        $this->robot = $this->mockRobot(['move']);
        $call = 0;
        $expected = [
            [self::TEST_ID, 'right', 5],
            [self::TEST_ID, 'right', 2],
            [self::TEST_ID, 'up', 5],
            [self::TEST_ID, 'up', 2],
        ];

        $this->robot->method('move')
            ->willReturnCallback(function ($id, $direction, $distance) use (&$call, $expected) {
                [$expId, $expDir, $expDist] = $expected[$call];
                \PHPUnit\Framework\Assert::assertSame($expId, $id);
                \PHPUnit\Framework\Assert::assertSame($expDir, $direction);
                \PHPUnit\Framework\Assert::assertSame($expDist, $distance);
                $call++;
                return $expDist;
            });

        $this->robot->moveToCenter(self::TEST_ID, 7, 7);
        $this->assertTrue(true);
    }

    public function testMeasureHall(): void
    {
        $this->robot = $this->mockRobot(['moveUntilBlocked']);

        $call = 0;
        $expectedArgs = [
            [self::TEST_ID, 'right'],
            [self::TEST_ID, 'left'],
            [self::TEST_ID, 'up'],
            [self::TEST_ID, 'down'],
        ];
        $returnValues = [6, 4, 3, 5];

        $this->robot->method('moveUntilBlocked')
            ->willReturnCallback(function (...$args) use (&$call, $expectedArgs, $returnValues) {
                \PHPUnit\Framework\Assert::assertSame($expectedArgs[$call][0], $args[0]);
                \PHPUnit\Framework\Assert::assertSame($expectedArgs[$call][1], $args[1]);
                return $returnValues[$call++];
            });

        [$w, $h] = $this->robot->measureHall(self::TEST_ID);
        $this->assertEquals(6, $w);
        $this->assertEquals(5, $h);
    }

    /**
     * @dataProvider startDataProvider
     */
    public function testStart(array $escapeResponse, string $expectedOutput): void
    {
        $this->robot = $this->mockRobot(['apiRequest', 'measureHall', 'moveToCenter']);

        $call = 0;
        $expectedApiCalls = [
            ['POST', null, ['email' => self::TEST_EMAIL]],
            ['PUT', '/escape', ['salary' => 60000]],
        ];
        $returnValues = [
            ['id' => self::TEST_ID],
            $escapeResponse,
        ];

        $this->robot->method('apiRequest')
            ->willReturnCallback(function ($method, $url, $data) use (&$call, $expectedApiCalls, $returnValues) {
                $this->assertApiCallMatches($expectedApiCalls[$call], $method, $url, $data);
                return $returnValues[$call++];
            });

        $this->robot->expects($this->once())
            ->method('measureHall')
            ->with(self::TEST_ID)
            ->willReturn([10, 8]);

        $this->robot->expects($this->once())
            ->method('moveToCenter')
            ->with(self::TEST_ID, 5, 4);

        $this->expectOutputRegex("/$expectedOutput/");
        $this->robot->start();
    }

    public function startDataProvider(): array
    {
        return [
            'successful escape' => [
                'apiResponse' => ['success' => true],
                'expectedOutput' => 'Únik proběhl úspěšně',
            ],
            'failed escape' => [
                'apiResponse' => ['success' => false],
                'expectedOutput' => 'Escape selhal.',
            ],
        ];
    }

    /**
     * @dataProvider apiRequestDataProvider
     */
    public function testApiRequestDataDriven(
        $execReturn,
        int $httpCode,
        string $curlError,
        bool $shouldThrow,
        string $expectedMessagePattern = '',
        array $expectedResponse = []
    ): void {
        $robot = new RobotNavigator('test.com', self::TEST_EMAIL, 1);

        $this->mockCurl('App', $execReturn, $httpCode, $curlError);

        if ($shouldThrow) {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessageMatches("/$expectedMessagePattern/");
        }

        $response = $robot->apiRequest('POST', 'test.com', ['email' => self::TEST_EMAIL]);

        if (!$shouldThrow) {
            $this->assertIsArray($response);
            $this->assertEquals($expectedResponse, $response);
        }
    }

    public function apiRequestDataProvider(): array
    {
        return [
            'successful request' => [
                'execReturn' => json_encode(['id' => 'abc123']),
                'httpCode' => 200,
                'curlError' => '',
                'shouldThrow' => false,
                'expectedMessagePattern' => '',
                'expectedResponse' => ['id' => 'abc123'],
            ],
            '410 error' => [
                'execReturn' => 'irrelevant',
                'httpCode' => 410,
                'curlError' => '',
                'shouldThrow' => true,
                'expectedMessagePattern' => 'Robot vyčerpal energii \(410\)',
                'expectedResponse' => [],
            ],
            'generic 500 error without curl error' => [
                'execReturn' => false,
                'httpCode' => 500,
                'curlError' => '',
                'shouldThrow' => true,
                'expectedMessagePattern' => 'API request failed',
                'expectedResponse' => [],
            ],
        ];
    }

    private function mockCurl(string $ns, $execReturn, int $code, string $error): void
    {
        $curlExec = (new MockBuilder())
            ->setNamespace($ns)
            ->setName('curl_exec')
            ->setFunction(fn($ch) => $execReturn)
            ->build();

        $curlGetinfo = (new MockBuilder())
            ->setNamespace($ns)
            ->setName('curl_getinfo')
            ->setFunction(fn($ch, $opt) => $code)
            ->build();

        $curlError = (new MockBuilder())
            ->setNamespace($ns)
            ->setName('curl_error')
            ->setFunction(fn($ch) => $error)
            ->build();

        foreach ([$curlExec, $curlGetinfo, $curlError] as $mock) {
            $mock->enable();
            $this->mocks[] = $mock;
        }
    }
}
