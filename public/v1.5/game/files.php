<?php

use RA\AchievementType;
use RA\Permissions;
use RA\TicketFilters;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputSanitized('ID', null, 'integer');
if ($gameID == null || $gameID == 0) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

authenticateFromCookie($user, $permissions, $userDetails);

$editLink = '';
if ($permissions >= Permissions::JuniorDeveloper) {
    $editLink = "/managehashes.php?g=$gameID";
}

$gameData = getGameData($gameID);
$hashes = getHashListByGameID($gameID);

$numOpenTickets = countOpenTickets(
    requestInputSanitized('f') == AchievementType::Unofficial,
    requestInputSanitized('t', TicketFilters::Default),
    null,
    null,
    null,
    $gameData['ID']
);

RenderHtmlStart(true);

?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($gameData['Title'] . ' (' . $gameData['ConsoleName'] . ')'); ?>
</head>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderGameHeader($gameData, 'Files', $editLink); ?>
        <?php if ($permissions >= Permissions::Registered) { ?>
            <div style='margin-top: 4px'>
                <div style='float: left; margin-right:4px'>
                    <a class='info-button' href='/codenotes.php?g=<?= $gameData['ID'] ?>'><span>📑</span>Code Notes</a>
                </div>
                <div style='float: left; margin-right:4px'>
                    <a class='info-button' href='/ticketmanager.php?g=<?= $gameData['ID'] ?>'><span>🎫</span>Open Tickets (<?= $numOpenTickets ?>)</a>
                </div>
            </div>
        <?php } ?>
        <div style='clear:both; margin-top: 10px'>
            <h4>Hashes</h4>
            Currently this game has <b><?= count((array) $hashes) ?></b> unique hashes registered for it:<br>
            <ul>
            <?php
                $hasUnlabeledHashes = false;
                foreach ($hashes as $hash) {
                    if (empty($hash['Name'])) {
                        $hasUnlabeledHashes = true;
                        continue;
                    }

                    $hashName = $hash['Name'];
                    sanitize_outputs($hashName);
                    echo "<li><p><b>$hashName</b>";
                    if (!empty($hash['Labels'])) {
                        foreach (explode(',', $hash['Labels']) as $label) {
                            if (empty($label)) {
                                continue;
                            }

                            $image = "/Images/labels/" . $label . '.png';
                            if (file_exists(__DIR__ . $image)) {
                                echo ' <img class="injectinlineimage" src="' . $image . '">';
                            } else {
                                echo ' [' . $label . ']';
                            }
                        }
                    }

                    echo '<br/><code> ' . $hash['Hash'] . '</code>';
                    if (!empty($hash['User'])) {
                        echo ' linked by ' . GetUserAndTooltipDiv($hash['User']);
                    }
                    echo '</p></li>';
                }

                if ($hasUnlabeledHashes) {
                    echo '<li><p><b>Unlabeled</b><br/>';
                    foreach ($hashes as $hash) {
                        if (!empty($hash['Name'])) {
                            continue;
                        }

                        echo '<code> ' . $hash['Hash'] . '</code>';
                        if (!empty($hash['User'])) {
                            echo " linked by " . GetUserAndTooltipDiv($hash['User']);
                        }
                        echo '<br/>';
                    }
                    echo "</p></li>";
                }
            ?>
            </ul>
            <?php
                if ($gameData['ForumTopicID'] > 0) {
                    echo "Additional information for these hashes may be listed on the <a href='viewtopic.php?t=" . $gameData['ForumTopicID'] . "'>official forum topic</a>.<br/>";
                }
            ?>
            <br/>
            Hashes are used to confirm if two copies of a file are identical.
            We use it to ensure the player is using the same ROM as the achievement developer, or a compatible one.
            <br/><br/>RetroAchievements only hashes portions of larger games to minimize load times, and strips
            headers on smaller ones. Details on how the hash is generated for each system can be found
            <a href='https://docs.retroachievements.org/Game-Identification/'>here</a>.
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
