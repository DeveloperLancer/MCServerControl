<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DevLancer\MCServerControl;


interface ServerControlInterface
{
    public function start(string $path, int $port, array $parameters = []): bool;
    public function stop(int $port): bool;
}