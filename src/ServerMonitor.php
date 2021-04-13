<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\ServerController;


use DevLancer\ServerController\Exception\FailedExecute;

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

    private ?int $pid = null;
    private ?array $resultProcess = null;

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
        $process = $this->process($pid);

        if (!$process)
            return 0.0;

        return round($process[Process::$processCpu], 2);
    }

    /**
     * @inheritDoc
     * @throws FailedExecute
     */
    public function getMemoryUsage(int $pid): float
    {
        $process = $this->process($pid);

        if (!$process || !isset($process[Process::$processMemory]))
            return 0.0;

        return (float) $process[Process::$processMemory];
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
        if (isset($result[2])) {
            $sec = $result[2];
            $min = $result[1];
            $hour = $result[0];
        } else {
            $sec = $result[1];
            $min = $result[0];
            $hour = "0-0";
        }
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

    /**
     * @param int $pid
     * @return array|null
     * @throws FailedExecute
     */
    public function process(int $pid): ?array
    {
        if ($pid !== $this->pid) {
            $this->pid = $pid;
            $process = $this->process->getByPid($pid);
            $this->resultProcess = $process;
        } else {
            $process = $this->resultProcess;
        }

        return $process;
    }
}