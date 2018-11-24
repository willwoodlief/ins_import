<?php
/**
 * @link http://zeromq.org/community
 */
require_once  'vendor/autoload.php';

use AsyncPHP\Doorman\Manager\GroupProcessManager;
use AsyncPHP\Doorman\Manager\ProcessManager;
use AsyncPHP\Doorman\Task\ProcessCallbackTask;
use AsyncPHP\Doorman\Rule\InMemoryRule;

use AsyncPHP\Remit\Client\ZeroMqClient;
use AsyncPHP\Remit\Server\ZeroMqServer;
use AsyncPHP\Remit\Location\InMemoryLocation;


if (isCommandLineInterface()) {
	print "cli\n";
} else {
	print "not\n";
}
$a = "hellop";





$server = new ZeroMqServer(new InMemoryLocation("127.0.0.1", 5555));

$server->addListener("greet", function($thing,$more,$file_base,$other) {
	print $thing . "\n";

});


$rule1 = new InMemoryRule();
$rule1->setProcesses(2);
$rule1->setMinimumProcessorUsage(0);
$rule1->setMaximumProcessorUsage(50);

$rule2 = new InMemoryRule();
$rule2->setProcesses(1);
$rule2->setMinimumProcessorUsage(51);
$rule2->setMaximumProcessorUsage(75);

$rule3 = new InMemoryRule();
$rule3->setProcesses(0);
$rule3->setMinimumProcessorUsage(76);
$rule3->setMaximumProcessorUsage(100);

$manager = new GroupProcessManager(new ProcessManager());



function get_task($a,$file_base) {
	return new ProcessCallbackTask(function () use($a,$file_base) {


		try {
			$client = new ZeroMqClient(new InMemoryLocation("127.0.0.1", 5555));
			$write = function($message,$other = null) use ($client,$a,$file_base) {
				$name = $a . ':'. getmypid();

				$total_message = $name . ' ' . $message;

				if ($other !== null) {
					$total_message .= ':' . print_r($other,true);
				}

				file_put_contents( $file_base.'process_darna.txt', $message );
				$fp = fopen($file_base."process_log.txt", "a+");
				$getLock = flock($fp, LOCK_EX);

				if ($getLock) {  // acquire an exclusive lock

					fwrite($fp, $total_message. "\n");
					fflush($fp);            // flush output before releasing the lock
					flock($fp, LOCK_UN);    // release the lock
				} else {
					file_put_contents($file_base.'process_darn.txt','darn');
				}

				fclose($fp);

				$client->emit("greet", array($total_message,$a,$file_base,$other));
			};

			$write('hi there',22);




		} catch (Exception $e) {
			file_put_contents( '/home/will/htdocs/prestashop/modules/jn_itvaikasimport/process_darna.txt', (string)$e );
		}
	});
}



$file_base = '/home/will/htdocs/prestashop/modules/jn_itvaikasimport/';
$array_tasks = [];
$r = "process# ";
for($i=0; $i < 5; $i++) {
//	$t = new tot($r . $i, $i);
	$t = $r . $i;
	$array_tasks[] = get_task($t,$file_base);
}
$manager->addTaskGroup($array_tasks);
//$manager->addTask(get_task($what));
//$manager->addTask(get_task($what));
//$manager->addTask(get_task($what));
$pid = getmypid();
print "in main, pid is $pid\n";






$count = 100;
do {
	// check for new remit events
	$server->tick();
	$count --;

	usleep(250);
} while ($manager->tick() || $count > 0);



function isCommandLineInterface()
{
	return (php_sapi_name() === 'cli');
}
