<?php

namespace Bga\Games\skarabrae\Common;

use Bga\Games\skarabrae\Game;

class SoloChallenge {
    const LEGACY_BEST_SCORE = "bscore";
    const LEGACY_CHALLENGE_PREFIX = "cscore";
    const LEGACY_LEADERBOARD_PREFIX = "lb";

    function __construct(private Game $game, private int $challengeNumber = 0) {}

    // --- Best score (beat your own) ---

    function getBestScore(int $playerId): ?int {
        $stored = $this->game->legacy->get(self::LEGACY_BEST_SCORE, $playerId, null);
        if ($stored !== null) {
            return (int) $stored;
        }
        return null;
    }

    function setBestScore(int $playerId, int $score): void {
        $this->game->setPersistent(self::LEGACY_BEST_SCORE, $playerId, $score);
    }

    function scoreBeatOwnScore(int $playerId, int $score): void {
        $bestScore = $this->getBestScore($playerId);
        if ($bestScore !== null) {
            $this->game->notify->all("message", clienttranslate('${player_name} previous best score is ${points}'), [
                "points" => $bestScore,
            ]);
        } else {
            $this->game->notify->all("message", clienttranslate('${player_name} has no previous best score'));
            $bestScore = 0;
        }
        if ($score <= $bestScore) {
            $this->game->notify->all(
                "message",
                clienttranslate('${player_name} did not beat their best score of ${points}, score is negated'),
                [
                    "points" => $bestScore,
                ]
            );
            $this->game->playerScore->set($playerId, -1);
            $this->setBestScore($playerId, $bestScore); // refresh TTL
        } else {
            $this->setBestScore($playerId, $score);
            $this->game->notify->all("message", clienttranslate('${player_name} sets a new best personal score of ${points}!'), [
                "points" => $score,
            ]);
        }
    }

    // --- Challenge mode ---

    function getChallengeSeed(): int {
        $year = (int) date("o"); // ISO year
        $week = (int) date("W"); // ISO week number
        return $year * 10000 + $week * 100 + $this->challengeNumber;
    }

    function getChallengeWeek(): string {
        return date("o") . date("W"); // e.g. "202627"
    }

    function getChallengeLegacyKey(): string {
        return self::LEGACY_CHALLENGE_PREFIX . $this->challengeNumber;
    }

    function seedSetup(): void {
        mt_srand($this->getChallengeSeed());
    }

    function getChallengeGoal(int $playerId, int $minScore): int {
        $key = $this->getChallengeLegacyKey();
        $stored = $this->game->legacy->get($key, $playerId, null);
        $currentWeek = $this->getChallengeWeek();
        if ($stored !== null) {
            $parts = explode(":", (string) $stored);
            if (count($parts) == 2 && $parts[0] === $currentWeek) {
                $bestScore = (int) $parts[1];
                $this->game->notify->all("message", clienttranslate('${player_name} previous challenge score is ${points}'), [
                    "points" => $bestScore,
                ]);
                return max($minScore, $bestScore);
            }
        }
        return $minScore;
    }

    function scoreSoloChallenge(int $playerId, int $score, int $minScore): void {
        $key = $this->getChallengeLegacyKey();
        $currentWeek = $this->getChallengeWeek();
        $bestScore = 0;

        $stored = $this->game->legacy->get($key, $playerId, null);
        if ($stored !== null) {
            $parts = explode(":", (string) $stored);
            if (count($parts) == 2 && $parts[0] === $currentWeek) {
                $bestScore = (int) $parts[1];
                $this->game->notify->all("message", clienttranslate('${player_name} previous challenge score is ${points}'), [
                    "points" => $bestScore,
                ]);
            } else {
                $this->game->notify->all("message", clienttranslate('${player_name} has no previous score for this week\'s challenge'));
            }
        }

        if ($score < $minScore) {
            $this->game->notify->all("message", clienttranslate('${player_name} scores less than ${points}, score is negated'), [
                "points" => $minScore,
            ]);
            $this->game->playerScore->set($playerId, -1);
        } elseif ($bestScore > 0 && $score <= $bestScore) {
            $this->game->notify->all(
                "message",
                clienttranslate('${player_name} did not beat their challenge score of ${points}, score is negated'),
                ["points" => $bestScore]
            );
            $this->game->playerScore->set($playerId, -1);
        } else {
            $this->game->notify->all("message", clienttranslate('${player_name} sets a new challenge best of ${points}!'), [
                "points" => $score,
            ]);
        }
        $newBest = max($score, $bestScore);
        $this->game->setPersistent($key, $playerId, "$currentWeek:$newBest");
        $this->setBestScore($playerId, $score);

        // Update leaderboard if score qualifies
        if ($score >= $minScore) {
            $playerName = $this->game->getPlayerNameById($playerId);
            $this->updateLeaderboard($playerId, $playerName, $score);
        }
    }

    function getPlayerChallengeScore(int $playerId): ?int {
        $key = $this->getChallengeLegacyKey();
        $stored = $this->game->legacy->get($key, $playerId, null);
        if ($stored === null) {
            return null;
        }
        $parts = explode(":", (string) $stored);
        if (count($parts) == 2 && $parts[0] === $this->getChallengeWeek()) {
            return (int) $parts[1];
        }
        return null;
    }

    // --- Leaderboard ---

    function getLeaderboardKey(): string {
        return self::LEGACY_LEADERBOARD_PREFIX . $this->challengeNumber;
    }

    /**
     * Leaderboard stored as plain string: "YYYYWW;pid,name,score;pid,name,score;..."
     * Names cannot contain ; or , characters (replaced with space on store).
     */
    function getLeaderboard(): array {
        $key = $this->getLeaderboardKey();
        $stored = $this->game->legacy->get($key, 0, null);
        if ($stored === null || !is_string($stored)) {
            return [];
        }
        $parts = explode(";", $stored);
        $week = array_shift($parts);
        if ($week !== $this->getChallengeWeek()) {
            return [];
        }
        $entries = [];
        foreach ($parts as $part) {
            $fields = explode(",", $part);
            if (count($fields) === 3) {
                $entries[] = ["p" => (int) $fields[0], "n" => $fields[1], "s" => (int) $fields[2]];
            }
        }
        return $entries;
    }

    function updateLeaderboard(int $playerId, string $playerName, int $score): void {
        $key = $this->getLeaderboardKey();
        $currentWeek = $this->getChallengeWeek();
        $entries = $this->getLeaderboard();

        // Sanitize name (no ; or ,)
        $playerName = str_replace([";", ","], " ", $playerName);

        // Update or insert player entry
        $found = false;
        foreach ($entries as &$entry) {
            if ($entry["p"] == $playerId) {
                $entry["n"] = $playerName; // always refresh name
                if ($score > $entry["s"]) {
                    $entry["s"] = $score;
                }
                $found = true;
                break;
            }
        }
        unset($entry);
        if (!$found) {
            $entries[] = ["p" => $playerId, "n" => $playerName, "s" => $score];
        }

        // Sort by score descending, keep top 10
        usort($entries, fn($a, $b) => $b["s"] <=> $a["s"]);
        $entries = array_slice($entries, 0, 10);

        // Encode: "YYYYWW;pid,name,score;pid,name,score;..."
        $rows = [];
        foreach ($entries as $e) {
            $rows[] = $e["p"] . "," . $e["n"] . "," . $e["s"];
        }
        $data = $currentWeek . ";" . implode(";", $rows);
        $this->game->setPersistent($key, 0, $data);
    }
}
