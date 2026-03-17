<?php

declare(strict_types=1);

use Bga\Games\skarabrae\Common\SoloChallenge;
use Bga\Games\skarabrae\Game;
use Bga\Games\skarabrae\Material;
use PHPUnit\Framework\TestCase;

final class SoloChallengeTest extends TestCase {
    private Game $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
    }

    public function testGetChallengeSeed() {
        $challenge = new SoloChallenge($this->game, 3);
        $seed = $challenge->getChallengeSeed();
        $this->assertEquals(3, $seed % 100, "Challenge number should be 03");
        $this->assertGreaterThan(20250000, $seed, "Seed should encode year and week");
    }

    public function testGetChallengeSeedDifferentNumbers() {
        $c1 = new SoloChallenge($this->game, 1);
        $c5 = new SoloChallenge($this->game, 5);
        $this->assertNotEquals($c1->getChallengeSeed(), $c5->getChallengeSeed());
    }

    public function testGetChallengeWeek() {
        $challenge = new SoloChallenge($this->game);
        $week = $challenge->getChallengeWeek();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $week);
    }

    public function testGetChallengeLegacyKey() {
        $challenge = new SoloChallenge($this->game, 4);
        $this->assertEquals(SoloChallenge::LEGACY_CHALLENGE_PREFIX . "4", $challenge->getChallengeLegacyKey());
    }

    public function testBestScoreRoundTrip() {
        $challenge = new SoloChallenge($this->game);
        $playerId = 10;
        $this->assertNull($challenge->getBestScore($playerId));
        $challenge->setBestScore($playerId, 52);
        $this->assertEquals(52, $challenge->getBestScore($playerId));
    }

    public function testScoreBeatOwnScoreNewBest() {
        $challenge = new SoloChallenge($this->game);
        $playerId = 10;
        $challenge->setBestScore($playerId, 40);
        $challenge->scoreBeatOwnScore($playerId, 50);
        $this->assertEquals(50, $challenge->getBestScore($playerId));
    }

    public function testScoreBeatOwnScoreNotBeaten() {
        $challenge = new SoloChallenge($this->game);
        $playerId = 10;
        $challenge->setBestScore($playerId, 50);
        $challenge->scoreBeatOwnScore($playerId, 45);
        $this->assertEquals(-1, $this->game->playerScore->get($playerId));
        $this->assertEquals(50, $challenge->getBestScore($playerId));
    }

    public function testScoreBeatOwnScoreFirstGame() {
        $challenge = new SoloChallenge($this->game);
        $playerId = 10;
        $challenge->scoreBeatOwnScore($playerId, 50);
        $this->assertEquals(50, $challenge->getBestScore($playerId));
    }

    public function testChallengeGoalNoPreviousScore() {
        $challenge = new SoloChallenge($this->game, 1);
        $goal = $challenge->getChallengeGoal(10, 45);
        $this->assertEquals(45, $goal);
    }

    public function testChallengeGoalWithPreviousScore() {
        $challenge = new SoloChallenge($this->game, 2);
        $playerId = 10;
        $week = $challenge->getChallengeWeek();
        $this->game->legacy->set($challenge->getChallengeLegacyKey(), $playerId, "$week;$playerId,Alice,55");
        $goal = $challenge->getChallengeGoal($playerId, 45);
        $this->assertEquals(55, $goal);
    }

    public function testChallengeGoalExpiredWeek() {
        $challenge = new SoloChallenge($this->game, 2);
        $playerId = 10;
        // Store score from a different week
        $this->game->legacy->set($challenge->getChallengeLegacyKey(), $playerId, "999999;$playerId,Alice,55");
        $goal = $challenge->getChallengeGoal($playerId, 45);
        $this->assertEquals(45, $goal, "Expired week score should be ignored");
    }

    public function testSoloChallengeScoringNewBest() {
        $challenge = new SoloChallenge($this->game, 3);
        $playerId = 10;
        $challenge->scoreSoloChallenge($playerId, 50, 45);
        $this->assertEquals(50, $challenge->getPlayerChallengeScore($playerId));
        $stored = $this->game->legacy->get($challenge->getChallengeLegacyKey(), $playerId, null);
        $this->assertNotNull($stored);
        $this->assertStringContainsString("50", (string) $stored);
    }

    public function testSoloChallengeScoringBelowMin() {
        $challenge = new SoloChallenge($this->game, 4);
        $playerId = 10;
        $challenge->scoreSoloChallenge($playerId, 30, 45);
        $this->assertEquals(-1, $this->game->playerScore->get($playerId));
    }

    public function testSoloChallengeScoringNotBeaten() {
        // Player must beat their own previous best to win
        $challenge = new SoloChallenge($this->game, 5);
        $playerId = 10;
        $week = $challenge->getChallengeWeek();
        $this->game->legacy->set($challenge->getChallengeLegacyKey(), $playerId, "$week;$playerId,Alice,60");
        $challenge->scoreSoloChallenge($playerId, 55, 45);
        $this->assertEquals(-1, $this->game->playerScore->get($playerId));
    }

    public function testSeedSetup() {
        $challenge = new SoloChallenge($this->game, 1);
        $challenge->seedSetup();
        $arr1 = range(1, 20);
        shuffle($arr1);

        // Same seed should produce same result
        $challenge->seedSetup();
        $arr2 = range(1, 20);
        shuffle($arr2);

        $this->assertEquals($arr1, $arr2, "seedSetup should produce deterministic shuffles");
    }

    public function testSetupGameTablesDeterministic() {
        $color = PCOLOR;

        // Run setup twice with solo challenge mode, compare results
        $snapshots = [];
        for ($i = 0; $i < 2; $i++) {
            $game = new GameUT();
            $game->setPlayersNumber(1);
            $game->_setCurrentPlayerId(10);
            $game->gamestate->changeActivePlayer(10);
            // Set solo challenge mode
            $game->setGameStateInitialValue("variant_solo_dif", Material::MA_GAMEOPTION_SOLO_DIFFICULTY_CHALLENGE);
            $game->setGameStateInitialValue("variant_challenge_type", 3);
            $game->setupGameTables();

            $snapshots[] = [
                "tasks" => array_keys($game->tokens->getTokensOfTypeInLocation("card_task", "tableau_$color")),
                "goals" => array_keys($game->tokens->getTokensOfTypeInLocation("card_goal", "tableau_$color")),
                "action" => array_keys($game->tokens->getTokensOfTypeInLocation("action_special", "tableau_$color")),
                "village_top5" => array_keys($game->tokens->db->getTokensOnTop(5, "deck_village")),
            ];
        }

        $this->assertEquals($snapshots[0]["tasks"], $snapshots[1]["tasks"], "Tasks should be identical");
        $this->assertEquals($snapshots[0]["goals"], $snapshots[1]["goals"], "Goals should be identical");
        $this->assertCount(1, $snapshots[0]["action"], "Challenge mode should auto-pick exactly 1 action tile");
        $this->assertEquals($snapshots[0]["action"], $snapshots[1]["action"], "Action tile should be identical");
        $this->assertEquals($snapshots[0]["village_top5"], $snapshots[1]["village_top5"], "Village deck order should be identical");
    }

    public function testDifferentWeeksProduceDifferentSetups() {
        $color = PCOLOR;

        // Same challenge number, different weeks — should produce different setups
        // Week 11: Monday March 10 2025 00:00 UTC
        // Week 12: Monday March 17 2025 00:00 UTC
        $timestamps = [
            "w11" => gmmktime(0, 0, 0, 3, 10, 2025),
            "w12" => gmmktime(0, 0, 0, 3, 17, 2025),
        ];

        $snapshots = [];
        foreach ($timestamps as $label => $ts) {
            $game = new GameUT();
            $game->setPlayersNumber(1);
            $game->_setCurrentPlayerId(10);
            $game->gamestate->changeActivePlayer(10);
            $game->setGameStateInitialValue("variant_solo_dif", Material::MA_GAMEOPTION_SOLO_DIFFICULTY_CHALLENGE);
            $game->setGameStateInitialValue("variant_challenge_type", 1);
            $game->challenge = new SoloChallenge($game, 1);
            $game->challenge->setNow($ts);
            $game->setupGameTables();

            $snapshots[$label] = [
                "tasks" => array_keys($game->tokens->getTokensOfTypeInLocation("card_task", "tableau_$color")),
                "goals" => array_keys($game->tokens->getTokensOfTypeInLocation("card_goal", "tableau_$color")),
                "action" => array_keys($game->tokens->getTokensOfTypeInLocation("action_special", "tableau_$color")),
                "village_top5" => array_keys($game->tokens->db->getTokensOnTop(5, "deck_village")),
            ];
        }

        $allSame = $snapshots["w11"]["tasks"] === $snapshots["w12"]["tasks"]
            && $snapshots["w11"]["goals"] === $snapshots["w12"]["goals"]
            && $snapshots["w11"]["action"] === $snapshots["w12"]["action"]
            && $snapshots["w11"]["village_top5"] === $snapshots["w12"]["village_top5"];
        $this->assertFalse($allSame, "Different weeks should produce different setups");
    }

    public function testLeaderboardResetsOnNewWeek() {
        $challenge = new SoloChallenge($this->game, 1);
        $playerId = 10;
        $week = $challenge->getChallengeWeek();

        // Store leaderboard data from a different week
        $staleData = "999999;$playerId,Alice,80;2300663,Bob,70";
        $this->game->legacy->set($challenge->getLeaderboardKey(), 0, $staleData);

        // Reading returns stale data (getLeaderboard does not filter)
        $lb = $challenge->getLeaderboard();
        $this->assertArrayHasKey("999999", $lb, "Stale week data is still present on read");
        $this->assertArrayNotHasKey($week, $lb, "Current week should not exist yet");

        // Writing a new entry should drop stale data (encodeWeeklyData filters)
        $challenge->updateLeaderboard($week, $playerId, 50);
        $lb = $challenge->getLeaderboard();
        $this->assertArrayNotHasKey("999999", $lb, "Stale week should be dropped after write");
        $this->assertCount(1, $lb[$week], "Leaderboard should have 1 entry after reset");
        $this->assertEquals(50, $lb[$week][0]["s"]);
    }

    public function testLeaderboardTop10Limit() {
        $challenge = new SoloChallenge($this->game, 2);
        $week = $challenge->getChallengeWeek();

        // Register 12 players
        $players = [];
        for ($i = 1; $i <= 12; $i++) {
            $players[1000 + $i] = ["player_name" => "Player$i"];
        }
        $this->game->_setPlayerBasicInfo($players);

        // Add 12 players
        for ($i = 1; $i <= 12; $i++) {
            $challenge->updateLeaderboard($week, 1000 + $i, 40 + $i);
        }
        $lb = $challenge->getLeaderboard();
        $this->assertCount(10, $lb[$week], "Leaderboard should be capped at 10");
        $this->assertEquals(52, $lb[$week][0]["s"], "Top score should be 52");
        $this->assertEquals(43, $lb[$week][9]["s"], "10th score should be 43");
    }

    public function testLeaderboard11thPlayerWithTiedScoreDoesNotMakeIt() {
        $challenge = new SoloChallenge($this->game, 3);
        $week = $challenge->getChallengeWeek();

        // Register 11 players
        $players = [];
        for ($i = 1; $i <= 10; $i++) {
            $players[1000 + $i] = ["player_name" => "Player$i"];
        }
        $players[2000] = ["player_name" => "Latecomer"];
        $this->game->_setPlayerBasicInfo($players);

        // Add 10 players all with score 50
        for ($i = 1; $i <= 10; $i++) {
            $challenge->updateLeaderboard($week, 1000 + $i, 50);
        }
        $lb = $challenge->getLeaderboard();
        $this->assertCount(10, $lb[$week]);

        // 11th player also scores 50 — should not make the leaderboard
        $challenge->updateLeaderboard($week, 2000, 50);
        $lb = $challenge->getLeaderboard();
        $this->assertCount(10, $lb[$week], "Should still be 10 entries");

        // Latecomer should not be on the board
        $names = array_map(fn($e) => $e["n"], $lb[$week]);
        $this->assertNotContains("Latecomer", $names, "11th player with tied score should not make leaderboard");
    }

    public function testLeaderboardUpdatesExistingPlayer() {
        $challenge = new SoloChallenge($this->game, 3);
        $playerId = 10;
        $week = $challenge->getChallengeWeek();

        $challenge->updateLeaderboard($week, $playerId, 50);
        $challenge->updateLeaderboard($week, $playerId, 60);
        $lb = $challenge->getLeaderboard();
        $this->assertCount(1, $lb[$week], "Should have 1 entry, not 2");
        $this->assertEquals(60, $lb[$week][0]["s"], "Score should be updated to 60");
    }

    public function testLeaderboardKeepsHigherScore() {
        $challenge = new SoloChallenge($this->game, 4);
        $playerId = 10;
        $week = $challenge->getChallengeWeek();

        $challenge->updateLeaderboard($week, $playerId, 60);
        $challenge->updateLeaderboard($week, $playerId, 50);
        $lb = $challenge->getLeaderboard();
        $this->assertEquals(60, $lb[$week][0]["s"], "Higher score should be kept");
    }

    public function testChallengeScoreResetsOnNewWeek() {
        $challenge = new SoloChallenge($this->game, 5);
        $playerId = 10;

        // Store a score from a different week
        $this->game->legacy->set($challenge->getChallengeLegacyKey(), $playerId, "999999;$playerId,Alice,80");

        // Should be treated as no score (expired)
        $score = $challenge->getPlayerChallengeScore($playerId);
        $this->assertNull($score, "Score from old week should be null");

        $goal = $challenge->getChallengeGoal($playerId, 45);
        $this->assertEquals(45, $goal, "Goal should be minScore when old week score expired");
    }

    public function testIsWeekRecent() {
        $challenge = new SoloChallenge($this->game, 1);

        // Same week
        $this->assertTrue($challenge->isWeekRecent("202511", "202511"), "Same week should be recent");

        // Previous week (normal case)
        $this->assertTrue($challenge->isWeekRecent("202510", "202511"), "Previous week should be recent");

        // Two weeks ago
        $this->assertFalse($challenge->isWeekRecent("202509", "202511"), "Two weeks ago should not be recent");

        // Year boundary: week 1 of 2026, previous is last week of 2025
        // 2025 has 52 ISO weeks (Dec 28 2025 is in week 52)
        $this->assertTrue($challenge->isWeekRecent("202552", "202601"), "Last week of prev year should be recent");

        // Stale across year boundary
        $this->assertFalse($challenge->isWeekRecent("202551", "202601"), "Two weeks before across year should not be recent");

        // Completely different year
        $this->assertFalse($challenge->isWeekRecent("202411", "202611"), "Different year should not be recent");
    }

    public function testLeaderboardTwoPlayers() {
        $challenge = new SoloChallenge($this->game, 1);
        $week = $challenge->getChallengeWeek();

        // Register players with names
        $this->game->_setPlayerBasicInfo([
            10 => ["player_name" => "Alice"],
            20 => ["player_name" => "Bob"],
        ]);

        // Initially empty
        $lb = $challenge->getLeaderboard();
        $this->assertEmpty($lb, "Leaderboard should start empty");

        // First player scores 50
        $challenge->updateLeaderboard($week, 10, 50);
        $lb = $challenge->getLeaderboard();
        $this->assertCount(1, $lb[$week], "Should have 1 entry");
        $this->assertEquals(50, $lb[$week][0]["s"]);
        $this->assertEquals("Alice", $lb[$week][0]["n"]);

        // Second player scores 47
        $challenge->updateLeaderboard($week, 20, 47);
        $lb = $challenge->getLeaderboard();
        $this->assertCount(2, $lb[$week], "Should have 2 entries");
        // Sorted by score descending
        $this->assertEquals(50, $lb[$week][0]["s"], "Alice should be first");
        $this->assertEquals(47, $lb[$week][1]["s"], "Bob should be second");
    }

    public function testChallengeScoringNewWeekWithHighPreviousScore() {
        // Player scored 65 last week, scores 46 this week — should win (>= 45)
        $challenge = new SoloChallenge($this->game, 1);
        $playerId = 10;
        $currentWeek = $challenge->getChallengeWeek();

        // Store last week's high score in new format
        $this->game->legacy->set($challenge->getChallengeLegacyKey(), $playerId, "999901;$playerId,Alice,65");

        // Score 46 this week — above minScore, no previous score this week
        $challenge->scoreSoloChallenge($playerId, 46, 45);

        // Should NOT be negated — 46 >= 45
        $score = $this->game->playerScore->get($playerId);
        $this->assertNotEquals(-1, $score, "Score >= minScore should win even if last week was higher");

        // Per-player best for this week should be 46
        $this->assertEquals(46, $challenge->getPlayerChallengeScore($playerId));
    }

    public function testOldFormatSilentlyDropped() {
        $challenge = new SoloChallenge($this->game, 1);
        $playerId = 10;
        $week = $challenge->getChallengeWeek();

        // Store in old "YYYYWW:score" format
        $this->game->legacy->set($challenge->getChallengeLegacyKey(), $playerId, "$week:55");

        // Should not crash, should return null (old format silently dropped)
        $score = $challenge->getPlayerChallengeScore($playerId);
        $this->assertNull($score, "Old format should be silently dropped");

        $goal = $challenge->getChallengeGoal($playerId, 45);
        $this->assertEquals(45, $goal, "Old format should be ignored for goal");

        // New score should overwrite old format data
        $challenge->scoreSoloChallenge($playerId, 50, 45);
        $this->assertEquals(50, $challenge->getPlayerChallengeScore($playerId), "New score should be stored in new format");
    }

    public function testEncodeWeeklyDataPreservesBothWeeks() {
        $allData = [
            "202611" => [["p" => 10, "n" => "Alice", "s" => 65]],
            "202612" => [["p" => 10, "n" => "Alice", "s" => 46]],
        ];
        $encoded = SoloChallenge::encodeWeeklyData($allData, "202612", 1);
        $parsed = SoloChallenge::parseWeeklyData($encoded);

        $this->assertCount(2, $parsed, "Both weeks should be preserved");
        $this->assertEquals(65, $parsed["202611"][0]["s"], "Previous week score should be preserved");
        $this->assertEquals(46, $parsed["202612"][0]["s"], "Current week score should be preserved");
    }

    public function testEncodeWeeklyDataDropsStaleWeek() {
        $allData = [
            "202609" => [["p" => 10, "n" => "Alice", "s" => 80]],
            "202612" => [["p" => 10, "n" => "Alice", "s" => 46]],
        ];
        $encoded = SoloChallenge::encodeWeeklyData($allData, "202612", 1);
        $parsed = SoloChallenge::parseWeeklyData($encoded);

        $this->assertCount(1, $parsed, "Only current week should remain");
        $this->assertArrayNotHasKey("202609", $parsed, "Stale week should be dropped");
    }
}
