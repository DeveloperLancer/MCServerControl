<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\MCServerControl;


use DevLancer\MCServerControl\Exception\MachineMonitorException;
use DevLancer\MCServerControl\Exception\ProcessException;
use DevLancer\MCServerControl\Exception\ServerControlException;
use phpseclib3\Net\SSH2;

/**
 * Class MachineMonitor
 * @package DevLancer\MCServerControl
 */
class MachineMonitor implements MachineMonitorInterface
{
    const CMD_MEMORY = "free -m";
    const CMD_CPU = "ps -aux";

    /**
     * @var SSH2
     */
    private SSH2 $ssh;

    private Process $process;

    /**
     * MachineMonitor constructor.
     * @param SSH2 $ssh
     * @param Process|null $process
     * @throws MachineMonitorException
     */
    public function __construct(SSH2 $ssh, Process $process = null)
    {
        $this->ssh = $ssh;
        if (!$this->ssh->isConnected())
            throw new MachineMonitorException("SSH must be connected");

        $this->process = ($process)? $process : new Process($ssh);
    }

    /**
     * @inheritDoc
     */
    public function getCpuUsage(): float
    {
        $result = $this->ssh->exec(self::CMD_CPU);
        if (!$result)
            return 0.0;

        $result = explode("\n", $result);
        $cpu = 0.0;

        foreach ($result as $item) {
            $value = $this->process->explode($item, 10);
            if (!isset($value[MCSC_PROCESS_CPU]))
                continue;

            $cpu += floatval($value[MCSC_PROCESS_CPU]);
        }

        return $cpu;
    }

    /**
     * @inheritDoc
     */
    public function getMemoryUsage(int $format = self::FORMAT_PERCENTAGE): float
    {
        $total = $this->getMemory();
        $usage = $total - $this->getMemoryFree(self::FORMAT_UNITS);
        if ($usage == 0)
            return 0.0;

        if ($format == self::FORMAT_PERCENTAGE)
            return round(($usage * 100) / $total, 2);

        return $usage;
    }

    /**
     * @inheritDoc
     */
    public function getMemory(): int
    {
        return $this->memory(1);
    }

    /**
     * @inheritDoc
     */
    public function getMemoryFree(int $format = self::FORMAT_PERCENTAGE): float
    {
        $free = $this->memory(3);

        if ($free == 0)
            return 0.0;

        if (self::FORMAT_PERCENTAGE == $format)
            return round(($free * 100) / $this->getMemory(), 2);

        return $free;
    }

    /**
     * @param int $type
     * @return int
     */
    private function memory(int $type): int
    {
        $result = $this->ssh->exec(self::CMD_MEMORY);
        if (!$result)
            return 0;

        $result = explode("\n", $result)[1];
        $result = $this->process->explode($result);

        if (!isset($result[$type]))
            return 0;

        return (int) $result[$type];
    }
}