# Server Controller [![Packagist](https://img.shields.io/packagist/dt/dev-lancer/server-controller.svg)](https://packagist.org/packages/dev-lancer/server-controller)

## Installation
This library can installed by issuing the following command:
```bash
composer require dev-lancer/server-controller
```

## Example

### Terminal

```php
<?php declare(strict_types=1);

use DevLancer\ServerController\Terminal;
use phpseclib3\Net\SSH2;

require_once ("vendor/autoload.php");

$host = ""; //ssh host
$login = ""; //ssh login
$password = ""; //ssh password

$ssh = new SSH2($host);
$ssh->login($login, $password);
$terminal = new Terminal($ssh, $password);
```

### ServerControl

For minecraft server

```php
<?php declare(strict_types=1);

use DevLancer\ServerController\Terminal;
use DevLancer\ServerController\ServerControl;
use DevLancer\ServerController\Locator;
use DevLancer\ServerController\Command;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;

require_once ("vendor/autoload.php");

$host = ""; //ssh host
$login = ""; //ssh login
$password = ""; //ssh password

$ssh = new SSH2($host);
$ssh->login($login, $password);

$sftp = new SFTP($host);
$sftp->login($login, $password);

$terminal = new Terminal($ssh, $password);
$serverControl = new ServerControl($terminal);

$port = 25565; //minecraft server port
$path = "path/to/server.jar";
$params = ["-Xms2G", "-Xmx6G"];
$locator = new Locator($sftp, $path);

$result = $serverControl->start($locator, $port, Command::MINECRAFT_START, $params);
if ($result)
    echo "Server started";
```

### ServerMonitor

```php
<?php declare(strict_types=1);

use DevLancer\ServerController\Terminal;
use DevLancer\ServerController\ServerControl;
use DevLancer\ServerController\ServerMonitor;
use phpseclib3\Net\SSH2;

require_once ("vendor/autoload.php");

$host = ""; //ssh host
$login = ""; //ssh login
$password = ""; //ssh password

$port = 25565; //minecraft server port

$ssh = new SSH2($host);
$ssh->login($login, $password);
$terminal = new Terminal($ssh, $password);
$serverControl = new ServerControl($terminal);

$pid = $serverControl->getPid($port);

$serverMonitor = new ServerMonitor($terminal);
print_r([
   $serverMonitor->getCpuUsage($pid),
   $serverMonitor->getMemoryUsage($pid),
   $serverMonitor->getUptime($pid) 
]);
```

### MachineMonitor

```php
<?php declare(strict_types=1);

use DevLancer\ServerController\Terminal;
use DevLancer\ServerController\MachineMonitor;
use phpseclib3\Net\SSH2;

require_once ("vendor/autoload.php");

$host = ""; //ssh host
$login = ""; //ssh login
$password = ""; //ssh password

$ssh = new SSH2($host);
$ssh->login($login, $password);
$terminal = new Terminal($ssh, $password);

$machineMonitor = new MachineMonitor($terminal);
print_r([
   $machineMonitor->getCpuUsage(),
   $machineMonitor->getMemoryUsage(),
   $machineMonitor->getMemoryFree(),
   $machineMonitor->getMemory()
]);
```

## License

[MIT](LICENSE)