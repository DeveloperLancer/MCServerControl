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

use phpseclib3\Net\SSH2;

/**
 * Class Process
 * @package DevLancer\MCServerControl
 */
class Process
{
    const CMD_SEARCH = "ps -aux | grep --color=never %s\n";

    /**
     * @param SSH2 $ssh
     * @param string $name
     * @param string $cmd
     * @return string[]|null
     */
    public static function getByName(SSH2 $ssh, string $name, string $cmd = self::CMD_SEARCH): ?array
    {
        if (!$ssh->isConnected())
            return null;

        $cmd = sprintf($cmd, $name);
        $result = $ssh->exec($cmd);

        if((bool) !$result)
            return null;

        $result = explode("\n", $result)[0];
        $result = self::explode($result, MCSC_PROCESS_COMMAND);

        if (!isset($result[MCSC_PROCESS_COMMAND]))
            return null;

        if (strpos($result[MCSC_PROCESS_COMMAND], $name) === false)
            return null;

        return $result;
    }

    /**
     * @param SSH2 $ssh
     * @param int $pid
     * @param string $cmd
     * @return string[]|null
     */
    public static function getByPid(SSH2 $ssh, int $pid, string $cmd = self::CMD_SEARCH): ?array
    {
        if (!$ssh->isConnected())
            return null;

        $cmd = sprintf($cmd, $pid);
        $result = $ssh->exec($cmd);

        if((bool) !$result)
            return null;

        $result = explode("\n", $result);
        foreach ($result as $item) {
            $item = self::explode($item, MCSC_PROCESS_COMMAND);

            if (!isset($item[MCSC_PROCESS_PID]))
                continue;

            if ($item[MCSC_PROCESS_PID] == $pid)
                return $result;
        }

        return null;
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $separator
     * @return array|string[]
     */
    public static function explode(string $string, int $length = -1, string $separator = " "): array
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