<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\MCServerControl\Tests;

use DevLancer\MCServerControl\Exception\ProcessException;
use DevLancer\MCServerControl\Locator;
use DevLancer\MCServerControl\Process;
use DevLancer\MCServerControl\Exception\ServerControlException;
use DevLancer\MCServerControl\ServerControl;
use phpseclib3\Net\SSH2;
use PHPUnit\Framework\TestCase;

class ServerControlTest extends TestCase
{
    private $ssh;
    private $process;

    public function setUp(): void
    {
        $ssh = $this->createStub(SSH2::class);
        $ssh
            ->method('isConnected')
            ->willReturn(true)
        ;

        $process = $this->createStub(Process::class);

        $this->ssh = $ssh;
        $this->process = $process;
    }

    public function test__constructThrowProcessException()
    {
        $stub = $this->createStub(SSH2::class);
        $stub
            ->method('isConnected')
            ->willReturn(false)
        ;
        $this->expectException(ServerControlException::class);
        (new ServerControl($stub));
    }

    public function testStartPathNotExist()
    {
        $locator = $this->createStub(Locator::class);
        $locator
            ->method('isFileExist')
            ->willReturn(false)
        ;

        $this->expectException(ServerControlException::class);
        $this->expectExceptionMessageMatches('/The path .* does not exist/');

        $serverControl = new ServerControl($this->ssh, $this->process);
        $serverControl->start($locator, 25565);
    }

    public function testStartBadType()
    {
        $locator = $this->createStub(Locator::class);
        $locator
            ->method('isFileExist')
            ->willReturn(true)
        ;

        $locator
            ->method('getFile')
            ->willReturn('server.jarno')
        ;

        $this->expectException(ServerControlException::class);
        $this->expectExceptionMessageMatches('/The .* file must be of the .jar type/');

        $serverControl = new ServerControl($this->ssh, $this->process);
        $serverControl->start($locator, 25565);
    }

    public function testStartFailedExecute()
    {
        $locator = $this->createStub(Locator::class);
        $locator
            ->method('isFileExist')
            ->willReturn(true)
        ;

        $locator
            ->method('getFile')
            ->willReturn('server.jar')
        ;

        $this->expectException(ServerControlException::class);
        $this->expectExceptionMessageMatches('/Failed to execute: .*/');

        $ssh = clone $this->ssh;
        $ssh
            ->method('exec')
            ->willReturn(false)
        ;

        $serverControl = new ServerControl($ssh, $this->process);
        $serverControl->start($locator, 25565);
    }

    public function testIsRunningReturnTrue()
    {
        $process = clone $this->process;
        $process
            ->method('getByName')
            ->willReturn([
                1 => 1
            ])
        ;

        $serverControl = new ServerControl($this->ssh, $process);
        $this->assertTrue($serverControl->isRunning(25565));
    }

    /**
     * @dataProvider isRunningReturnFalseDataProvider
     */
    public function testIsRunningReturnFalse($data)
    {
        $process = clone $this->process;
        $process
            ->method('getByName')
            ->willReturn([
                $data
            ])
        ;

        $serverControl = new ServerControl($this->ssh, $process);
        $this->assertFalse($serverControl->isRunning(25565));
    }

    /**
     * @dataProvider isRunningReturnFalseDataProvider
     */
    public function testStopIsNotRunning($data)
    {
        $process = clone $this->process;
        $process
            ->method('getByName')
            ->willReturn([
                $data
            ])
        ;

        $this->expectWarning();
        $serverControl = new ServerControl($this->ssh, $process);
        $this->assertFalse($serverControl->stop(25565));
    }

    public function isRunningReturnFalseDataProvider(): array
    {
        return [
            [
                null
            ],
            [1 => 0],
            [
                []
            ]
        ];
    }

    public function testStopFailedExecute()
    {
        $process = clone $this->process;
        $process
            ->method('getByName')
            ->willReturn([1=>1])
        ;

        $ssh = clone $this->ssh;
        $ssh
            ->method('exec')
            ->willReturn(false)
        ;

        $this->expectException(ServerControlException::class);
        $this->expectExceptionMessageMatches('/Failed to execute: .*/');

        $serverControl = new ServerControl($ssh, $process);
        $serverControl->stop(25565);
    }

    /**
     * @dataProvider killProvider
     */
    public function testKillBadPid($data)
    {
        $process = clone $this->process;
        $process
            ->method('getByName')
            ->willReturn([
                $data
            ])
        ;

        $this->expectWarning();
        $serverControl = new ServerControl($this->ssh, $process);
        $this->assertFalse($serverControl->kill(25565));
    }

    public function testKillFailedExecute()
    {
        $process = clone $this->process;
        $process
            ->method('getByName')
            ->willReturn([1=>1])
        ;

        $ssh = clone $this->ssh;
        $ssh
            ->method('exec')
            ->willReturn(false)
        ;

        $this->expectException(ServerControlException::class);
        $this->expectExceptionMessageMatches('/Failed to execute: .*/');

        $serverControl = new ServerControl($ssh, $process);
        $serverControl->kill(25565);
    }

    public function killProvider(): array
    {
        return [
            [
                null
            ],
            [
                []
            ],
            [
                0 => 1,
                1 => 0
            ],
            [
                1 => -1
            ],
        ];
    }

    /**
     * @dataProvider generateExpectProvider
     */
    public function testGenerateExpect($data)
    {
        $ssh = clone $this->ssh;
        $ssh
            ->method('read')
            ->willReturn($data)
        ;

        $serverControl = new ServerControl($ssh, $this->process);
        $result = $serverControl->generateExpect();
        $this->assertSame('username@hostname:~$ ', $result);
    }

    public function generateExpectProvider(): array
    {
        return [
            [
            'username@hostname:~$ '],
            ['
test data

;

username@hostname:~$ '],
        ];
    }
}
