<?php


class PhotoTree extends Eloquent {

	public $timestamps = false;
	
	public function tree () {
		return $this->belongsTo('PhotoTree');
	}
	
	public function photo () {
		return $this->belongsTo('PhotoTree');
	}

}