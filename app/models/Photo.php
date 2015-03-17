<?php


class Photo extends Eloquent {
	
	public $timestamps = false;

	public function location()
	{
		return $this->hasOne('Location', 'id');
	}
	
	public function user()
	{
		return $this->hasOne('User', 'id');
	}

}