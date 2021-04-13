<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\ServerController;


/**
 * Interface ServerMonitorInterface
 * @package DevLancer\MCServerControl
 */
interface ServerMonitorInterface
{
    /**
     * @param int $pid
     * @return float
     */
    public function getCpuUsage(int $pid): float;

    /**
     * @param int $pid
     * @return float
     */
    public function getMemoryUsage(int $pid): float;

    /**
     * @param int $pid
     * @return int seconds uptime
     */
    public function getUptime(int $pid): int;
}