<?php

namespace app\models\redis;


use yii\redis\RecordSchema;

class User extends \yii\redis\ActiveRecord
{
	public static function getRecordSchema()
	{
		return new RecordSchema(array(
			'name' => 'user',
			'primaryKey' => array('id'),
			'columns' => array(
				'id' => 'integer',
				'name' => 'string',
				'email' => 'string',
				'visits' => 'integer',
				'created' => 'integer',
			)
		));
	}
}