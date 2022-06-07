<?php

use RA\RatingType;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputSanitized('ID', null, 'integer');
if ($gameID == null || $gameID == 0) {
    header("Location: " . getenv('APP_URL') . "?e=urlissue");
    exit;
}

authenticateFromCookie($user, $permissions, $userDetails);

$numAchievements = getGameMetaData($gameID, $user, $achievementData, $gameData);
$numLeaderboards = getLeaderboardsForGame($gameID, $leaderboardData, $user);

$numPoints = 0;
foreach ($achievementData as $achievement) {
    $numPoints += $achievement['Points'];
}

$screenshotWidth = 200;
$screenshotMaxHeight = 240; // corresponds to the DS screen aspect ratio

$relatedGames = getGameAlternatives($gameID);
$gameAlts = [];
$gameHubs = [];
$gameSubsets = [];
$subsetPrefix = $gameData['Title'] . " [Subset - ";
foreach ($relatedGames as $gameAlt) {
    if ($gameAlt['ConsoleName'] == 'Hubs') {
        $gameHubs[] = $gameAlt;
    } else {
        if (str_starts_with($gameAlt['Title'], $subsetPrefix)) {
            $gameSubsets[] = $gameAlt;
        } else {
            $gameAlts[] = $gameAlt;
        }
    }
}

$gameRating = getGameRating($gameID, $user);
$minimumNumberOfRatingsToDisplay = 5;
$renderRatingControl = function ($label, $containername, $labelname, $ratingData) use ($minimumNumberOfRatingsToDisplay) {
    echo "<div style='float: right; margin-left: 10px; margin-right: 10px'>";

    $yourRating = ($ratingData['UserRating'] > 0) ? $ratingData['UserRating'] : 'not rated';

    $voters = $ratingData['RatingCount'];
    if ($voters < $minimumNumberOfRatingsToDisplay) {
        $labelcontent = "More ratings needed ($voters votes)";

        $star1 = $star2 = $star3 = $star4 = $star5 = "";
        $tooltip = "<div id='objtooltip' style='display:flex;max-width:400px'>";
        $tooltip .= "<table><tr><td nowrap>Your rating: $yourRating</td></tr></table>";
        $tooltip .= "</div>";
    } else {
        $rating = $ratingData['AverageRating'];
        $labelcontent = "Rating: " . number_format($rating, 2) . " ($voters votes)";

        $percent1 = round($ratingData['Rating1'] * 100 / $voters);
        $percent2 = round($ratingData['Rating2'] * 100 / $voters);
        $percent3 = round($ratingData['Rating3'] * 100 / $voters);
        $percent4 = round($ratingData['Rating4'] * 100 / $voters);
        $percent5 = round($ratingData['Rating5'] * 100 / $voters);

        $tooltip = "<div id='objtooltip' style='display:flex;max-width:400px'>";
        $tooltip .= "<table>";
        $tooltip .= "<tr><td colspan=3>Your rating: $yourRating</td></tr>";
        $tooltip .= "<tr><td nowrap>5 star</td><td>";
        $tooltip .= "<div class='progressbar'><div class='completion' style='width:$percent5%' /></div>";
        $tooltip .= "</td><td>$percent5%</td/></tr>";
        $tooltip .= "<tr><td nowrap>4 star</td><td>";
        $tooltip .= "<div class='progressbar'><div class='completion' style='width:$percent4%' /></div>";
        $tooltip .= "</td><td>$percent4%</td/></tr>";
        $tooltip .= "<tr><td nowrap>3 star</td><td>";
        $tooltip .= "<div class='progressbar'><div class='completion' style='width:$percent3%' /></div>";
        $tooltip .= "</td><td>$percent3%</td/></tr>";
        $tooltip .= "<tr><td nowrap>2 star</td><td>";
        $tooltip .= "<div class='progressbar'><div class='completion' style='width:$percent2%' /></div>";
        $tooltip .= "</td><td>$percent2%</td/></tr>";
        $tooltip .= "<tr><td nowrap>1 star</td><td>";
        $tooltip .= "<div class='progressbar'><div class='completion' style='width:$percent1%' /></div>";
        $tooltip .= "</td><td>$percent1%</td/></tr>";
        $tooltip .= "</table>";
        $tooltip .= "</div>";

        $star1 = ($rating >= 1.0) ? "starlit" : (($rating >= 0.5) ? "starhalf" : "");
        $star2 = ($rating >= 2.0) ? "starlit" : (($rating >= 1.5) ? "starhalf" : "");
        $star3 = ($rating >= 3.0) ? "starlit" : (($rating >= 2.5) ? "starhalf" : "");
        $star4 = ($rating >= 4.0) ? "starlit" : (($rating >= 3.5) ? "starhalf" : "");
        $star5 = ($rating >= 5.0) ? "starlit" : (($rating >= 4.5) ? "starhalf" : "");
    }

    echo "<div class='rating' id='$containername'>";
    echo "<a class='starimg $star1 1star'>1</a>";
    echo "<a class='starimg $star2 2star'>2</a>";
    echo "<a class='starimg $star3 3star'>3</a>";
    echo "<a class='starimg $star4 4star'>4</a>";
    echo "<a class='starimg $star5 5star'>5</a>";
    echo "</div>";

    echo "<script>var {$containername}tooltip = \"$tooltip\";</script>";
    echo "<div style='float: left; clear: left' onmouseover=\"Tip({$containername}tooltip)\" onmouseout=\"UnTip()\">";
    echo "<span class='$labelname'>$labelcontent</span>";
    echo "</div>";

    echo "</div>";
    echo "<br>";
};

