<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\MCServerController\Tests;

use DevLancer\MCServerController\Process;
use DevLancer\MCServerController\Terminal;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{

    /**
     * @dataProvider processProvider
     */
    public function testGetByPid($data)
    {
        $terminal = $this->createStub(Terminal::class);
        $terminal
            ->method("exec")
            ->willReturn($data)
        ;

        $process = new Process($terminal);
        $result = $process->getByPid(937);
        $this->assertSame(937, (int) $result[Process::$processPid]);
    }

    /**
     * @dataProvider processProvider
     */
    public function testGetByName($data)
    {
        $terminal = $this->createStub(Terminal::class);
        $terminal
            ->method("exec")
            ->willReturn($data)
        ;

        $process = new Process($terminal);
        $result = $process->getByName("mcserv25565");
        $this->assertSame(937, (int) $result[Process::$processPid]);
    }

    public function processProvider(): array
    {
        $process = "bubanga  937  0.0  0.1  40100  3576 pts/0    R+   19:49   0:00  cd /path/to; screen -dmS mcserv25565 java -Xmx4G -Xms2G -jar server.jar nogui --port 25565";

        return [
          [
              $process
          ]
        ];
    }
}
