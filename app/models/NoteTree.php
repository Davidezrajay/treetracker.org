<?php


class NoteTree extends Eloquent {

	public $timestamps = false;
	
	public function tree () {
		return $this->belongsTo('NoteTree');
	}
	
	public function note () {
		return $this->belongsTo('NoteTree');
	}

}