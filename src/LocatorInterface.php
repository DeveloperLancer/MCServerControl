<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\ServerController;


/**
 * Interface LocatorInterface
 * @package DevLancer\MCServerControl
 */
interface LocatorInterface
{
    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @return string
     */
    public function getFile(): string;

    /**
     * @return bool
     */
    public function isFileExist(): bool;
}