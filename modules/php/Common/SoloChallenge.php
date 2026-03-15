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

    /** Returns current week as integer YYYYWW, e.g. 202511 */
    function getChallengeWeekNum(): int {
        return (int) date("o") * 100 + (int) date("W");
    }

    /** Returns the week stored at game start, or current week if not stored */
    function getGameStartWeek(): string {
        $stored = (int) $this->game->getGameStateValue("challenge_week_start");
        if ($stored > 0) {
            // stored as YYYYWW integer, convert back to string
            $year = intdiv($stored, 100);
            $week = $stored % 100;
            return $year . str_pad((string) $week, 2, "0", STR_PAD_LEFT);
        }
        return $this->getChallengeWeek();
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
        $gameWeek = $this->getGameStartWeek();
        $currentWeek = $this->getChallengeWeek();
        $bestScore = 0;

        // Use the game's start week for score comparison (not live week)
        $stored = $this->game->legacy->get($key, $playerId, null);
        if ($stored !== null) {
            $parts = explode(":", (string) $stored);
            if (count($parts) == 2 && $parts[0] === $gameWeek) {
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
        $this->game->setPersistent($key, $playerId, "$gameWeek:$newBest");
        $this->setBestScore($playerId, $score);

        // Update leaderboard if score qualifies and game week is still recent
        if ($score >= $minScore && $this->isWeekRecent($gameWeek, $currentWeek)) {
            $playerName = $this->game->getPlayerNameById($playerId);
            $this->updateLeaderboard($gameWeek, $playerId, $playerName, $score);
        }
    }

    function getPlayerChallengeScore(int $playerId): ?int {
        $key = $this->getChallengeLegacyKey();
        $stored = $this->game->legacy->get($key, $playerId, null);
        if ($stored === null) {
            return null;
        }
        $parts = explode(":", (string) $stored);
        $gameWeek = $this->getGameStartWeek();
        if (count($parts) == 2 && $parts[0] === $gameWeek) {
            return (int) $parts[1];
        }
        return null;
    }

    // --- Leaderboard ---
    // Storage format: "YYYYWW;pid,name,score;...|YYYYWW;pid,name,score;..."
    // Two week slots separated by "|". Old format (single week, no "|") is also supported for reading.

    function getLeaderboardKey(): string {
        return self::LEGACY_LEADERBOARD_PREFIX . $this->challengeNumber;
    }

    /** Check if targetWeek is the same as currentWeek or the immediately previous ISO week */
    function isWeekRecent(string $targetWeek, string $currentWeek): bool {
        if ($targetWeek === $currentWeek) {
            return true;
        }
        // Calculate previous week from current
        $currentYear = (int) substr($currentWeek, 0, 4);
        $currentW = (int) substr($currentWeek, 4, 2);
        if ($currentW > 1) {
            $prevWeek = $currentYear . str_pad((string) ($currentW - 1), 2, "0", STR_PAD_LEFT);
        } else {
            // Week 1 → previous is last week of prior year
            $prevYear = $currentYear - 1;
            $prevW = (int) date("W", mktime(0, 0, 0, 12, 28, $prevYear)); // ISO week of Dec 28
            $prevWeek = $prevYear . str_pad((string) $prevW, 2, "0", STR_PAD_LEFT);
        }
        return $targetWeek === $prevWeek;
    }

    /**
     * Parse stored leaderboard string into array keyed by week string.
     * Supports both old format "YYYYWW;entries" and new "YYYYWW;entries|YYYYWW;entries".
     */
    private function parseLeaderboardData(): array {
        $key = $this->getLeaderboardKey();
        $stored = $this->game->legacy->get($key, 0, null);
        if ($stored === null || !is_string($stored)) {
            return [];
        }
        $result = [];
        // Split by "|" for multi-week format; old format has no "|" so yields one segment
        $segments = explode("|", $stored);
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === "") {
                continue;
            }
            $parts = explode(";", $segment);
            $week = array_shift($parts);
            if (!$week) {
                continue;
            }
            $entries = [];
            foreach ($parts as $part) {
                $fields = explode(",", $part);
                if (count($fields) === 3) {
                    $entries[] = ["p" => (int) $fields[0], "n" => $fields[1], "s" => (int) $fields[2]];
                }
            }
            $result[$week] = $entries;
        }
        return $result;
    }

    /** Get leaderboard entries for a specific week (defaults to game start week) */
    function getLeaderboard(?string $forWeek = null): array {
        $forWeek = $forWeek ?? $this->getGameStartWeek();
        $data = $this->parseLeaderboardData();
        return $data[$forWeek] ?? [];
    }

    private function encodeWeekEntries(string $week, array $entries): string {
        $rows = [];
        foreach ($entries as $e) {
            $rows[] = $e["p"] . "," . $e["n"] . "," . $e["s"];
        }
        return $week . ";" . implode(";", $rows);
    }

    function updateLeaderboard(string $forWeek, int $playerId, string $playerName, int $score): void {
        $key = $this->getLeaderboardKey();
        $currentWeek = $this->getChallengeWeek();
        $allData = $this->parseLeaderboardData();

        // Sanitize name (no ; or , or |)
        $playerName = str_replace([";", ",", "|"], " ", $playerName);

        // Get or init entries for the target week
        $entries = $allData[$forWeek] ?? [];

        // Update or insert player entry
        $found = false;
        foreach ($entries as &$entry) {
            if ($entry["p"] == $playerId) {
                $entry["n"] = $playerName;
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

        $allData[$forWeek] = $entries;

        // Keep only current and previous week, drop anything older
        $segments = [];
        foreach ($allData as $week => $weekEntries) {
            if ($this->isWeekRecent($week, $currentWeek) && !empty($weekEntries)) {
                $segments[] = $this->encodeWeekEntries($week, $weekEntries);
            }
        }

        $data = implode("|", $segments);
        $this->game->setPersistent($key, 0, $data);
    }
}
