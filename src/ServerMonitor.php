<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\MCServerController;


use DevLancer\MCServerController\Exception\FailedExecute;

/**
 * Class ServerMonitor
 * @package DevLancer\MCServerControl
 */
class ServerMonitor implements ServerMonitorInterface
{
    const CMD_UPTIME = "ps -p %s -o etime";

    /**
     * @var TerminalInterface
     */
    private TerminalInterface $terminal;

    /**
     * @var ProcessInterface
     */
    private ProcessInterface $process;

    /**
     * ServerMonitor constructor.
     * @param TerminalInterface $terminal
     * @param ProcessInterface|null $process
     */
    public function __construct(TerminalInterface $terminal, ProcessInterface $process = null)
    {
        $this->terminal = $terminal;
        $this->process = ($process)? $process : new Process($terminal);
    }

    /**
     * @inheritDoc
     * @throws FailedExecute
     */
    public function getCpuUsage(int $pid): float
    {
        $process = $this->process->getByPid($pid);
        if (!$process)
            return 0.0;

        return (float) $process[Process::$processCpu];
    }

    /**
     * @inheritDoc
     * @throws FailedExecute
     */
    public function getMemoryUsage(int $pid, int $format = self::FORMAT_PERCENTAGE): float
    {
        $process = $this->process->getByPid($pid);
        if (!$process || !isset($process[Process::$processMemory]))
            return 0.0;

        $usage = (float) $process[Process::$processMemory];

        if ($format == self::FORMAT_PERCENTAGE)
            return $usage;

        if ($usage == 0.0)
            return 0.0;

        $total = $this->getMemory($pid);

        if ($total == 0)
            return 0.0;

        return round($usage * $total / 100, 2);

    }

    /**
     * @inheritDoc
     * @throws FailedExecute
     */
    public function getMemory(int $pid): int
    {
        $process = $this->process->getByPid($pid);
        if (!$process || !isset($process[Process::$processCommand]))
            return 0;

        if (!preg_match('/(?i)(xmx[0-9]+[g,m])/', $process[Process::$processCommand], $memory))
            return 0;

        $memory = str_ireplace("xmx", "", $memory[0]);
        $format = $memory[strlen($memory) - 1];
        $memory = (int) str_replace($format, "", $memory);

        if ("g" == strtolower($format))
            $memory *= 1024;

        return $memory;
    }

    /**
     * @inheritDoc
     * @throws FailedExecute
     */
    public function getUptime(int $pid): int
    {
        $cmd = sprintf(self::CMD_UPTIME, $pid);
        $result = $this->terminal->exec($cmd);
        if (!$result)
            return 0;

        if (!preg_match('/([0-9:-]+)/', $result, $time))
            return 0;

        $result = explode(":", $time[0]);
        $sec = $result[2];
        $min = $result[1];
        $hour = $result[0];
        $day = 0;
        if (strpos($hour, "-") !== false) {
            $result = explode("-", $hour);
            $day = $result[0];
            $hour = $result[1];
        }

        $time = (int) $day * 86400;
        $time += (int) $hour * 3600;
        $time += (int) $min * 60;
        $time += (int) $sec;

        return $time;
    }
}