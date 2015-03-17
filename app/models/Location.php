<?php


class Location extends Eloquent {
	
	public $timestamps = false;

	public function photo()
	{
		return $this->hasOne('Photo');
	}

}