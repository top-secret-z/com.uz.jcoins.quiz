<?php
namespace wcf\system\event\listener;
use wcf\data\quiz\Quiz;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\NamedUserException;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;
use wcf\system\WCF;

/**
 * JCoins solve / get quiz listener.
 *
 * @author		2018-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.jcoins.quiz
 */
class JCoinsQuizEventListener implements IParameterizedEventListener {
	/**
	 * @inheritdoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		if (!MODULE_JCOINS) return;
		
		if (!WCF::getUser()->userID) return;
		
		switch ($eventObj->getActionName()) {
			case 'getQuiz':
				if (JCOINS_ALLOW_NEGATIVE) return;
				
				if (!WCF::getSession()->getPermission('user.jcoins.canEarn') || !WCF::getSession()->getPermission('user.jcoins.canUse')) return;
				
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
				}
				else {
					// percent
					$statement = UserJCoinsStatementHandler::getInstance()->getStatementProcessorInstance('com.uz.jcoins.statement.quiz.percent');
					$percent = $statement->calculateAmount();
					
					if ($count / $total * 100 > $percent ) {
						UserJCoinsStatementHandler::getInstance()->create('com.uz.jcoins.statement.quiz.result', $quiz, ['userID' => WCF::getUser()->userID]);
					}
				}
				break;
		}
	}
}
