<?php

return array(
	'id' => 'yii2-ar-benchmark',
	'name' => 'Yii2 AR Benchmark',
	'basePath' => __DIR__,
	'controllerPath' => '@app/controllers',

	'components' => array(
		'sqlite' => array(
			'class' => \yii\db\Connection::className(),
			'dsn' => 'sqlite::memory:',
		),
		'cubrid' => array(
			'class' => \yii\db\Connection::className(),
			'dsn' => 'cubrid:dbname=demodb;host=localhost;port=33000',
			'username' => 'dba',
			'password' => '',

		),
		'mysql' => array(
			'class' => \yii\db\Connection::className(),
			'dsn' => 'mysql:host=localhost;dbname=yii',
			'username' => 'test',
			'password' => 'test',

		),
		'redis' => array(
			'class' => \yii\redis\Connection::className(),
			'dsn' => 'redis://localhost/0',
		),
	)

);