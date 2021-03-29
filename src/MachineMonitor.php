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
 * Class MachineMonitor
 * @package DevLancer\MCServerControl
 */
class MachineMonitor implements MachineMonitorInterface
{
    const CMD_MEMORY = "free -m";
    const CMD_CPU = "ps -aux";

    /**
     * @var TerminalInterface
     */
    private TerminalInterface $terminal;

    /**
     * @var Process
     */
    private Process $process;

    /**
     * MachineMonitor constructor.
     * @param TerminalInterface $terminal
     * @param Process|null $process
     */
    public function __construct(TerminalInterface $terminal, Process $process = null)
    {
        $this->terminal = $terminal;
        $this->process = ($process)? $process : new Process($terminal);
    }

    /**
     * @inheritDoc
     * @throws FailedExecute
     */
    public function getCpuUsage(): float
    {
        $result = $this->terminal->exec(self::CMD_CPU);
        if (!$result)
            return 0.0;

        $result = explode("\n", $result);
        $cpu = 0.0;

        foreach ($result as $item) {
            $value = $this->process->explode($item, 10);
            if (!isset($value[Process::$processCpu]))
                continue;

            $cpu += floatval($value[Process::$processCpu]);
        }

        return $cpu;
    }

    /**
     * @inheritDoc
     * @throws FailedExecute
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
     * @throws FailedExecute
     */
    public function getMemory(): int
    {
        return $this->memory(1);
    }

    /**
     * @inheritDoc
     * @throws FailedExecute
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
     * @throws FailedExecute
     */
    private function memory(int $type): int
    {
        $result = $this->terminal->exec(self::CMD_MEMORY);
        if (!$result)
            return 0;

        $result = explode("\n", $result)[1];
        $result = $this->process->explode($result);

        if (!isset($result[$type]))
            return 0;

        return (int) $result[$type];
    }
}