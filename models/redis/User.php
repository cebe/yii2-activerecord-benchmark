<?php

namespace app\models\redis;


class User extends \yii\redis\ActiveRecord
{
	public function attributes()
	{
		return [
			'id',
			'name',
			'email',
			'visits',
			'created',
		];
	}

	public static function primaryKey()
	{
		return ['id'];
	}
}
