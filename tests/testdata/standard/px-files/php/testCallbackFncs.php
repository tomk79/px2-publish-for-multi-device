<?php
class testCallbackFncs{
	public static function rewrite_tab($path){
		$path = dirname($path).'/_tab/'.basename($path);
		return $path;
	}
}
