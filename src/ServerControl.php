<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DevLancer\MCServerControl;

use phpseclib3\Net\SSH2;

if (!defined("MCSC_SERVER_NAME"))
    define("MCSC_SERVER_NAME", "mcserv%s");

/**
 * Class ServerControl
 * @package DevLancer\MCServerControl
 */
class ServerControl implements ServerControlInterface
{
    const CMD_IS_RUNNING = Process::CMD_SEARCH;
    const CMD_START = "cd %s; screen -dmS %s java %s -jar %s nogui --port %s";
    const CMD_STOP = "screen -X -S %s quit";

    /**
     * @var SSH2
     */
    private SSH2 $ssh;

    /**
     * @var string|null
     */
    protected ?string $expect = null;

    /**
     * @var bool|string
     */
    private $responseTerminal = false;

    /**
     * ServerControl constructor.
     * @param SSH2 $ssh
     */
    public function __construct(SSH2 $ssh)
    {
        $this->ssh = $ssh;
        if (!$this->ssh->isConnected())
            throw new \RuntimeException("SSH must be connected");
    }

    /**
     * @param string $path absolute path to the server file
     * @param int $port
     * @param array $parameters
     * @param string $cmd
     * @return bool
     */
    public function start(string $path, int $port, array $parameters = [], string $cmd = self::CMD_START): bool
    {
        if (!file_exists($path))
            return false;

        $path = explode("/", $path);
        $file = end($path);
        $path = implode("/", $path);

        if (preg_match('/(?i)(.*\.jar)/', $file) === false)
            return false;

        if ($this->isRunning($port))
            return false;

        $name = sprintf(MCSC_SERVER_NAME, $port);
        $parameters = implode(" ", $parameters);
        $cmd = sprintf($cmd, $path, $name, $parameters, $file, $port);

        if (!$this->terminal($cmd))
            return false;

        return $this->isRunning($port); //todo check it
    }

    /**
     * @param int $port
     * @param string $cmd
     * @return bool
     */
    public function isRunning(int $port, string $cmd = self::CMD_IS_RUNNING): bool
    {
        $name = sprintf(MCSC_SERVER_NAME, $port);
        $process = Process::getByName($this->ssh, $name, $cmd);
        if (!$process || !isset($process[MCSC_PROCESS_PID]))
            return false;

        return (bool) ((int) $process[MCSC_PROCESS_PID] > 0);
    }

    /**
     * @param int $port
     * @param string $cmd
     * @return bool
     */
    public function stop(int $port, string $cmd = self::CMD_STOP): bool
    {
        if (!$this->isRunning($port))
            return false;

        $name = sprintf(MCSC_SERVER_NAME, $port);
        $cmd = sprintf($cmd, $name);

        if (!$this->terminal($cmd))
            return false;

        return !$this->isRunning($port); //todo check it
    }

    /**
     * @param int $port
     * @param int $mode
     * @return bool
     */
    public function kill(int $port, int $mode = 9): bool
    {
        $pid = $this->getPid($port);
        if (!$pid || !$pid > 0)
            return false;

        $cmd = "kill -$mode $pid";

        if(!$this->terminal($cmd))
            return false;

        return !$this->isRunning($port);
    }

    /**
     * @return string|null
     */
    public function getExpect(): ?string
    {
        return $this->expect;
    }

    /**
     * @param string $expect
     */
    public function setExpect(string $expect): void
    {
        $this->expect = $expect;
    }

    /**
     * @return string
     */
    public function generateExpect(): string
    {
        $result = explode("\n", $this->ssh->read());
        return end($result);
    }

    /**
     * @param string $command
     * @param bool $interactive
     * @return bool
     */
    protected function terminal(string $command, bool $interactive = false): bool
    {
        if ($interactive && $this->getExpect() == null)
            $this->setExpect($this->generateExpect());
        
        if (!$interactive) {
            $this->responseTerminal = $this->ssh->exec($command);
            return (bool) $this->responseTerminal;
        }

        $this->ssh->read($this->getExpect());
        $this->ssh->write("$command\n");
        $this->responseTerminal = $this->ssh->read($this->getExpect());
        return (bool) $this->responseTerminal;
    }

    /**
     * @return bool|string
     */
    public function getResponseTerminal()
    {
        return $this->responseTerminal;
    }

    public function getPid(int $port): ?int
    {
        $name = sprintf(MCSC_SERVER_NAME, $port);
        $process = Process::getByName($this->ssh, $name);
        if (!$process || !isset($process[MCSC_PROCESS_PID]))
            return null;

        return (int) $process[MCSC_PROCESS_PID];
    }
}