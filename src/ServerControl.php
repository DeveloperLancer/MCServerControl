<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DevLancer\MCServerController;

use DevLancer\MCServerController\Exception\BadFileType;
use DevLancer\MCServerController\Exception\FailedExecute;
use DevLancer\MCServerController\Exception\NotFoundFile;


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
     * @var string
     */
    public static string $serverName = "mcserv%s";

    /**
     * @var ProcessInterface|Process
     */
    private ProcessInterface $process;

    /**
     * @var Terminal
     */
    private Terminal $terminal;

    /**
     * @var bool|null|string
     */
    private $responseTerminal;

    /**
     * ServerControl constructor.
     * @param Terminal $terminal
     * @param ProcessInterface|null $process
     */
    public function __construct(Terminal $terminal, ProcessInterface $process = null)
    {
        $this->terminal = $terminal;
        $this->process = ($process)? $process : new Process($terminal);
    }

    /**
     * @param LocatorInterface $locator
     * @param int $port
     * @param array $parameters
     * @param string $cmd
     * @return bool
     * @throws NotFoundFile
     * @throws FailedExecute
     * @throws BadFileType
     */
    public function start(LocatorInterface $locator, int $port, array $parameters = [], string $cmd = self::CMD_START): bool
    {
        if (!$locator->isFileExist())
            throw new NotFoundFile(sprintf("The path %s does not exist", $locator->getPath() . "/" . $locator->getFile()));

        if (preg_match('/(?i)(.*\.jar\z)/', $locator->getFile()) == false)
            throw new BadFileType(sprintf("The %s file must be of the .jar type", $locator->getFile()));

        if ($this->isRunning($port)) {
            trigger_error(sprintf("The server for port %s is running", $port), E_USER_WARNING);
            return false;
        }

        $name = sprintf(self::$serverName, $port);
        $parameters = implode(" ", $parameters);
        $cmd = sprintf($cmd, $locator->getPath(), $name, $parameters, $locator->getFile(), $port);
        $this->responseTerminal = $this->terminal->exec($cmd);

        if($this->isRunning($port)) //todo check it
            return true;

        trigger_error("The server failed to start", E_USER_WARNING);
        return false;
    }

    /**
     * @param int $port
     * @param string $cmd
     * @return bool
     * @throws FailedExecute
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
     * @throws FailedExecute
     */
    public function stop(int $port, string $cmd = self::CMD_STOP): bool
    {
        if (!$this->isRunning($port)) {
            trigger_error(sprintf("The server for port %s is stopped", $port), E_USER_WARNING);
            return false;
        }

        $name = sprintf(self::$serverName, $port);
        $cmd = sprintf($cmd, $name);

        $this->responseTerminal = $this->terminal->exec($cmd);

        if(!$this->isRunning($port)) //todo check it
            return true;

        trigger_error("The server failed to stop", E_USER_WARNING);
        return false;
    }

    /**
     * @param int $port
     * @param int $mode
     * @return bool
     * @throws FailedExecute
     */
    public function kill(int $port, int $mode = 9): bool
    {
        $pid = $this->getPid($port);
        if (!$pid || !$pid > 0) {
            trigger_error(sprintf("The server for port %s is stopped", $port), E_USER_WARNING);
            return false;
        }

        $cmd = "kill -$mode $pid";

        $this->responseTerminal = $this->terminal->exec($cmd);

        if(!$this->isRunning($port)) //todo check it
            return true;

        trigger_error("The server was not killed", E_USER_WARNING);
        return false;
    }

    /**
     * @param int $port
     * @param string $cmd
     * @return int|null
     * @throws FailedExecute
     */
    public function getPid(int $port, string $cmd = self::CMD_IS_RUNNING): ?int
    {
        $name = sprintf(self::$serverName, $port);
        $process = $this->process->getByName($name, $cmd);
        if (!$process || !isset($process[Process::$processPid]))
            return null;

        return (int) $process[Process::$processPid];
    }

    /**
     * @return TerminalInterface
     */
    public function getTerminal(): TerminalInterface
    {
        return $this->terminal;
    }

    /**
     * @return ProcessInterface
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @return bool|string|null
     */
    public function getResponseTerminal()
    {
        return $this->responseTerminal;
    }
}