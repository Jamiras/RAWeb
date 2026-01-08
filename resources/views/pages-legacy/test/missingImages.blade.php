<?php

use App\Models\System;

authenticateFromCookie($user, $permissions, $userDetails);

$consoleList = System::get(['ID', 'Name'])->keyBy('ID')->map(fn ($system) => $system['Name']);
$consoleID = requestInputSanitized('c', 0, 'integer');
$filterID = requestInputSanitized('f', 0, 'integer');

$filter = match($filterID)
{
    0 => "(image_icon_asset_path = '/Images/000001.png' ||
           image_title_asset_path = '/Images/000002.png' ||
           image_ingame_asset_path = '/Images/000002.png' ||
           image_box_art_asset_path = '/Images/000002.png')",
    1 => "achievements_published = 0",
};

$query = "SELECT g.id as ID, g.title as Title, g.system_id as ConsoleID,
                 g.image_icon_asset_path as ImageIcon, s.name as ConsoleName, g.genre as Genre
          FROM games g LEFT JOIN systems s ON s.id=g.system_id WHERE $filter";
if ($consoleID > 0) {
    $query .= " AND g.system_id = $consoleID";
} else {
    $query .= " AND g.system_id < 100";
}
$query .= " ORDER BY RAND() LIMIT 10";
$gamesList = legacyDbFetchAll($query)->sortBy('Title');

$title = match($filterID)
{
    0 => "Games Missing Media",
    1 => "Games Missing Achievements",
}

?>
<x-app-layout
    pageTitle="{{ $title }}"
    pageDescription="{{ $title }}"
>
    <div>
        <h2 class="flex gap-x-2 items-center $headingSizeClassName">
            <img src="<?= getSystemIconUrl(0) ?>" alt="Console icon" width="32" height="32">
            <span>Games Missing Media</span>
        </h2>
        <div class='w-full flex flex-col sm:flex-row sm:items-center lg:items-start gap-2 justify-between'>
            <div class='flex items-center gap-x-2'>
                <p>Show:</p>
                <select class='w-full sm:w-auto' onchange='window.location = "/test/missingImages.php?f={{ $filterID }}&c=" + this.options[this.selectedIndex].value'>
                    @if ($consoleID == 0)
                        <option value='0' selected>All consoles</option>
                    @else
                        <option value='0'>All consoles</option>
                    @endif
                    @foreach ($consoleList as $nextConsoleID => $nextConsoleName)
                        <!-- 0 is "All Consoles". Don't show consoles that haven't been rolled out yet. -->
                        @if ($nextConsoleID == 0 || isValidConsoleId($nextConsoleID))
                            @if ($nextConsoleID == $consoleID)
                                <option value='{{ $nextConsoleID }}' selected>{{ $nextConsoleName }}</option>
                            @else
                                <option value='{{ $nextConsoleID }}'>{{ $nextConsoleName }}</option>
                            @endif
                        @endif
                    @endforeach
                </select>
                <p>Filter:</p>
                <select class='w-full sm:w-auto' onchange='window.location = "/test/missingImages.php?c={{ $consoleID }}&f=" + this.options[this.selectedIndex].value'>
                    @if ($filterID == 0)
                        <option value='0' selected>Missing Media</option>
                    @else
                        <option value='0'>Missing Media</option>
                    @endif
                    @if ($filterID == 1)
                        <option value='1' selected>Missing Achievements</option>
                    @else
                        <option value='1'>Missing Achievements</option>
                    @endif
                </select>
            </div>

            <div><button type='button' class='btn' onClick='window.location.reload()'>Refresh</button></div>
        </div>

        <br/>

        <table>
        @foreach ($gamesList as $gameEntry)
            <tr><td class="py-2">
            @php
                $content = Blade::render('
                    <x-game.multiline-avatar
                        :gameId="$gameId"
                        :gameTitle="$gameTitle"
                        :gameImageIcon="$gameImageIcon"
                        :consoleName="$consoleName"
                    />
                ', [
                    'gameId' => $gameEntry['ID'],
                    'gameTitle' => $gameEntry['Title'],
                    'gameImageIcon' => $gameEntry['ImageIcon'],
                    'consoleName' => $gameEntry['ConsoleName'],
                ]);

                echo str_replace('http://localhost:64000/', 'https://retroachievements.org/', $content);
            @endphp
            </td>
            <td>{{ $gameEntry['Genre'] }}</td>
            </tr>
        @endforeach
        </table>

        <br />
    </div>
</x-app-layout>
