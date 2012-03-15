<?php
use Symfony\Framework\UniversalClassLoader;

use Everzet\Jade\Jade;
use Everzet\Jade\Parser;
use Everzet\Jade\Lexer\Lexer;
use Everzet\Jade\Dumper\PHPDumper;
use Everzet\Jade\Visitor\AutotagsVisitor;

use Everzet\Jade\Filter\JavaScriptFilter;
use Everzet\Jade\Filter\CDATAFilter;
use Everzet\Jade\Filter\PHPFilter;
use Everzet\Jade\Filter\CSSFilter;

require Kohana::find_file('vendor/jade.php/vendor', 'symfony/src/Symfony/Framework/UniversalClassLoader');
require Kohana::find_file('vendor/jade.php', 'autoload.php', 'dist');

class Jade_View extends Kohana_View{
	public static $views = array();
	
	public function __construct($file = NULL, array $data = NULL){
		parent::__construct($file, $data);
		
		if ( ! is_dir(APPPATH.'cache/jade') ){
			mkdir(APPPATH.'cache/jade');
		}

		$loader = new UniversalClassLoader();
		$loader->registerNamespaces(array('Everzet' => __DIR__.'/src'));
		$loader->register();
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
			$dumper = new PHPDumper();
			$dumper->registerVisitor('tag', new AutotagsVisitor());
			$dumper->registerFilter('javascript', new JavaScriptFilter());
			$dumper->registerFilter('cdata', new CDATAFilter());
			$dumper->registerFilter('php', new PHPFilter());
			$dumper->registerFilter('style', new CSSFilter());
			
			// Initialize parser & Jade
			$parser = new Parser(new Lexer());
			$jade   = new Jade($parser, $dumper);
			$hash   = md5($this->_file);
			
			$parsed_file = Kohana::$config->load('jade.cache') . $hash . '.php';
			self::$views[ $this->_file ] = $parsed_file;
			
			if ( ! Kohana::$config->load('jade.cache_files') || ! is_file($parsed_file) ){
				file_put_contents($parsed_file, $jade->render($this->_file));
			}
			
			// Parse a template (both string & file containers)
			return View::capture($parsed_file, $this->_data);
		}
		else{
			return View::capture($this->_file, $this->_data);
		}
	}
}