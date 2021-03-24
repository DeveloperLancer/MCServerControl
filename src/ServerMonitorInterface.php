<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\MCServerControl;


/**
 * Interface ServerMonitorInterface
 * @package DevLancer\MCServerControl
 */
interface ServerMonitorInterface
{
    const FORMAT_PERCENTAGE = 0;
    const FORMAT_UNITS = 1;

    /**
     * @param int $pid
     * @return float
     */
    public function getCpuUsage(int $pid): float;

    /**
     * @param int $pid
     * @param int $format
     * @return float
     */
    public function getMemoryUsage(int $pid, int $format = self::FORMAT_PERCENTAGE): float;

    /**
     * @param int $pid
     * @return int
     */
    public function getMemory(int $pid): int;

    /**
     * @param int $pid
     * @return int seconds uptime
     */
    public function getUptime(int $pid): int;
}