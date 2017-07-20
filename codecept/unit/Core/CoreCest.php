<?php
namespace Core;

use SilverStripe\Control\Director;
use SilverStripe\Core\TempFolder;
use \UnitTester;

class CoreCest
{
    /**
     * @var string
     */
    protected $tempPath;

    public function _before(UnitTester $I)
    {
        $this->tempPath = Director::baseFolder() . DIRECTORY_SEPARATOR . 'silverstripe-cache';
    }

    public function _after(UnitTester $I)
    {
        $user = TempFolder::getTempFolderUsername();
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'silverstripe-cache-php' .
            preg_replace('/[^\w-\.+]+/', '-', PHP_VERSION);
        foreach (array(
                     'C--inetpub-wwwroot-silverstripe-test-project',
                     '-Users-joebloggs-Sites-silverstripe-test-project',
                     '-cache-var-www-silverstripe-test-project'
                 ) as $dir) {
            $path = $base . $dir;
            if (file_exists($path)) {
                rmdir($path . DIRECTORY_SEPARATOR . $user);
                rmdir($path);
            }
        }
    }

    // tests
    public function testGetTempPathInProject(UnitTester $I)
    {
        $user = TempFolder::getTempFolderUsername();

        if (file_exists($this->tempPath)) {
            $I->assertEquals(TempFolder::getTempFolder(BASE_PATH), $this->tempPath . DIRECTORY_SEPARATOR . $user);
        } else {
            $user = TempFolder::getTempFolderUsername();
            $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'silverstripe-cache-php' .
                preg_replace('/[^\w-\.+]+/', '-', PHP_VERSION);

            // A typical Windows location for where sites are stored on IIS
            $I->assertEquals(
                $base . 'C--inetpub-wwwroot-silverstripe-test-project' . DIRECTORY_SEPARATOR . $user,
                TempFolder::getTempFolder('C:\\inetpub\\wwwroot\\silverstripe-test-project')
            );

            // A typical Mac OS X location for where sites are stored
            $I->assertEquals(
                $base . '-Users-joebloggs-Sites-silverstripe-test-project' . DIRECTORY_SEPARATOR . $user,
                TempFolder::getTempFolder('/Users/joebloggs/Sites/silverstripe-test-project')
            );

            // A typical Linux location for where sites are stored
            $I->assertEquals(
                $base . '-var-www-silverstripe-test-project' . DIRECTORY_SEPARATOR . $user,
                TempFolder::getTempFolder('/var/www/silverstripe-test-project')
            );
        }
    }
}
