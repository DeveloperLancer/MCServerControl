<?php declare(strict_types=1);
/**
 * @author Jakub Gniecki
 * @copyright Jakub Gniecki <kubuspl@onet.eu>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace DevLancer\MCServerControl;

if (!defined("MCSC_PROCESS_PID"))
    define("MCSC_PROCESS_PID", 1);

if (!defined("MCSC_PROCESS_CPU"))
    define("MCSC_PROCESS_CPU", 2);

if (!defined("MCSC_PROCESS_MEMORY"))
    define("MCSC_PROCESS_MEMORY", 3);

if (!defined("MCSC_PROCESS_COMMAND"))
    define("MCSC_PROCESS_COMMAND", 10);

use DevLancer\MCServerControl\Exception\ProcessException;
use phpseclib3\Net\SSH2;

/**
 * Class Process
 * @package DevLancer\MCServerControl
 */
class Process implements ProcessInterface
{
    const CMD_SEARCH = "ps -aux | grep --color=never %s\n";

    /**
     * @var SSH2
     */
    private SSH2 $ssh;

    /**
     * Process constructor.
     * @param SSH2 $ssh
     * @throws ProcessException
     */
    public function __construct(SSH2 $ssh)
    {
        $this->ssh = $ssh;
        if (!$this->ssh->isConnected())
            throw new ProcessException("SSH must be connected");
    }

    /**
     * @param string $name
     * @param string $cmd
     * @return string[]|null
     * @throws ProcessException
     */
    public function getByName(string $name, string $cmd = self::CMD_SEARCH): ?array
    {
        $cmd = sprintf($cmd, $name);
        $result = $this->ssh->exec($cmd);

        if((bool) !$result)
            throw new ProcessException(sprintf("Failed to execute: %s", $cmd));

        $result = explode("\n", $result)[0];
        $result = $this->explode($result, MCSC_PROCESS_COMMAND);

        if (!isset($result[MCSC_PROCESS_COMMAND])) {
            trigger_error("The process could not be processed properly", E_USER_WARNING);
            return null;
        }

        if (strpos($result[MCSC_PROCESS_COMMAND], $name) === false)
            return null;

        return $result;
    }

    /**
     * @param int $pid
     * @param string $cmd
     * @return string[]|null
     * @throws ProcessException
     */
    public function getByPid(int $pid, string $cmd = self::CMD_SEARCH): ?array
    {
        $cmd = sprintf($cmd, $pid);
        $result = $this->ssh->exec($cmd);

        if((bool) !$result)
            throw new ProcessException(sprintf("Failed to execute: %s", $cmd));

        $result = explode("\n", $result);
        foreach ($result as $item) {
            $item = $this->explode($item, MCSC_PROCESS_COMMAND);

            if (!isset($item[MCSC_PROCESS_PID])) {
                trigger_error("The process could not be processed properly", E_USER_WARNING);
                continue;
            }

            if ($item[MCSC_PROCESS_PID] == $pid)
                return $item;
        }

        return null;
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $separator
     * @return array|string[]
     */
    public function explode(string $string, int $length = -1, string $separator = " "): array
    {
        if ($length == 0)
            return [];

        $key = 0;
        $result = [""];
        $last_char = "";
        for ($i = 0; strlen($string) > $i; $i++) {

            $char = $string[$i];
            if ($char == $separator && ($key < $length || $length < 0)) {
                if ($last_char != $separator) {
                    $key++;
                    $result[] = "";
                }

                $last_char = $separator;
                continue;
            }

            $result[$key] .= $char;
            $last_char = $char;
        }

        return $result;
    }
}