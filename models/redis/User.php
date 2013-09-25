<?php
/**
 * Created by JetBrains PhpStorm.
 * User: cebe
 * Date: 25.09.13
 * Time: 13:42
 * To change this template use File | Settings | File Templates.
 */

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
				'created' => 'integer',
			)
		));
	}
}