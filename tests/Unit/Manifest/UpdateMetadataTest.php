<?php

declare(strict_types=1);

namespace CoCoCo\Component\Balancirk\Tests\Unit\Manifest;

use PHPUnit\Framework\TestCase;

/**
 * Tests for Joomla update metadata.
 *
 * @since  1.3.8
 */
class UpdateMetadataTest extends TestCase
{
    public function testLatestPackageUpdateMatchesInstalledPackageIdentity(): void
    {
        $updates = simplexml_load_file(dirname(__DIR__, 3) . '/balancirk_update.xml');
        $this->assertNotFalse($updates);

        $latest = null;

        foreach ($updates->update as $update) {
            if ((string) $update->version === '1.3.7') {
                $latest = $update;
                break;
            }
        }

        $this->assertNotNull($latest);
        $this->assertSame('Balancirk', (string) $latest->name);
        $this->assertSame('pkg_balancirk', (string) $latest->element);
        $this->assertSame('package', (string) $latest->type);
        $this->assertSame('site', (string) $latest->client);
        $this->assertStringContainsString('/1.3.7/pkg_balancirk.zip', (string) $latest->downloads->downloadurl);
    }

    public function testAllPackageUpdatesDeclarePackageElementAndClient(): void
    {
        $updates = simplexml_load_file(dirname(__DIR__, 3) . '/balancirk_update.xml');
        $this->assertNotFalse($updates);

        foreach ($updates->update as $update) {
            if ((string) $update->type !== 'package') {
                continue;
            }

            $this->assertSame('pkg_balancirk', (string) $update->element);
            $this->assertSame('site', (string) $update->client);
        }
    }

    public function testComponentManifestDoesNotRegisterDuplicatePackageUpdateServer(): void
    {
        $componentManifest = simplexml_load_file(dirname(__DIR__, 3) . '/components/com_balancirk/balancirk.xml');
        $this->assertNotFalse($componentManifest);

        $this->assertSame([], $componentManifest->xpath('/extension/updateservers/server'));
    }
}
