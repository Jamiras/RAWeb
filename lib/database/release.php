<?php

/**
 * References:
 * https://github.com/RetroAchievements/RAInterface/blob/master/RA_Interface.h
 * https://github.com/RetroAchievements/rcheevos/blob/develop/include/rconsoles.h
 * https://github.com/RetroAchievements/rcheevos/blob/develop/src/rcheevos/consoleinfo.c
 * https://github.com/RetroAchievements/rcheevos/blob/develop/test/rcheevos/test_consoleinfo.c
 */
function isValidConsoleId(int $consoleId): bool
{
    return match ($consoleId) {
        1, // Mega Drive/Genesis
        2, // Nintendo 64
        3, // SNES
        4, // Game Boy
        5, // Game Boy Advance
        6, // Game Boy Color
        7, // NES
        8, // PC Engine
        9, // Sega CD
        10, // Sega 32X
        11, // Master System
        12, // PlayStation
        13, // Atari Lynx
        14, // Neo Geo Pocket
        15, // Game Gear
        // 16, // GameCube
        17, // Atari Jaguar
        18, // Nintendo DS
        // 19, // Wii
        // 20, // Wii U
        21, // PlayStation 2
        // 22, // Xbox
        23, // Magnavox Odyssey 2
        24, // Pokemon Mini
        25, // Atari 2600
        // 26, // DOS
        27, // Arcade
        28, // Virtual Boy
        29, // MSX
        // 30, // Commodore 64
        // 31, // ZX81
        // 32, // Oric
        33, // SG-1000
        // 34, // VIC-20
        // 35, // Amiga
        // 36, // Atari ST
        37, // Amstrad CPC
        38, // Apple II
        39, // Sega Saturn
        40, // Dreamcast
        41, // PlayStation Portable
        // 42, // Philips CD-i
        43, // 3DO Interactive Multiplayer
        44, // ColecoVision
        45, // Intellivision
        46, // Vectrex
        47, // PC-8000/8800
        // 48, // PC-9800
        49, // PC-FX
        // 50, // Atari 5200
        51, // Atari 7800
        // 52, // X68K
        53, // WonderSwan
        // 54, // Cassette Vision
        // 55, // Super Cassette Vision
        56, // Neo Geo CD
        57, // Fairchild Channel-F
        // 58, // FM Towns
        // 59, // ZX Spectrum
        // 60, // Game & Watch
        // 61, // Nokia N-Gage
        // 62, // Nintendo 3DS
        63, // Supervision
        // 64, // Sharp X1
        // 65, // TIC-80
        // 66, // Thomson TO8
        // 67, // PC-6000
        // 68, // Sega Pico
        69, // Mega Duck
        // 70, // Zeebo
        71, // Arduboy
        72, // WASM-4
        // 100, // Hubs (not an actual console)
        101 => true, // Events (not an actual console)
        default => false,
    };
}

function getEmulatorReleaseByIntegrationId(?int $integrationId): ?array
{
    $releases = getReleasesFromFile();
    $emulators = $releases['emulators'] ?? [];

    return $emulators[$integrationId] ?? null;
}

function getIntegrationRelease(): ?array
{
    $releases = getReleasesFromFile();

    return $releases['integration'] ?? null;
}

function getReleasesFromFile(): ?array
{
    return file_exists(storage_path('app/releases.php')) ? require_once storage_path('app/releases.php') : null;
}

function getActiveEmulatorReleases(): array
{
    $consoles = getConsoleList();
    $releases = getReleasesFromFile();
    $emulators = array_filter($releases['emulators'] ?? [], fn ($emulator) => $emulator['active'] ?? false);
    if (!empty($consoles)) {
        $emulators = array_map(function ($emulator) use ($consoles) {
            $systems = [];
            foreach ($emulator['systems'] as $system) {
                $systems[$system] = $consoles[$system];
            }
            $emulator['systems'] = $systems;

            return $emulator;
        }, $emulators);
    }

    return $emulators;
}

