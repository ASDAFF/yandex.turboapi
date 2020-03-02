<?
namespace Goodde\YandexTurbo;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

class Log
{
	protected static function scanDir($dir, $comment, $data, $fileName, $count = 5)
	{
		if(defined('GOODDE_API_LOG') && GOODDE_API_LOG == true) 
		{
			if(!is_dir($dir))
				mkdir($dir, 0755, true);

			$fileUrl = $dir . $fileName . '.txt';

			$fopenResult = fopen($fileUrl, 'ab');
			$writeData   = $comment . "\n";
			if(strlen($data) > 0)
				$writeData = $comment . ":\t" . $data . "\n";

			if($fopenResult) 
			{
				flock($fopenResult, LOCK_EX);
				fwrite($fopenResult, $writeData);
				fflush($fopenResult);
				flock($fopenResult, LOCK_UN);
				fclose($fopenResult);
			}

			//Save last 30 log files
			$objects = scandir($dir, 1);
			if(is_dir($dir)) 
			{
				$i = 0;
				foreach($objects as $key => $object) 
				{
					if($object != "." && $object != "..") 
					{
						$i++;

						if(filetype($dir . $object) != "dir" && $i >= $count)
							unlink($dir . $object);
					}
					else 
					{
						unset($objects[ $key ]);
					}
				}
				reset($objects);
			}
		}
	}

	public static function write($comment, $data, $fileName)
	{
		$dir = \Goodde\YandexTurbo\Turbo::getPath() . '/export_log/';
		self::scanDir($dir, $comment, $data, $fileName);
	}
	
	public static function  getMemoryUsage($mgu = 0)
	{
		$memory = $mgu ? memory_get_usage(true) - $mgu : memory_get_usage(true);
		return \CFile::FormatSize($memory, 0);
	}
}