RenderHtmlStart(true);

?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($gameData['Title'] . ' (' . $gameData['ConsoleName'] .')'); ?>
</head>
<body>
<?php RenderHeader($userDetails); ?>
<script>
var lastKnownAchRating = <?= $gameRating[RatingType::Achievement]['AverageRating'] ?>;
var lastKnownGameRating = <?= $gameRating[RatingType::Game]['AverageRating'] ?>;
var lastKnownAchRatingCount = <?= $gameRating[RatingType::Achievement]['RatingCount'] ?>;
var lastKnownGameRatingCount = <?= $gameRating[RatingType::Game]['RatingCount'] ?>;

function SetLitStars(container, numStars) {
    $(container + ' a').removeClass('starlit');
    $(container + ' a').removeClass('starhalf');

    if (numStars >= 0.5)
        $(container + ' a:first-child').addClass('starhalf');
    if (numStars >= 1.5)
        $(container + ' a:first-child + a').addClass('starhalf');
    if (numStars >= 2.5)
        $(container + ' a:first-child + a + a').addClass('starhalf');
    if (numStars >= 3.5)
        $(container + ' a:first-child + a + a + a').addClass('starhalf');
    if (numStars >= 4.5)
        $(container + ' a:first-child + a + a + a + a').addClass('starhalf');

    if (numStars >= 1) {
        $(container + ' a:first-child').removeClass('starhalf');
        $(container + ' a:first-child').addClass('starlit');
    }

    if (numStars >= 2) {
        $(container + ' a:first-child + a').removeClass('starhalf');
        $(container + ' a:first-child + a').addClass('starlit');
    }

    if (numStars >= 3) {
        $(container + ' a:first-child + a + a').removeClass('starhalf');
        $(container + ' a:first-child + a + a').addClass('starlit');
    }

    if (numStars >= 4) {
        $(container + ' a:first-child + a + a + a').removeClass('starhalf');
        $(container + ' a:first-child + a + a + a').addClass('starlit');
    }

    if (numStars >= 5) {
        $(container + ' a:first-child + a + a + a + a').removeClass('starhalf');
        $(container + ' a:first-child + a + a + a + a').addClass('starlit');
    }
}

function UpdateRating(container, label, rating, raters) {
    if (raters < <?= $minimumNumberOfRatingsToDisplay ?>) {
        SetLitStars(container, 0);
        label.html('More ratings needed (' + raters + ' votes)');
    } else {
        SetLitStars(container, rating);
        label.html('Rating: ' + rating.toFixed(2) + ' (' + raters + ' votes)');
    }
}

function UpdateRatings() {
    UpdateRating('#ratinggame', $('.ratinggamelabel'), lastKnownGameRating, lastKnownGameRatingCount);
    UpdateRating('#ratingach', $('.ratingachlabel'), lastKnownAchRating, lastKnownAchRatingCount);
}

function SubmitRating(gameID, ratingObjectType, value) {
    $.ajax({
        url: '/request/game/update-rating.php?i=' + gameID + '&t=' + ratingObjectType + '&v=' + value,
        dataType: 'json',
        success: function (results) {
        if (ratingObjectType == <?= RatingType::Game ?>) {
            $('.ratinggamelabel').html('Rating: ...');
        } else {
            $('.ratingachlabel').html('Rating: ...');
        }

        $.ajax({
            url: '/request/game/rating.php?i=' + gameID,
            dataType: 'json',
            success: function (results) {
            lastKnownGameRating = parseFloat(results.Ratings['Game']);
            lastKnownAchRating = parseFloat(results.Ratings['Achievements']);
            lastKnownGameRatingCount = results.Ratings['GameNumVotes'];
            lastKnownAchRatingCount = results.Ratings['AchievementsNumVotes'];

            UpdateRatings();

            if (ratingObjectType == <?= RatingType::Game ?>) {
                index = ratinggametooltip.indexOf("Your rating: ") + 13;
                index2 = ratinggametooltip.indexOf("</td>", index);
                ratinggametooltip = ratinggametooltip.substring(0, index) + value + "<br><i>Distribution may have changed</i>" + ratinggametooltip.substring(index2);
            } else {
                index = ratingachtooltip.indexOf("Your rating: ") + 13;
                index2 = ratingachtooltip.indexOf("</td>", index);
                ratingachtooltip = ratingachtooltip.substring(0, index) + value + "<br><i>Distribution may have changed</i>" + ratingachtooltip.substring(index2);
            }
            },
        });
        },
    });
}

