<?php

namespace Bga\Games\skarabrae\Common;

use Bga\Games\skarabrae\Game;

class SoloChallenge {
    const LEGACY_BEST_SCORE = "bscore";
    const LEGACY_CHALLENGE_PREFIX = "cscore";

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
    }
}
