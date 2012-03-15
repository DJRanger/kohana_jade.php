<?php
require_once Kohana::find_file('vendor/jade.php', 'work');
require_once Kohana::find_file('vendor/jade.php/lib', 'Node');
require_once Kohana::find_file('vendor/jade.php/lib', 'Dumper');
require_once Kohana::find_file('vendor/jade.php/lib', 'Lexer');
require_once Kohana::find_file('vendor/jade.php/lib', 'Parser');
require_once Kohana::find_file('vendor/jade.php', 'Jade');

class Jade_View extends Kohana_View{
	public static $views = array();
	protected $jade;
	
	public function __construct($file = NULL, array $data = NULL){
		if ( ! isset($this->jade) ){
			 $this->jade = new Jade();
		}
		
		parent::__construct($file, $data);
		
		if ( ! is_dir(APPPATH.'cache/jade') ){
			mkdir(APPPATH.'cache/jade');
		}
	}
	
	public function set_filename($file)
	{
		$jade_ext = Kohana::$config->load('jade.ext');
		$jade = Kohana::find_file('views', $file, $jade_ext);
		$php = Kohana::find_file('views', $file);
		if (($jade || $php) === FALSE)
		{
			throw new View_Exception('The requested view :file could not be found', array(
				':file' => $file,
			));
		}
	
		// Store the file path locally
		$this->_file = ($jade ? $jade : $php);
	
		return $this;
	}

	
	public function render($file = NULL)
	{
		$jade_ext = Kohana::$config->load('jade.ext');
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}

		if (empty($this->_file))
		{
			throw new View_Exception('You must set the file to use within your view before rendering');
		}

		// Combine local and global data and capture the output
		$info = pathinfo($this->_file);
		
		if ($info['extension'] === $jade_ext){
			$hash   = md5($this->_file);
			
			$parsed_file = Kohana::$config->load('jade.cache') . $hash . '.php';
			self::$views[ $this->_file ] = $parsed_file;
			
			if ( ! Kohana::$config->load('jade.cache_files') || ! is_file($parsed_file) ){
				file_put_contents($parsed_file, $this->jade->render($this->_file));
			}
			
			// Parse a template (both string & file containers)
			return View::capture($parsed_file, $this->_data);
		}
		else{
			return View::capture($this->_file, $this->_data);
		}
	}
}