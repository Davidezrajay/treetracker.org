<?php


class Note extends Eloquent {

	public $timestamps = false;
	
	public function trees()
	{
		return $this->belongsToMany('Tree');
	}

}