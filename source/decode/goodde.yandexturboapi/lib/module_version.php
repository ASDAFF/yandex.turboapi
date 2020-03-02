<?
namespace Goodde\YandexTurbo;

class ModuleVersion 
{
	protected static $moduleVersion = array();

	public static function getModuleVersion($module)
	{
		if (!isset(self::$moduleVersion[$module])) {
			self::loadModuleVersion($module);
		}
		return self::$moduleVersion[$module];
	}

	public static function checkMinVersion($module, $version)
	{
		if (!isset(self::$moduleVersion[$module])) {
			self::loadModuleVersion($module);
		}
		if (self::$moduleVersion[$module] == '0.0.0') {
			return false;
		}

		return version_compare(self::$moduleVersion[$module], $version, '>=');
	}

	protected static function loadModuleVersion($module)
	{
		self::$moduleVersion[$module] = '0.0.0';
		$moduleObject = \CModule::CreateModuleObject($module);
		if ($moduleObject) {
			self::$moduleVersion[$module] = $moduleObject->MODULE_VERSION;
		}
		unset($moduleObject);
	}
	
	public static function isIblockNewCatalog18()
	{
		return self::checkIblockMinVersion('18.6.200');
	}

	public static function getIblockVersion()
	{
		return self::getModuleVersion('iblock');
	}

	public static function checkIblockMinVersion($checkVersion)
	{
		return self::checkMinVersion('iblock', $checkVersion);
	}
}