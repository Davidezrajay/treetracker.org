<?php


class AuthToken extends Eloquent {

	public $timestamps = false;
	protected $table ="tokens";
	
	public function user()
	{
		return $this->hasOne('User', 'id');
	}

}