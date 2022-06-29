<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$userPage = requestInputSanitized('ID');
if ($userPage == null || mb_strlen($userPage) == 0 || ctype_alnum($userPage) == false) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

authenticateFromCookie($user, $permissions, $userDetails);

getUserPageInfo($userPage, $userData, 0, 0, $user);
if (!$userData) {
    http_response_code(404);
    echo "user not found";
    exit;
}

if ($userPage != $user) {
    header("Location: " . getenv('APP_URL') . "/user/$userPage");
    exit;
}

$friendsList = getFriendList($user);

RenderHtmlStart(true);

?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($userPage); ?>
</head>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderUserHeader($userData, 'Friends', $userPage == $user); ?>
        <div style='margin-top:10px; width:310px; float: right'>
            <?php RenderScoreLeaderboardComponent($user, true); ?>
        </div>
        <div style='margin-top:10px; margin-right:320px; overflow: hidden'>
            <h2>Friends</h2>
            <?php
            if (empty($friendsList)) {
                echo "You don't appear to have friends registered here yet. Why not leave a comment on the <a href='/forum.php'>forums</a> or <a href='/userList.php'>browse the user pages</a> to find someone to add to your friend list?<br>";
            } else {
                echo "<table><tbody>";
                echo "<tr><th colspan='2'>Friend</th><th>Last Seen</th><th>Commands</th></tr>";
                $iter = 0;
                foreach ($friendsList as $friendEntry) {
                    if ($iter++ % 2 == 0) {
                        echo "<tr>";
                    } else {
                        echo "<tr>";
                    }

                    $nextFriendName = $friendEntry['Friend'];
                    $nextFriendActivity = $friendEntry['LastSeen'];

                    echo "<td>";
                    echo GetUserAndTooltipDiv($nextFriendName, true, null, 64);
                    echo "</td>";

                    echo "<td>";
                    echo GetUserAndTooltipDiv($nextFriendName, false);
                    echo "</td>";

                    echo "<td>";
                    if ($friendEntry['LastGameID']) {
                        $gameData = getGameData($friendEntry['LastGameID']);
                        echo GetGameAndTooltipDiv($gameData['ID'], $gameData['Title'], $gameData['ImageIcon'], $gameData['ConsoleName']);
                        echo "<br/>";
                    }
                    echo "$nextFriendActivity";
                    echo "</td>";

                    echo "<td style='vertical-align:middle;'>";
                    echo "<div class='buttoncollection'>";
                    echo "<span style='display:block;'><a href='/createmessage.php?t=$user'>Send&nbsp;Message</a></span>";
                    echo "<span style='display:block;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=0'>Remove&nbsp;Friend</a></span>";
                    echo "<span style='display:block;'><a href='/request/friend/update.php?f=$nextFriendName&amp;a=-1'>Block&nbsp;User</a></span>";
                    echo "</div>";
                    echo "</td>";

                    echo "</tr>";
                }
                echo "</tbody></table>";
            }
            ?>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
