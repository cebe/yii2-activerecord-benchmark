<?php

namespace app\controllers;

use yii\db\ActiveRecord;
use yii\helpers\Console;

class BenchmarkController extends \yii\console\Controller
{
	protected $time = 0;

	public $iterations = 1000;
	private $i;
	public $n = 1;
	public $ns = array(
		10,
		100,
		1000,
		10000,
		100000,
	);

	protected $stats = array();
	protected $current = null;

	public function actionIndex()
	{
		$this->runTests('redis');
		$this->runTests('sqlite');
		$this->runTests('mysql');
		$this->runTests('cubrid');

		$this->showMatrix();

		$this->stdout("legend:\n\n", Console::BOLD);
		echo <<<EOF
insertUsers            = insert of a record with 4 attributes and single integer pk
findUsersByPk          = find by pk (indexed search) and retrieve of a record with 4 attributes and single integer pk
findNonUsersByPk       = find of a record with single integer pk with empty result
findUsersWhere         = find by non pk attribute (full table scan) and retrieve of a record with 4 attributes and single integer pk
findNonUsersWhere      = find by non pk attribute (full table scan) of a record with single integer pk with empty result
updateUsers            = find by pk (indexed search) and update one non-pk attribute of a record with 4 attributes and single integer pk
updateUserCounters     = find by pk (indexed search) and update one counter attribute of a record with 4 attributes and single integer pk
deleteUsers            = find by pk (indexed search) and delete of a record with 4 attributes and single integer pk
updateAllUsers         = mass update of one non-pk attribute for all records with 4 attributes and single integer pk
updateAllUsersCounters = mass update of one counter attribute for all records with 4 attributes and single integer pk
deleteAllUsers         = mass delete of all records with 4 attributes and single integer pk

pk = primary key

EOF;

	}

	protected function prepareDb($backend)
	{
		switch($backend)
		{
			case 'redis':
				/** @var \yii\redis\Connection $redis */
				$redis = \Yii::$app->redis;
				$redis->executeCommand('FLUSHALL');

				break;
			case 'cubrid':
				/** @var \yii\db\Connection $db */
				$db = \Yii::$app->cubrid;
				$db->createCommand("DROP TABLE IF EXISTS tbl_user;")->execute();
				$db->createCommand()->createTable('tbl_user', array(
					'id' => 'pk',
					'name' => 'string',
					'email' => 'string',
					'visits' => 'integer',
					'created' => 'integer',
				))->execute();
				break;
			case 'mysql':
				/** @var \yii\db\Connection $db */
				$db = \Yii::$app->mysql;
				$db->createCommand("DROP TABLE IF EXISTS tbl_user;")->execute();
				$db->createCommand()->createTable('tbl_user', array(
					'id' => 'pk',
					'name' => 'string',
					'email' => 'string',
					'visits' => 'integer',
					'created' => 'integer',
				))->execute();
				break;
			case 'sqlite':
				/** @var \yii\db\Connection $db */
				$db = \Yii::$app->sqlite;
				$db->close();
				$db->open();
				$db->createCommand()->createTable('tbl_user', array(
					'id' => 'pk',
					'name' => 'string',
					'email' => 'string',
					'visits' => 'integer',
					'created' => 'integer',
				))->execute();
				break;

		}
	}

	protected function showMatrix()
	{
		foreach($this->stats as $backend => $times) {
			$this->stdout("$backend:\n\n", Console::BOLD);
			$len = $this->textLength(array_merge(array_keys($times), array('number of records:')));

			$this->printLLen('number of records:', $len);
			foreach($this->ns as $n) {
				$this->printRLen($n, 10);
			}
			echo "\n";

			foreach($times as $name => $t) {
				$this->printLLen($name, $len);
				foreach($this->ns as $n) {
					$this->printRLen(number_format($t[$n], 6), 10);
				}
				echo "\n";
			}
			echo "\n";
		}
	}

	private function printLLen($text, $len)
	{
		echo $text . str_repeat(' ', $len - strlen($text) + 1);
	}

	private function printRLen($text, $len)
	{
		echo str_repeat(' ', $len - strlen($text)) . $text . ' ';
	}

	private function textLength($items)
	{
		$maxlen = 0;
		foreach($items as $item) {
			$len = strlen($item);
			if ($maxlen < $len) {
				$maxlen = $len;
			}
		}
		return $maxlen;
	}

