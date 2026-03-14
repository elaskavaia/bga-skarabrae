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
        $this->game->legacy->set($challenge->getChallengeLegacyKey(), $playerId, "$week:55");
        $goal = $challenge->getChallengeGoal($playerId, 45);
        $this->assertEquals(55, $goal);
    }

    public function testChallengeGoalExpiredWeek() {
        $challenge = new SoloChallenge($this->game, 2);
        $playerId = 10;
        // Store score from a different week
        $this->game->legacy->set($challenge->getChallengeLegacyKey(), $playerId, "999999:55");
        $goal = $challenge->getChallengeGoal($playerId, 45);
        $this->assertEquals(45, $goal, "Expired week score should be ignored");
    }

    public function testSoloChallengeScoringNewBest() {
        $challenge = new SoloChallenge($this->game, 3);
        $playerId = 10;
        $challenge->scoreSoloChallenge($playerId, 50, 45);
        $this->assertEquals(50, $challenge->getBestScore($playerId));
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
        $this->game->legacy->set($challenge->getChallengeLegacyKey(), $playerId, "$week:60");
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

    public function testLeaderboardResetsOnNewWeek() {
        $challenge = new SoloChallenge($this->game, 1);
        $playerId = 10;

        // Store leaderboard data from a different week
        $staleData = "999999;$playerId,Alice,80;2300663,Bob,70";
        $this->game->legacy->set($challenge->getLeaderboardKey(), 0, $staleData);

        // Reading should return empty (week mismatch = reset)
        $entries = $challenge->getLeaderboard();
        $this->assertEmpty($entries, "Leaderboard from old week should be empty");

        // Writing a new entry should start fresh
        $challenge->updateLeaderboard($playerId, "Alice", 50);
        $entries = $challenge->getLeaderboard();
        $this->assertCount(1, $entries, "Leaderboard should have 1 entry after reset");
        $this->assertEquals(50, $entries[0]["s"]);
    }

    public function testLeaderboardTop10Limit() {
        $challenge = new SoloChallenge($this->game, 2);

        // Add 12 players
        for ($i = 1; $i <= 12; $i++) {
            $challenge->updateLeaderboard(1000 + $i, "Player$i", 40 + $i);
        }
        $entries = $challenge->getLeaderboard();
        $this->assertCount(10, $entries, "Leaderboard should be capped at 10");
        $this->assertEquals(52, $entries[0]["s"], "Top score should be 52");
        $this->assertEquals(43, $entries[9]["s"], "10th score should be 43");
    }

    public function testLeaderboardUpdatesExistingPlayer() {
        $challenge = new SoloChallenge($this->game, 3);
        $playerId = 10;

        $challenge->updateLeaderboard($playerId, "Alice", 50);
        $challenge->updateLeaderboard($playerId, "Alice", 60);
        $entries = $challenge->getLeaderboard();
        $this->assertCount(1, $entries, "Should have 1 entry, not 2");
        $this->assertEquals(60, $entries[0]["s"], "Score should be updated to 60");
    }

    public function testLeaderboardKeepsHigherScore() {
        $challenge = new SoloChallenge($this->game, 4);
        $playerId = 10;

        $challenge->updateLeaderboard($playerId, "Alice", 60);
        $challenge->updateLeaderboard($playerId, "Alice", 50);
        $entries = $challenge->getLeaderboard();
        $this->assertEquals(60, $entries[0]["s"], "Higher score should be kept");
    }

    public function testChallengeScoreResetsOnNewWeek() {
        $challenge = new SoloChallenge($this->game, 5);
        $playerId = 10;

        // Store a score from a different week
        $this->game->legacy->set($challenge->getChallengeLegacyKey(), $playerId, "999999:80");

        // Should be treated as no score (expired)
        $score = $challenge->getPlayerChallengeScore($playerId);
        $this->assertNull($score, "Score from old week should be null");

        $goal = $challenge->getChallengeGoal($playerId, 45);
        $this->assertEquals(45, $goal, "Goal should be minScore when old week score expired");
    }
}