function isValidClientForHardcore(string &$errorMessage): bool
{
    $client = parseUserAgent();
    $releases = getReleasesFromFile();
    $emulators = array_filter($releases['emulators'] ?? [], fn ($emulator) => $emulator['active'] ?? false);
    foreach ($emulators as $emulator) {
        if (strcasecmp($emulator['handle'], $client['Client']) == 0) {
            if (!array_key_exists('minimum_version', $emulator)) {
                return true;
            }

            if ($client['ClientVersion'] === 'Unknown') {
                $errorMessage = 'Unknown client version';
                return false;
            }

            if (version_compare($client['ClientVersion'], $emulator['minimum_version']) < 0) {
                $errorMessage = $emulator['handle'] . ' version ' . $emulator['minimum_version'] . ' or higher required for hardcore';
                return false;
            }

            return true;
        }
    }

    $errorMessage = 'Unknown client';
    return false;
}

function parseUserAgent(?string $userAgent = null): array
{
    $result = ['Client' => 'Unknown', 'ClientVersion' => 'Unknown'];

    // RALibRetro/1.3.11 (WindowsNT 10.0) Integration/1.0.4.0
    // RetroArch/1.8.1 (Windows 10 x64 Build 18362 10.0) quicknes_libretro/1.0-WIP_7c0796d
    if ($userAgent === null) {
        $userAgent = request()->header('User-Agent') ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    $userAgentLength = strlen($userAgent);

    $index = strpos($userAgent, '/');
    if ($index !== false) {
        // found "Client/Version", just split it
        $result['Client'] = substr($userAgent, 0, $index);

        $index2 = strpos($userAgent, '(', $index);
        if ($index2 === false) {
            $index2 = strpos($userAgent, ' ', $index);
            if ($index2 === false) {
                 $index2 = $userAgentLength;
            }
        }
        $result['ClientVersion'] = trim(substr($userAgent, $index + 1, $index2 - $index - 1));
    } else {
        // not in form "Client/Version", try to extract version from end of string

        // ignore 'nightly' moniker
        $userAgent = str_ireplace('nightly', '', $userAgent);
        $userAgentLength = strlen($userAgent);

        $index2 = strpos($userAgent, '(');
        if ($index2 === false) {
            $index2 = $userAgentLength;
        }

        // skip whitespace
        $index = $index2;
        while ($index > 0) {
            $c = substr($userAgent, $index - 1, 1);
            if (!ctype_space($c)) {
                break;
            }
            $index--;
        }

        if ($index === 0 || !ctype_digit(substr($userAgent, $index - 1, 1))) {
            // does not end in a number, can't extract version
            $result['Client'] = $userAgent;
            return $result;
        }

        // capture numbers and decimals
        while (true) {
            if ($index === 0) {
                // did not find non-version character, abort
                $result['Client'] = $userAgent;
                return $result;
            }

            $c = substr($userAgent, --$index, 1);
            if (!is_numeric($c) && $c !== '.') {
                // found non-version character, split on it
                break;
            }
        }

        $result['ClientVersion'] = trim(substr($userAgent, $index + 1, $index2 - $index - 1));

        // trim non-alphanumeric stuff
        while ($index > 0) {
            $c = substr($userAgent, $index - 1, 1);
            if (ctype_alnum($c)) {
                break;
            }
            $index--;
        }

        $result['Client'] = substr($userAgent, 0, $index);
    }

    if ($index2 == $userAgentLength) {
        return $result;
    }

    // assume the first chunk in parenthesis is the operating system
    if (substr($userAgent, $index2, 1) == '(') {
        $index3 = strpos($userAgent, ')', $index2);
        if ($index3 !== false) {
            $result['OS'] = substr($userAgent, $index2 + 1, $index3 - $index2 - 1);
            $index2 = $index3;
        }
    }

    // put any other "key/value" pairs into the 'Extra' bucket
    $index4 = strpos($userAgent, '/', $index2);
    if ($index4 !== false) {
        $result['Extra'] = [];

        do {
            // to get strrpos to search backwards, we have to provide a negative offset to the index
            $index5 = strrpos($userAgent, ' ', -($userAgentLength - $index4));
            if ($index5 === false) {
                break;
            }
            $index6 = strpos($userAgent, ' ', $index4);
            if ($index6 === false) {
                $index6 = $userAgentLength;
            }

            $submodule = substr($userAgent, $index5 + 1, $index4 - $index5 - 1);
            $subversion = substr($userAgent, $index4 + 1, $index6 - $index4 - 1);
            $result['Extra'][$submodule] = $subversion;

            $index4 = strpos($userAgent, '/', $index6);
        } while ($index4 !== false);
    }

    return $result;
}
