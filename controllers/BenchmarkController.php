<?php

namespace app\controllers;

use yii\db\ActiveRecord;
use yii\helpers\Console;

class BenchmarkController extends \yii\console\Controller
{
	protected $time = 0;

	public $steps = 5000;
	public $n = 1;
	public $ns = array(
		10,
		100,
		1000,
		10000,
//		100000,
	);

	protected $stats = array();
	protected $current = null;

	public function actionIndex()
	{
		$this->runTests('redis');
		$this->runTests('sqlite');
		$this->runTests('mysql');

		$this->showMatrix();
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
			case 'mysql':
				/** @var \yii\db\Connection $db */
				$db = \Yii::$app->mysql;
				$db->createCommand("DROP TABLE IF EXISTS tbl_user;")->execute();
				$db->createCommand()->createTable('tbl_user', array(
					'id' => 'pk',
					'name' => 'string',
					'email' => 'string',
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
					'created' => 'integer',
				))->execute();
				break;

		}
	}

	protected function showMatrix()
	{
		foreach($this->stats as $backend => $times) {
			$this->stdout("$backend:\n\n", Console::BOLD);
			$len = $this->textLength(array_keys($times) + array('number of records:'));

			$this->printLLen('number of records:', $len);
			foreach($this->ns as $n) {
				$this->printRLen($n, 10);
			}
			echo "\n";

			foreach($times as $name => $t) {
				$this->printLLen($name, $len);
				foreach($this->ns as $n) {
					$this->printRLen(number_format($t[$n] / $n, 6), 10);
				}
				echo "\n";
			}
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
		$this->stdout("\n\nrunning tests for $backend...\n\n", Console::BOLD);
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
			$this->callTimed('updateUsers', array("app\\models\\$backend\\User"));
			$this->callTimed('updateUsersPk', array("app\\models\\$backend\\User"));
			$this->callTimed('deleteUsers', array("app\\models\\$backend\\User"));
			echo "\n\n";
		}
	}

	protected function callTimed($method, $params)
	{
		$this->time = microtime(true);
		call_user_func_array(array($this, $method), $params);
		$time = microtime(true) - $this->time;
		$this->stats[$this->current][$method][$this->n] = $time;
		echo "finished. time: " . number_format($time, 4) . " sec. avg: " . number_format($time / $this->n, 6) . " sec.\n";
	}

	protected function insertUsers($modelClass)
	{
		$n = $this->n;
		echo "inserting $n users...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $record */
			$record = new $modelClass();
			$record->name = 'user' . $i;
			$record->email = 'user' . $i . '@test.cebe.cc';
			$record->created = time();
			$record->save();
		}
	}

	protected function findUsersByPk($modelClass)
	{
		$n = $this->n;
		echo "finding $n users by pk...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find($i + 1);
		}
	}

	protected function findNonUsersByPk($modelClass)
	{
		$n = $this->n;
		echo "finding $n nonexisting users by pk...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find($n + $i);
		}
	}

	protected function findUsersWhere($modelClass)
	{
		$n = $this->n;
		echo "finding $n users with where()...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find(array('name' => 'user' . $i));
		}
	}

	protected function updateUsers($modelClass)
	{
		$n = $this->n;
		echo "finding and updating $n users...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find($i + 1);
			$user->email = $user->name . '@example.com';
			$user->save();
		}
	}

	protected function updateUsersPk($modelClass)
	{
		$n = $this->n;
		echo "finding and updating $n users pk...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find($i + 1);
			$user->id = $i;
			$user->save();
		}
	}

	protected function deleteUsers($modelClass)
	{
		$n = $this->n;
		echo "finding and deleting $n users...";
		for($i = 0; $i < $n; $i++) {
			/** @var ActiveRecord $modelClass */
			$user = $modelClass::find($i);
			$user->delete();
		}
	}

}
