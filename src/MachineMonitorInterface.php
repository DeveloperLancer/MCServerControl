<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DevLancer\MCServerControl;


/**
 * Interface MachineMonitorInterface
 * @package DevLancer\MCServerControl
 */
interface MachineMonitorInterface
{
    const FORMAT_PERCENTAGE = 0;
    const FORMAT_UNITS = 1;

    /**
     * @param int $format
     * @return float
     */
    public function getMemoryFree(int $format = self::FORMAT_PERCENTAGE): float;

    /**
     * @return float
     */
    public function getCpuUsage(): float;

    /**
     * @param int $format
     * @return float
     */
    public function getMemoryUsage(int $format = self::FORMAT_PERCENTAGE): float;

    /**
     * @return int
     */
    public function getMemory(): int;
}