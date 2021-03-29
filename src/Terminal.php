<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\MCServerController;


use DevLancer\MCServerController\Exception\FailedExecute;
use DevLancer\MCServerController\Exception\NotSshConnection;
use phpseclib3\Net\SSH2;

/**
 * Class Terminal
 * @package DevLancer\MCServerControl
 */
class Terminal implements TerminalInterface
{
    /**
     * @var SSH2
     */
    private SSH2 $ssh;

    /**
     * @var string|null
     */
    private ?string $sudoPassword;


    /**
     * @var bool|null|string
     */
    private $response;

    /**
     * Terminal constructor.
     * @param SSH2 $ssh
     * @param string|null $sudoPassword
     * @throws NotSshConnection
     */
    public function __construct(SSH2 $ssh, ?string $sudoPassword = null)
    {
        $this->ssh = $ssh;
        if (!$ssh->isConnected())
            throw new NotSshConnection("SSH must be connected");

        $this->sudoPassword = $sudoPassword;
    }

    /**
     * @param string $cmd
     * @return bool|string|null
     * @throws FailedExecute
     */
    public function exec(string $cmd)
    {
        preg_match('/(?i)(\Asudo)|;sudo|; sudo/', $cmd, $match);

        $isSudo = ($match == [])? false : true;

        if ($isSudo === true && !$this->sudoPassword)
            throw new FailedExecute("sudoPassword"); //todo dokoncz

        if (!$isSudo)
            return $this->ssh->exec($cmd);

        $this->ssh->read('/.*@.*[$|#]/', SSH2::READ_REGEX);
        $this->ssh->write($cmd . "\n");
        $output = $this->ssh->read('/.*@.*[$|#]|.*[pP]assword.*/', SSH2::READ_REGEX);

        if (preg_match('/.*[pP]assword.*/', $output)) {
            $this->ssh->write($this->sudoPassword . "\n");
            $output = $this->ssh->read('/.*@.*[$|#]/', SSH2::READ_REGEX);
        }

        $this->response = $output;

        return $output;
    }

    /**
     * @return SSH2
     */
    public function getSsh(): SSH2
    {
        return $this->ssh;
    }

    /**
     * @param SSH2 $ssh
     */
    public function setSsh(SSH2 $ssh): void
    {
        $this->ssh = $ssh;
    }

    /**
     * @return string|null
     */
    public function getSudoPassword(): ?string
    {
        return $this->sudoPassword;
    }

    /**
     * @param string $sudoPassword
     */
    public function setSudoPassword(string $sudoPassword): void
    {
        $this->sudoPassword = $sudoPassword;
    }

    /**
     * @return bool|string|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}