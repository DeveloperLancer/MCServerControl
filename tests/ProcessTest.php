<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\MCServerControl\Tests;

use DevLancer\MCServerControl\Exception\ProcessException;
use DevLancer\MCServerControl\Process;
use phpseclib3\Net\SSH2;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    private $ssh;

    public function setUp(): void
    {
        $stub = $this->createStub(SSH2::class);
        $stub
            ->method('isConnected')
            ->willReturn(true)
        ;

        $this->ssh = $stub;
    }

    public function test__constructThrowProcessException()
    {
        $stub = $this->createStub(SSH2::class);
        $stub
            ->method('isConnected')
            ->willReturn(false)
        ;
        $this->expectException(ProcessException::class);
        $process = new Process($stub);
    }

    public function testGetByNameFailedExecute()
    {
        $stub = $this->ssh;

        $stub
            ->method('exec')
            ->willReturn(false);

        $process = new Process($stub);

        $this->expectException(ProcessException::class);
        $process->getByName("mcserv%s");

    }

    public function testGetByNameFindProcess()
    {
        $process = "bubanga  14693  0.0  0.1  40100  3576 pts/0    R+   19:49   0:00 ps -aux";
        $stub = $this->ssh;

        $stub
            ->method('exec')
            ->willReturn($process);

        $process = new Process($stub);

        $result = $process->getByName("ps -aux");
        $this->assertStringContainsString($result[1], "14693");
    }

    public function testGetByNameNotFindProcess()
    {
        $process = "bubanga  14693  0.0  0.1  40100  3576 pts/0    R+   19:49   0:00 ps -ax";
        $stub = $this->ssh;

        $stub
            ->method('exec')
            ->willReturn($process);

        $process = new Process($stub);

        $result = $process->getByName("ps -aux");
        $this->assertNull($result);
    }

    public function testGetByNameBadFormatProcess()
    {
        $process = "bubanga  14693  0.0  0.1  40100  3576 pts/0    R+   19:49   0:00";
        $stub = $this->ssh;

        $stub
            ->method('exec')
            ->willReturn($process);

        $process = new Process($stub);

        $this->expectWarning();
        $result = $process->getByName("ps -aux");
        $this->assertNull($result);
    }

    public function testGetByPidFailedExecute()
    {
        $stub = $this->ssh;

        $stub
            ->method('exec')
            ->willReturn(false);

        $process = new Process($stub);

        $this->expectException(ProcessException::class);
        $process->getByPid(937);

    }

    public function testGetByPidFindProcess()
    {
        $process = "bubanga  14693  0.0  0.1  40100  3576 pts/0    R+   19:49   0:00 ps -aux";
        $stub = $this->ssh;

        $stub
            ->method('exec')
            ->willReturn($process);

        $process = new Process($stub);

        $result = $process->getByPid(14693);
        $this->assertStringContainsString($result[1], "14693");
    }

    public function testGetByPidNotFindProcess()
    {
        $process = "bubanga  1469  0.0  0.1  40100  3576 pts/0    R+   19:49   0:00 ps -ax";
        $stub = $this->ssh;

        $stub
            ->method('exec')
            ->willReturn($process);

        $process = new Process($stub);

        $result = $process->getByPid(14693);
        $this->assertNull($result);
    }

    public function testGetByPidBadFormatProcess()
    {
        $process = "
bubanga  
bubanga  1469  0.0  0.1  40100  3576 pts/0    R+   19:49   0:00 ps -ax
        ";
        $stub = $this->ssh;

        $stub
            ->method('exec')
            ->willReturn($process);

        $process = new Process($stub);

        $this->expectWarning();
        $result = $process->getByPid(14693);
        $this->assertNull($result);
    }

    public function testExplode()
    {
        $val = "bubanga  1469  0.0  0.1  40100  3576 pts/0    R+   19:49   0:00 ps -ax";
        $stub = $this->ssh;
        $process = new Process($stub);
        $result = $process->explode($val, 10);
        $this->assertNotCount(10, $result);
    }
}
