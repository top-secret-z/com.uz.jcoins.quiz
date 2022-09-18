<?php

/*
 * Copyright by Udo Zaydowicz.
 * Modified by SoftCreatR.dev.
 *
 * License: http://opensource.org/licenses/lgpl-license.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
namespace wcf\system\event\listener;

use wcf\data\quiz\Quiz;
use wcf\system\exception\NamedUserException;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;
use wcf\system\WCF;

/**
 * JCoins solve / get quiz listener.
 */
class JCoinsQuizEventListener implements IParameterizedEventListener
{
    /**
     * @inheritdoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if (!MODULE_JCOINS) {
            return;
        }

        if (!WCF::getUser()->userID) {
            return;
        }

        switch ($eventObj->getActionName()) {
            case 'getQuiz':
                if (JCOINS_ALLOW_NEGATIVE) {
                    return;
                }

                if (!WCF::getSession()->getPermission('user.jcoins.canEarn') || !WCF::getSession()->getPermission('user.jcoins.canUse')) {
                    return;
                }

                $statement = UserJCoinsStatementHandler::getInstance()->getStatementProcessorInstance('com.uz.jcoins.statement.quiz.result');
                if ($statement->calculateAmount() < 0 && ($statement->calculateAmount() * -1) > WCF::getUser()->jCoinsAmount) {
                    throw new NamedUserException(WCF::getLanguage()->getDynamicVariable('wcf.jcoins.amount.tooLow'));
                }
                break;

            case 'saveResult':
                $statement = UserJCoinsStatementHandler::getInstance()->getStatementProcessorInstance('com.uz.jcoins.statement.quiz.result');
                $jCoins = $statement->calculateAmount();

                $params = $eventObj->getParameters();
                $count = $params['correctAnswers'];
                $quiz = new Quiz($params['quizID']);
                $total = $quiz->getQuestionCount();

                // if JCoins substracted for quiz
                if ($jCoins < 0) {
                    UserJCoinsStatementHandler::getInstance()->create('com.uz.jcoins.statement.quiz.result', $quiz, ['userID' => WCF::getUser()->userID]);
                } else {
                    // percent
                    $statement = UserJCoinsStatementHandler::getInstance()->getStatementProcessorInstance('com.uz.jcoins.statement.quiz.percent');
                    $percent = $statement->calculateAmount();

                    if ($count / $total * 100 > $percent) {
                        UserJCoinsStatementHandler::getInstance()->create('com.uz.jcoins.statement.quiz.result', $quiz, ['userID' => WCF::getUser()->userID]);
                    }
                }
                break;
        }
    }
}