	protected function runTests($backend)
	{
		$this->stdout("\nrunning tests for $backend...\n\n", Console::BOLD);
		$this->current = $backend;
		$this->stats[$backend] = array();

		foreach($this->ns as $n) {
			$this->n = $n;

			$this->prepareDb($backend);
			sleep(1);

			$this->callTimed('insertUsers', array("app\\models\\$backend\\User"));
			$this->callTimed('findUsersByPk', array("app\\models\\$backend\\User"));
			$this->callTimed('findNonUsersByPk', array("app\\models\\$backend\\User"));
			$this->callTimed('findUsersWhere', array("app\\models\\$backend\\User"));
			$this->callTimed('findNonUsersWhere', array("app\\models\\$backend\\User"));
			$this->callTimed('updateUsers', array("app\\models\\$backend\\User"));
			$this->callTimed('updateUserCounters', array("app\\models\\$backend\\User"));
			$this->callTimed('deleteUsers', array("app\\models\\$backend\\User"));
			$this->callTimed('insertUsers', array("app\\models\\$backend\\User"));
			$this->callTimed('updateAllUsers', array("app\\models\\$backend\\User"));
			$this->callTimed('updateAllUsersCounters', array("app\\models\\$backend\\User"));
			$this->callTimed('deleteAllUsers', array("app\\models\\$backend\\User"));
			echo "\n\n";
		}
	}

	protected function callTimed($method, $params)
	{
		$this->time = microtime(true);
		$this->i = $this->iterations;
		call_user_func_array(array($this, $method), $params);
		$time = microtime(true) - $this->time;
		$this->stats[$this->current][$method][$this->n] = $time / $this->i;
		echo "finished. time: " . number_format($time, 4) . " sec. avg: " . number_format($time / $this->i, 6) . " sec.\n";
	}

	/**
	 * calculate pk in range of existing values
	 * @param $i
	 */
	protected function pk($i)
	{
		$n = $this->n;
		$it = $this->iterations;

		return $i * (int) ($n / $it + ($n < $it ? 1 : 0)) + 1;
	}

	protected function insertUsers($modelClass)
	{
		$n = $this->i = $this->n;
		echo "inserting $n users...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $record */
			$record = new $modelClass();
			$record->name = 'user' . $i;
			$record->email = 'user' . $i . '@test.cebe.cc';
			$record->created = time();
			$record->visits = 0;
			$record->save();
		}
	}

	protected function findUsersByPk($modelClass)
	{
		$n = $this->i = $this->iterations > $this->n ? $this->n : $this->iterations;
		echo "finding $n users out of $this->n by pk...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find($this->pk($i));
		}
	}

	protected function findNonUsersByPk($modelClass)
	{
		$n = $this->i = $this->iterations > $this->n ? $this->n : $this->iterations;
		echo "finding $n not existing users out of $this->n by pk...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find($this->n + $this->pk($i));
		}
	}

	protected function findUsersWhere($modelClass)
	{
		$n = $this->i = $this->iterations > $this->n ? $this->n : $this->iterations;
		echo "finding $n users out of $this->n with where()...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find(array('name' => 'user' . $this->pk($i)));
		}
	}

	protected function findNonUsersWhere($modelClass)
	{
		$n = $this->i = $this->iterations > $this->n ? $this->n : $this->iterations;
		echo "finding $n not existing users out of $this->n with where()...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find(array('name' => 'username' . $this->pk($i)));
		}
	}

	protected function updateUsers($modelClass)
	{
		$n = $this->i = $this->iterations > $this->n ? $this->n : $this->iterations;
		echo "finding and updating $n users out of $this->n...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find($this->pk($i));
			$user->email = $user->name . '@example.com';
			$user->save();
		}
	}

	protected function updateUserCounters($modelClass)
	{
		$n = $this->i = $this->iterations > $this->n ? $this->n : $this->iterations;
		echo "finding and updating $n users counter out of $this->n...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find($this->pk($i));
			$user->updateCounters(array('visits' => 1));
		}
	}

	protected function deleteUsers($modelClass)
	{
		$n = $this->i = $this->n;
		echo "finding and deleting $n users...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find($i + 1);
			$user->delete();
		}
	}

	protected function updateAllUsers($modelClass)
	{
		$n = $this->iterations;
		echo "updating all users...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$modelClass::updateAll(array('visits' => rand(1,100)));
		}
	}

	protected function updateAllUsersCounters($modelClass)
	{
		$n = $this->iterations;
		echo "updating all users counters...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$modelClass::updateAllCounters(array('visits' => 1));
		}
	}

	protected function deleteAllUsers($modelClass)
	{
		$this->i = 1;
		echo "deleting all users...";
		/** @var ActiveRecord $modelClass */
		$modelClass::deleteAll();
	}
}
