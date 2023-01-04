<?php

namespace Session\Domain\Question;

use PHPUnit\Framework\TestCase;
use QVGDS\Session\Domain\Question\Answer;
use QVGDS\Session\Domain\Question\BadAnswers;
use QVGDS\Session\Domain\Question\GoodAnswerIsAlsoInBadAnswersException;
use QVGDS\Session\Domain\Question\Question;
use QVGDS\Tests\Session\SessionFixtures;
use QVGDS\Utils\MissingMandatoryValueException;

class QuestionTest extends TestCase
{
    /**
     * @test
     */
    public function shouldNoBuildWithEmptyText()
    {
        self::expectException(MissingMandatoryValueException::class);
        self::expectExceptionMessage("text");

        new Question(SessionFixtures::questionId(), 1, "", SessionFixtures::goodAnswer(), SessionFixtures::badAnswers());
    }

    /**
     * @test
     */
    public function shouldHaveGoodAnswerDifferentOfBadAnswers(): void
    {
        $this->expectException(GoodAnswerIsAlsoInBadAnswersException::class);

        new Question(SessionFixtures::questionId(), 1, "toto", SessionFixtures::goodAnswer(), new BadAnswers(new Answer("Good answer"), new Answer("bad"), new Answer("bad")));
    }

    /**
     * @test
     */
    public function shouldAskForFiftyFifty(): void
    {
        $question = new Question(SessionFixtures::questionId(), 1, "toto", SessionFixtures::goodAnswer(), SessionFixtures::badAnswers());

        $answers = $question->fiftyFifty();

        self::assertContainsOnlyInstancesOf(Answer::class, $answers);
        self::assertCount(2, $answers);
        self::assertNotEquals($answers[0], $answers[1]);
    }

    /**
     * @test
     */
    public function shouldVerifyGoodAnswer(): void
    {
        $question = new Question(SessionFixtures::questionId(), 1, "toto", SessionFixtures::goodAnswer(), SessionFixtures::badAnswers());

        self::assertTrue($question->guess(new Answer("Good answer")));
    }

    /**
     * @test
     */
    public function shouldVerifyBadAnswer(): void
    {
        $question = new Question(SessionFixtures::questionId(), 1, "toto", SessionFixtures::goodAnswer(), SessionFixtures::badAnswers());

        self::assertFalse($question->guess(new Answer("Bad answer")));
    }
}
