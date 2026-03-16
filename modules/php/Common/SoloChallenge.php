<?php

namespace Bga\Games\skarabrae\Common;

use Bga\Games\skarabrae\Game;

class SoloChallenge {
    const LEGACY_BEST_SCORE = "bscore";
    const LEGACY_CHALLENGE_PREFIX = "cscore";
    const LEGACY_LEADERBOARD_PREFIX = "lb";

    private string $isoYear;
    private string $isoWeek;

    function __construct(private Game $game, private int $challengeNumber = 0) {
        // Use UTC to ensure consistent week calculation across all BGA server nodes
        $utc = new \DateTimeImmutable("now", new \DateTimeZone("UTC"));
        $this->isoYear = $utc->format("o");
        $this->isoWeek = $utc->format("W");
    }

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
        return (int) $this->isoYear * 10000 + (int) $this->isoWeek * 100 + $this->challengeNumber;
    }

    function getChallengeWeek(): string {
        return $this->isoYear . $this->isoWeek;
    }

    /** Returns current week as integer YYYYWW, e.g. 202511 */
    function getChallengeWeekNum(): int {
        return (int) $this->isoYear * 100 + (int) $this->isoWeek;
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

    /** Read per-player challenge data from legacy store, parsed into weekly format */
    private function getPlayerChallengeData(int $playerId): array {
        $key = $this->getChallengeLegacyKey();
        $stored = $this->game->legacy->get($key, $playerId, null);
        return self::parseWeeklyData($stored);
    }

    /** Save per-player challenge data back to legacy store */
    private function setPlayerChallengeData(int $playerId, array $allData): void {
        $key = $this->getChallengeLegacyKey();
        $this->game->setPersistent($key, $playerId, self::encodeWeeklyData($allData, $this->getGameStartWeek(), 1));
    }

    function getChallengeGoal(int $playerId, int $minScore): int {
        $gameWeek = $this->getGameStartWeek();
        $data = $this->getPlayerChallengeData($playerId);
        $entries = $data[$gameWeek] ?? [];
        if (!empty($entries)) {
            $bestScore = $entries[0]["s"];
            $this->game->notify->all("message", clienttranslate('${player_name} previous challenge score is ${points}'), [
                "points" => $bestScore,
            ]);
            return max($minScore, $bestScore);
        }
        return $minScore;
    }

    function scoreSoloChallenge(int $playerId, int $score, int $minScore): void {
        $gameWeek = $this->getGameStartWeek();
        $currentWeek = $this->getChallengeWeek();
        $data = $this->getPlayerChallengeData($playerId);
        $entries = $data[$gameWeek] ?? [];
        $bestScore = !empty($entries) ? $entries[0]["s"] : 0;

        if ($bestScore > 0) {
            $this->game->notify->all("message", clienttranslate('${player_name} previous challenge score is ${points}'), [
                "points" => $bestScore,
            ]);
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

        // Store per-player best score
        $playerName = $this->game->getPlayerNameById($playerId);
        self::upsertEntry($data, $gameWeek, $playerId, $playerName, $score);
        $this->setPlayerChallengeData($playerId, $data);
        $this->setBestScore($playerId, $score);

        // Update shared leaderboard if score qualifies and game week is still recent
        if ($score >= $minScore && $this->isWeekRecent($gameWeek, $currentWeek)) {
            $this->updateLeaderboard($gameWeek, $playerId, $playerName, $score);
        }
    }

    function getPlayerChallengeScore(int $playerId): ?int {
        $gameWeek = $this->getGameStartWeek();
        $data = $this->getPlayerChallengeData($playerId);
        $entries = $data[$gameWeek] ?? [];
        return !empty($entries) ? $entries[0]["s"] : null;
    }

    // --- Leaderboard ---
    // Storage format: "YYYYWW;pid,name,score;...|YYYYWW;pid,name,score;..."
    // Two week slots separated by "|". Old format (single week, no "|") is also supported for reading.

    function getLeaderboardKey(): string {
        return self::LEGACY_LEADERBOARD_PREFIX . $this->challengeNumber;
    }

    /** Check if targetWeek is the same as currentWeek or the immediately previous ISO week */
    function isWeekRecent(string $targetWeek, string $currentWeek): bool {
        return self::isWeekRecentStatic($targetWeek, $currentWeek);
    }

    /**
     * Parse a stored string in format "YYYYWW;pid,name,score;...|YYYYWW;pid,name,score;..."
     * into array keyed by week string. Old format "YYYYWW:score" is silently dropped.
     */
    static function parseWeeklyData(?string $stored): array {
        if ($stored === null || !is_string($stored)) {
            return [];
        }
        $result = [];
        $segments = explode("|", $stored);
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === "" || !str_contains($segment, ";")) {
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

    private function parseLeaderboardData(): array {
        $key = $this->getLeaderboardKey();
        $stored = $this->game->legacy->get($key, 0, null);
        return self::parseWeeklyData($stored);
    }

    /**
     * Returns ["current" => ["entries" => [...], "week" => "YYYYWW"], "previous" => ["entries" => [...], "week" => "YYYYWW"] | null].
     */
    function getLeaderboard(?string $forWeek = null): array {
        $forWeek = $forWeek ?? $this->getGameStartWeek();
        $data = $this->parseLeaderboardData();
        $currentWeek = $this->getChallengeWeek();

        $result = [
            "current" => ["entries" => $data[$forWeek] ?? [], "week" => $forWeek],
            "previous" => null,
        ];

        // Include previous week if it exists and is different from current
        foreach ($data as $week => $entries) {
            if ($week !== $forWeek && $this->isWeekRecent($week, $currentWeek) && !empty($entries)) {
                $result["previous"] = ["entries" => $entries, "week" => $week];
                break;
            }
        }

        return $result;
    }

    /**
     * Encode weekly data back to storage format "YYYYWW;pid,name,score;...|YYYYWW;pid,name,score;..."
     * Only keeps weeks that are recent relative to $currentWeek.
     */
    static function encodeWeeklyData(array $allData, string $currentWeek, int $maxPerWeek = 10): string {
        $segments = [];
        foreach ($allData as $week => $entries) {
            if (empty($entries) || !self::isWeekRecentStatic($week, $currentWeek)) {
                continue;
            }
            usort($entries, fn($a, $b) => $b["s"] <=> $a["s"]);
            $entries = array_slice($entries, 0, $maxPerWeek);
            $rows = [];
            foreach ($entries as $e) {
                $rows[] = $e["p"] . "," . $e["n"] . "," . $e["s"];
            }
            $segments[] = $week . ";" . implode(";", $rows);
        }
        return implode("|", $segments);
    }

    /**
     * Update or insert a player entry in the parsed weekly data.
     * Only keeps the higher score if the player already has an entry for that week.
     */
    static function upsertEntry(array &$allData, string $forWeek, int $playerId, string $playerName, int $score): void {
        $playerName = str_replace([";", ",", "|"], " ", $playerName);
        $entries = $allData[$forWeek] ?? [];
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
        $allData[$forWeek] = $entries;
    }

    static function isWeekRecentStatic(string $targetWeek, string $currentWeek): bool {
        if ($targetWeek === $currentWeek) {
            return true;
        }
        $currentYear = (int) substr($currentWeek, 0, 4);
        $currentW = (int) substr($currentWeek, 4, 2);
        if ($currentW > 1) {
            $prevWeek = $currentYear . str_pad((string) ($currentW - 1), 2, "0", STR_PAD_LEFT);
        } else {
            $prevYear = $currentYear - 1;
            $prevW = (int) date("W", mktime(0, 0, 0, 12, 28, $prevYear));
            $prevWeek = $prevYear . str_pad((string) $prevW, 2, "0", STR_PAD_LEFT);
        }
        return $targetWeek === $prevWeek;
    }

    function updateLeaderboard(string $forWeek, int $playerId, string $playerName, int $score): void {
        $key = $this->getLeaderboardKey();
        $gameWeek = $this->getGameStartWeek();
        $allData = $this->parseLeaderboardData();
        self::upsertEntry($allData, $forWeek, $playerId, $playerName, $score);
        $this->game->setPersistent($key, 0, self::encodeWeeklyData($allData, $this->getGameStartWeek()));
    }
}
