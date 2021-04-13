<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\ServerController;


use phpseclib3\Net\SFTP;

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

    private ?SFTP $sftp;

    /**
     * Locator constructor.
     * @param string $path
     * @param null|SFTP $sftp
     */
    public function __construct(?SFTP $sftp = null, string $path = "")
    {
        $this->path = $path;
        $this->sftp = $sftp;
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
        if (!$this->sftp || $this->path === "")
            return true;

        return $this->sftp->file_exists($this->path);
    }
}