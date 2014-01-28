<?php

class Autoloader {
	
	static public function loader($name)
	{
		$filename = str_replace('\\', '/', $name) . '.php';
		
		$path = dirname(__DIR__) . '/' . $filename;

		if (file_exists($path)) 
		{
			include($path);
			if (class_exists($name)) {
				return TRUE;
			}
		}
		return FALSE;
	}
}

spl_autoload_register('Autoloader::loader');