// Onload:
$(function () {

    // Add these handlers onload, they don't exist yet
    $('.starimg').hover(
        function () {
        // On hover

        if ($(this).parent().is($('#ratingach'))) {
            // Ach:
            var numStars = 0;
            if ($(this).hasClass('1star'))
            numStars = 1;
            else if ($(this).hasClass('2star'))
            numStars = 2;
            else if ($(this).hasClass('3star'))
            numStars = 3;
            else if ($(this).hasClass('4star'))
            numStars = 4;
            else if ($(this).hasClass('5star'))
            numStars = 5;

            SetLitStars('#ratingach', numStars);
        } else {
            // Game:
            var numStars = 0;
            if ($(this).hasClass('1star'))
            numStars = 1;
            else if ($(this).hasClass('2star'))
            numStars = 2;
            else if ($(this).hasClass('3star'))
            numStars = 3;
            else if ($(this).hasClass('4star'))
            numStars = 4;
            else if ($(this).hasClass('5star'))
            numStars = 5;

            SetLitStars('#ratinggame', numStars);
        }
    });

    $('.rating').hover(
        function () {
        // On hover
        },
        function () {
        // On leave
        UpdateRatings();
    });

    $('.starimg').click(function () {

        var numStars = 0;
        if ($(this).hasClass('1star'))
        numStars = 1;
        else if ($(this).hasClass('2star'))
        numStars = 2;
        else if ($(this).hasClass('3star'))
        numStars = 3;
        else if ($(this).hasClass('4star'))
        numStars = 4;
        else if ($(this).hasClass('5star'))
        numStars = 5;

        var ratingType = 1;
        if ($(this).parent().is($('#ratingach')))
        ratingType = 3;

        SubmitRating(<?= $gameID ?>, ratingType, numStars);
    });

});
</script>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderGameHeader($gameData, 'Overview'); ?>
        <div style='margin-top: 4px'>
            <div style='float: right'>
                <img src='<?= $gameData['ImageBoxArt'] ?>' style='max-width: 256px'/>
            </div>
            <div style='overflow: hidden'>
            <?php
            RenderPageStatistic('Achievements', $numAchievements);
            RenderPageStatistic('Points', $numPoints);
            RenderPageStatistic('Leaderboards', $numLeaderboards);
            RenderPageStatistic('Players', $gameData['NumDistinctPlayersCasual']);
            $renderRatingControl('Game Rating', 'ratinggame', 'ratinggamelabel', $gameRating[RatingType::Game]);
            ?>
            </div>
            <div style='margin-top:10px; margin-right:266px; overflow: hidden'>
                <table class='gameinfo'><tbody>
                <tr><td>System:</td><td><b><a href='/gameList.php?c=<?= $gameData['ConsoleID'] ?>'><?= $gameData['ConsoleName'] ?></a></b></td></tr>
                <?php
                RenderMetadataTableRow('Developer', $gameData['Developer'] ?? null, $gameHubs, ['Hacker']);
                RenderMetadataTableRow('Publisher', $gameData['Publisher'] ?? null, $gameHubs, ['Hacks']);
                RenderMetadataTableRow('Genre', $gameData['Genre'] ?? null, $gameHubs, ['Subgenre']);
                RenderMetadataTableRow('Released', $gameData['Released'] ?? null);
                ?>
                </tbody></table>
            </div>
            <div class='gamescreenshots' style='margin-top: 20px; margin-right:266px; width=100%'>
                <div style='float:left; width:50%'>
                    <img src='<?= $gameData['ImageTitle'] ?>' style='max-width:<?= $screenshotWidth ?>px;max-height:<?= $screenshotMaxHeight ?>px;margin-left:auto;margin-right:auto;display:block' alt='Title Screenshot'>
                </div>
                <div style='float:left; width:50%'>
                    <img src='<?= $gameData['ImageIngame'] ?>' style='max-width:<?= $screenshotWidth ?>px;max-height:<?= $screenshotMaxHeight ?>px;margin-left:auto;margin-right:auto;display:block' alt='In-game Screenshot'>
                </div>
            </div>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
