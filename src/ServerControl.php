<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DevLancer\ServerController;

use DevLancer\ServerController\Exception\FailedExecute;
use DevLancer\ServerController\Exception\NotFoundFile;


/**
 * Class ServerControl
 * @package DevLancer\MCServerControl
 */
class ServerControl implements ServerControlInterface
{
    /**
     * @var string
     */
    public static string $serverName = "serv%s";

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
     */
    public function start(LocatorInterface $locator, int $port, string $cmd, array $parameters = []): bool
    {
        if (!$locator->isFileExist())
            throw new NotFoundFile(sprintf("The path %s does not exist", $locator->getPath() . "/" . $locator->getFile()));

        if ($this->isRunning($port)) {
            trigger_error(sprintf("The server for port %s is running", $port), E_USER_WARNING);
            return false;
        }

        $name = sprintf(self::$serverName, $port);
        $parameters = implode(" ", $parameters);
        $cmd = sprintf($cmd, $locator->getPath(), $name, $parameters, $locator->getFile(), $port);

        $cmd_params = [
            'PATH' => $locator->getPath(),
            'NAME' => $name,
            'PARAMS' => $parameters,
            "FILE" => $locator->getFile(),
            "PORT" => $port
        ];

        foreach ($cmd_params as $key => $val)
            $cmd = str_replace("{$key}", $val, $cmd);

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
    public function isRunning(int $port, string $cmd = Command::IS_RUNNING): bool
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
    public function stop(int $port, string $cmd): bool
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
    public function getPid(int $port, string $cmd = Command::IS_RUNNING): ?int
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