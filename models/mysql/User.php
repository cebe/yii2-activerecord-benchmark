<?php

namespace app\models\mysql;

use Yii;

class User extends \yii\db\ActiveRecord
{
	public static function getDb()
	{
		return Yii::$app->mysql;
	}
}