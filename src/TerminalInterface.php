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
 * Interface TerminalInterface
 * @package DevLancer\MCServerControl
 */
interface TerminalInterface
{
    /**
     * @param string $cmd
     * @throws FailedExecute
     * @return mixed
     */
    public function exec(string $cmd);
}