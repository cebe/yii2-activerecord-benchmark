<?php

return [
	'id' => 'yii2-ar-benchmark',
	'name' => 'Yii2 AR Benchmark',
	'basePath' => __DIR__,

	'components' => [
		'sqlite' => [
			'class' => yii\db\Connection::class,
			'tablePrefix' => 'tbl_',
			'dsn' => 'sqlite::memory:',
		],
//		'cubrid' => [
//			'class' => yii\db\Connection::class,
//			'tablePrefix' => 'tbl_',
//			'dsn' => 'cubrid:dbname=demodb;host=localhost;port=33000',
//			'username' => 'dba',
//			'password' => '',
//
//		],
		'mysql' => [
			'class' => yii\db\Connection::class,
			'tablePrefix' => 'tbl_',
			'dsn' => 'mysql:host=localhost;dbname=test_yii',
			'username' => 'test',
			'password' => 'test',

		],
		'redis' => [
			'class' => yii\redis\Connection::class,
		],
	]

];