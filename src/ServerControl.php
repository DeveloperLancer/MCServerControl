<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DevLancer\MCServerControl;

use DevLancer\MCServerControl\Exception\ServerControlException;
use DevLancer\MCServerControl\Exception\ProcessException;
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
     * @var ProcessInterface|Process
     */
    private ProcessInterface $process;

    /**
     * ServerControl constructor.
     * @param SSH2 $ssh
     * @param ProcessInterface|null $process
     * @throws ServerControlException
     */
    public function __construct(SSH2 $ssh, ProcessInterface $process = null)
    {
        $this->ssh = $ssh;
        if (!$this->ssh->isConnected())
            throw new ServerControlException("SSH must be connected");

        $this->process = ($process)? $process : new Process($ssh);
    }

    /**
     * @param LocatorInterface $locator
     * @param int $port
     * @param array $parameters
     * @param string $cmd
     * @return bool
     * @throws ServerControlException
     * @throws ProcessException
     */
    public function start(LocatorInterface $locator, int $port, array $parameters = [], string $cmd = self::CMD_START): bool
    {
        if (!$locator->isFileExist())
            throw new ServerControlException(sprintf("The path %s does not exist", $locator->getPath() . "/" . $locator->getFile()));

        if (preg_match('/(?i)(.*\.jar\z)/', $locator->getFile()) == false)
            throw new ServerControlException(sprintf("The %s file must be of the .jar type", $locator->getFile()));

        if ($this->isRunning($port)) {
            trigger_error(sprintf("The server for port %s is running", $port), E_USER_WARNING);  //todo
            return false;
        }

        $name = sprintf(MCSC_SERVER_NAME, $port);
        $parameters = implode(" ", $parameters);
        $cmd = sprintf($cmd, $locator->getPath(), $name, $parameters, $locator->getFile(), $port);

        if (!$this->terminal($cmd))
            throw new ServerControlException(sprintf("Failed to execute: %s", $cmd));

        if($this->isRunning($port)) //todo check it
            return true;

        trigger_error("The server failed to start", E_USER_WARNING);
        return false;
    }

    /**
     * @param int $port
     * @param string $cmd
     * @return bool
     * @throws ProcessException
     */
    public function isRunning(int $port, string $cmd = self::CMD_IS_RUNNING): bool
    {
        $pid = $this->getPid($port, $cmd);
        return !is_null($pid);
    }

    /**
     * @param int $port
     * @param string $cmd
     * @return bool
     * @throws ServerControlException
     * @throws ProcessException
     */
    public function stop(int $port, string $cmd = self::CMD_STOP): bool
    {
        if (!$this->isRunning($port)) {
            trigger_error(sprintf("The server for port %s is stopped", $port), E_USER_WARNING);  //todo
            return false;
        }

        $name = sprintf(MCSC_SERVER_NAME, $port);
        $cmd = sprintf($cmd, $name);

        if (!$this->terminal($cmd))
            throw new ServerControlException(sprintf("Failed to execute: %s", $cmd));

        if(!$this->isRunning($port)) //todo check it
            return true;

        trigger_error("The server failed to stop", E_USER_WARNING);
        return false;
    }

    /**
     * @param int $port
     * @param int $mode
     * @return bool
     * @throws ServerControlException
     * @throws ProcessException
     */
    public function kill(int $port, int $mode = 9): bool
    {
        $pid = $this->getPid($port);
        if (!$pid || !$pid > 0) {
            trigger_error(sprintf("The server for port %s is stopped", $port), E_USER_WARNING);  //todo
            return false;
        }

        $cmd = "kill -$mode $pid";

        if(!$this->terminal($cmd))
            throw new ServerControlException(sprintf("Failed to execute: %s", $cmd));

        if(!$this->isRunning($port)) //todo check it
            return true;

        trigger_error("The server was not killed", E_USER_WARNING);
        return false;
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
     * @throws ServerControlException
     */
    public function generateExpect(): string
    {
        $read = $this->ssh->read();
        if (!$read)
            throw new ServerControlException("There was no answer");

        $result = explode("\n", $this->ssh->read());
        if ($result == [])
            throw new ServerControlException("Failed to export 'expect'");

        return end($result);
    }

    /**
     * @param string $command
     * @param bool $interactive
     * @return bool
     * @throws ServerControlException
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

    /**
     * @param int $port
     * @param string $cmd
     * @return int|null
     * @throws ProcessException
     */
    public function getPid(int $port, string $cmd = self::CMD_IS_RUNNING): ?int
    {
        $name = sprintf(MCSC_SERVER_NAME, $port);
        $process = $this->process->getByName($name, $cmd);
        if (!$process || !isset($process[MCSC_PROCESS_PID]))
            return null;

        return (int) $process[MCSC_PROCESS_PID];
    }
}