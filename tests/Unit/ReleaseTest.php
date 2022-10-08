<?php

declare(strict_types=1);

namespace Tests\Unit;

final class ReleaseTest extends TestCase
{
    public function testIntegrationUserAgent(): void
    {
        $userAgent = 'RANes/1.1.0.0 (WindowsNT 10.0) Integration/1.0.4.0';
        $parsed = parseUserAgent($userAgent);
        $this->assertEquals('RANes', $parsed['Client']);
        $this->assertEquals('1.1.0.0', $parsed['ClientVersion']);
        $this->assertEquals('WindowsNT 10.0', $parsed['OS']);
        $this->assertArrayHasKey('Extra', $parsed);
        $this->assertEquals('1.0.4.0', $parsed['Extra']['Integration']);
    }

    public function testRALibretroUserAgentWithCore(): void
    {
        $userAgent = 'RALibRetro/1.3.11 (WindowsNT 10.0) Integration/1.0.4.0 fceumm_libretro/(SVN)_58030a3';
        $parsed = parseUserAgent($userAgent);
        $this->assertEquals('RALibRetro', $parsed['Client']);
        $this->assertEquals('1.3.11', $parsed['ClientVersion']);
        $this->assertEquals('WindowsNT 10.0', $parsed['OS']);
        $this->assertArrayHasKey('Extra', $parsed);
        $this->assertEquals('1.0.4.0', $parsed['Extra']['Integration']);
        $this->assertEquals('(SVN)_58030a3', $parsed['Extra']['fceumm_libretro']);
    }

    public function testRetroArchUserAgent(): void
    {
        $userAgent = 'RetroArch/1.11.0 (Linux 5.0)';
        $parsed = parseUserAgent($userAgent);
        $this->assertEquals('RetroArch', $parsed['Client']);
        $this->assertEquals('1.11.0', $parsed['ClientVersion']);
        $this->assertEquals('Linux 5.0', $parsed['OS']);
        $this->assertArrayNotHasKey('Extra', $parsed);
    }

    public function testRetroArchUserAgentWithCore(): void
    {
        $userAgent = 'RetroArch/1.8.1 (Windows 10 x64 Build 18362 10.0) quicknes_libretro/1.0-WIP_7c0796d';
        $parsed = parseUserAgent($userAgent);
        $this->assertEquals('RetroArch', $parsed['Client']);
        $this->assertEquals('1.8.1', $parsed['ClientVersion']);
        $this->assertEquals('Windows 10 x64 Build 18362 10.0', $parsed['OS']);
        $this->assertArrayHasKey('Extra', $parsed);
        $this->assertEquals('1.0-WIP_7c0796d', $parsed['Extra']['quicknes_libretro']);
    }

    public function testBrowserUserAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.71 Safari/537.36';
        $parsed = parseUserAgent($userAgent);
        $this->assertEquals('Mozilla', $parsed['Client']);
        $this->assertEquals('5.0', $parsed['ClientVersion']);
        $this->assertEquals('Windows NT 10.0; Win64; x64', $parsed['OS']);
        $this->assertArrayHasKey('Extra', $parsed);
        $this->assertEquals('537.36', $parsed['Extra']['AppleWebKit']);
        $this->assertEquals('76.0.3809.71', $parsed['Extra']['Chrome']);
        $this->assertEquals('537.36', $parsed['Extra']['Safari']);
    }

    public function testPCSX2NightlyUserAgent(): void
    {
        $userAgent = 'PCSX2 Nightly - v1.7.3366 (Microsoft Windows 10)';
        $parsed = parseUserAgent($userAgent);
        $this->assertEquals('PCSX2', $parsed['Client']);
        $this->assertEquals('1.7.3366', $parsed['ClientVersion']);
        $this->assertEquals('Microsoft Windows 10', $parsed['OS']);
        $this->assertArrayNotHasKey('Extra', $parsed);
    }

    public function testEmptyUserAgent(): void
    {
        $userAgent = '';
        $parsed = parseUserAgent($userAgent);
        $this->assertEquals('', $parsed['Client']);
        $this->assertEquals('Unknown', $parsed['ClientVersion']);
        $this->assertArrayNotHasKey('OS', $parsed);
        $this->assertArrayNotHasKey('Extra', $parsed);
    }

    public function testNoVersionUserAgent(): void
    {
        $userAgent = 'MyApp';
        $parsed = parseUserAgent($userAgent);
        $this->assertEquals('MyApp', $parsed['Client']);
        $this->assertEquals('Unknown', $parsed['ClientVersion']);
        $this->assertArrayNotHasKey('OS', $parsed);
        $this->assertArrayNotHasKey('Extra', $parsed);
    }

    public function testNumericUserAgent(): void
    {
        $userAgent = '123456';
        $parsed = parseUserAgent($userAgent);
        $this->assertEquals('123456', $parsed['Client']);
        $this->assertEquals('Unknown', $parsed['ClientVersion']);
        $this->assertArrayNotHasKey('OS', $parsed);
        $this->assertArrayNotHasKey('Extra', $parsed);
    }
}
