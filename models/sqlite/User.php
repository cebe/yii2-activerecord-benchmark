<?php
/**
 * Created by JetBrains PhpStorm.
 * User: cebe
 * Date: 25.09.13
 * Time: 13:42
 * To change this template use File | Settings | File Templates.
 */

namespace app\models\sqlite;

use Yii;

class User extends \yii\db\ActiveRecord
{
	public static function getDb()
	{
		return Yii::$app->sqlite;
	}
}