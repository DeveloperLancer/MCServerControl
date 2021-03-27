<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\MCServerControl;


/**
 * Class Locator
 * @package DevLancer\MCServerControl
 */
class Locator implements LocatorInterface
{
    /**
     * @var string
     */
    private string $path;

    /**
     * Locator constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        $result = explode("/", $this->path);
        unset($result[(count($result) - 1)]);
        return implode("/", $result);
    }

    /**
     * @inheritDoc
     */
    public function getFile(): string
    {
        $result = explode("/", $this->path);
        return end($result);
    }

    /**
     * @inheritDoc
     */
    public function isFileExist(): bool
    {
        return file_exists($this->path);
    }
}