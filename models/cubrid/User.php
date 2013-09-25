<?php

namespace app\models\cubrid;

use Yii;

class User extends \yii\db\ActiveRecord
{
	public static function getDb()
	{
		return Yii::$app->cubrid;
	}
}