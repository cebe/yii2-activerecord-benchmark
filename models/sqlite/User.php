<?php

namespace app\models\sqlite;

use Yii;

class User extends \yii\db\ActiveRecord
{
	public static function getDb()
	{
		return Yii::$app->sqlite;
	}
}