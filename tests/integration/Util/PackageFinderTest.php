<?php declare(strict_types=1);
/*
 * This file is part of the WP Starter package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WeCodeMore\WpStarter\Tests\Integration\Util;

use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use WeCodeMore\WpStarter\Tests\IntegrationTestCase;
use WeCodeMore\WpStarter\Util\PackageFinder;

class PackageFinderTest extends IntegrationTestCase
{
    /**
     * @return PackageFinder
     */
    private function createFinder(): PackageFinder
    {
        $composer = $this->createComposer();

        return new PackageFinder(
            $composer->getRepositoryManager()->getLocalRepository(),
            $composer->getInstallationManager(),
            new Filesystem()
        );
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testFindByType()
    {
        $finder = $this->createFinder();

        $plugins = $finder->findByType('composer-plugin');
        $names = [];

        foreach ($plugins as $plugin) {
            static::assertInstanceOf(PackageInterface::class, $plugin);
            $names[] = $plugin->getName();
        }

        static::assertCount(3, $names);
        static::assertTrue(in_array('composer/installers', $names, true));
        static::assertTrue(in_array('composer/package-versions-deprecated', $names, true));
        static::assertTrue(in_array('dealerdirect/phpcodesniffer-composer-installer', $names, true));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testFindPathOf()
    {
        $finder = $this->createFinder();

        $phpcsInstaller = $finder->findByName('dealerdirect/phpcodesniffer-composer-installer');

        static::assertInstanceOf(PackageInterface::class, $phpcsInstaller);

        $path = $finder->findPathOf($phpcsInstaller);
        $paths = explode('/vendor/', $path);

        $expectedVendor = str_replace('\\', '/', $this->createComposerConfig()->get('vendor-dir'));

        static::assertCount(2, $paths);
        static::assertSame("{$paths[0]}/vendor", $expectedVendor);
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testFindByVendor()
    {
        $finder = $this->createFinder();

        $roavePackages = $finder->findByVendor('roave');

        $names = [];
        foreach ($roavePackages as $package) {
            static::assertInstanceOf(PackageInterface::class, $package);
            $names[] = $package->getName();
        }

        static::assertCount(1, $names);
        static::assertTrue(in_array('roave/security-advisories', $names, true));
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testFindByName()
    {
        $finder = $this->createFinder();

        $phpcs = $finder->findByName('*/*_c*er*');

        static::assertInstanceOf(PackageInterface::class, $phpcs);
        static::assertSame('squizlabs/php_codesniffer', $phpcs->getName());
    }

    /**
     * @covers \WeCodeMore\WpStarter\Util\PackageFinder
     */
    public function testSearch()
    {
        $finder = $this->createFinder();

        $phpcsPackages = $finder->search('*l*/php*c*er*');
        $names = [];
        foreach ($phpcsPackages as $package) {
            static::assertInstanceOf(PackageInterface::class, $package);
            $names[] = $package->getName();
        }

        static::assertCount(2, $names);
        static::assertTrue(in_array('squizlabs/php_codesniffer', $names, true));
        static::assertTrue(in_array('dealerdirect/phpcodesniffer-composer-installer', $names, true));
    }

}
