<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use QVGDS\Game\Domain\Game;
use QVGDS\Game\Domain\GameId;
use QVGDS\Game\Domain\Joker\Joker;
use QVGDS\Game\Service\GamesManager;
use QVGDS\Session\Domain\Question\Answer;
use QVGDS\Session\Domain\SessionId;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class GamesController
{
    public function __construct(private readonly GamesManager $games)
    {
    }

    public function start(Request $request): Response
    {
        $session = $request->json()->get("session");
        $player = $request->json()->get("player");

        $game = $this->games->start(GameId::newId(), new SessionId(Uuid::fromString($session)), $player);

        return new JsonResponse($this->serialize($game));
    }

    /**
     * @return array<string, string>
     */
    private function serialize(Game $game): array
    {
        return [
            "id" => $game->id()->get(),
            "player" => $game->player()
        ];
    }

    public function list(): Response
    {
        $games = $this->games->list();
        $json = array_map(
            fn(Game $game): array => $this->serializeWithSession($game),
            $games
        );
        return new JsonResponse($json);
    }

    public function currentQuestion(string $gameId): Response
    {
        $game = $this->getGame($gameId);

        $currentQuestion = $game->currentQuestion();
        $answers = [$currentQuestion->goodAnswer(), ...$currentQuestion->badAnswers()];

        shuffle($answers);

        $json = [
            "question" => $currentQuestion->text(),
            "answers" => array_map(fn(Answer $a): array => ["answer" => $a->text], $answers),
        ];

        return new JsonResponse($json);
    }

    public function guess(Request $request, string $gameId): Response
    {
        $answer = $request->json()->get("answer");
        $game = $this->getGame($gameId);

        $guess = $game->guess(new Answer($answer));

        $json = ["shitcoins" => $game->shitCoins()->amount()];
        return match ($guess) {
            true => new JsonResponse($json),
            false => new JsonResponse($json, Response::HTTP_BAD_REQUEST)
        };
    }

    public function fiftyFifty(string $gameId): Response
    {
        $game = $this->getGame($gameId);

        $json = array_map(fn(Answer $a): string => $a->text, $game->fiftyFifty());
        return new JsonResponse(["badAnswers" => $json]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeWithSession(Game $game): array
    {
        return [
            "id" => $game->id()->get(),
            "player" => $game->player(),
            "step" => $game->step(),
            "status" => $game->status()->name,
            "session" => [
                SessionsController::serializeSession($game->session())
            ]
        ];
    }

    public function jokers(string $gameId): Response
    {
        $game = $this->getGame($gameId);
        $jokers = array_map(
            fn(Joker $joker): array => [
                "type" => $joker->type(),
                "status" => $joker->status()
            ],
            $game->jokers()->all()
        );

        return new JsonResponse([$jokers]);
    }

    private function getGame(string $gameId): Game
    {
        return $this->games->get(new GameId(Uuid::fromString($gameId)));
    }

}