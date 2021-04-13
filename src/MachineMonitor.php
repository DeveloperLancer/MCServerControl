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

    private int $total = 0;
    private int $free = 0;
    private int $usage = 0;

    /**
     * MachineMonitor constructor.
     * @param TerminalInterface $terminal
     * @param Process|null $process
     * @throws FailedExecute
     */
    public function __construct(TerminalInterface $terminal, Process $process = null)
    {
        $this->terminal = $terminal;
        $this->process = ($process)? $process : new Process($terminal);
        $this->memory();
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

        return round($cpu, 2);
    }

    /**
     * @inheritDoc
     */
    public function getMemoryUsage(int $format = self::FORMAT_UNITS): float
    {
        if ($format == self::FORMAT_UNITS)
            return (float) $this->usage;

        if ($this->total == 0 || $this->usage == 0)
            return 0.0;

        return round(($this->usage * 100) / $this->total, 2);
    }

    /**
     * @inheritDoc
     */
    public function getMemory(): int
    {
        return $this->total;
    }

    /**
     * @inheritDoc
     */
    public function getMemoryFree(int $format = self::FORMAT_UNITS): float
    {
        if ($format == self::FORMAT_UNITS)
            return (float) $this->free;

        if ($this->free == 0 || $this->total == 0)
            return 0.0;

        return round(($this->free * 100) / $this->total, 2);
    }

    /**
     * @return array
     * @throws FailedExecute
     */
    public function memory(): array
    {
        $result = $this->terminal->exec(self::CMD_MEMORY);
        if (!$result)
            return [];

        $result = explode("\n", $result)[1];
        $result = $this->process->explode($result);

        $this->total = (int) $result[1]?? 0;
        $this->free  = (int) $result[3]?? 0;
        $this->usage = (int) $this->total - $this->free;

        return $result;
    }
}