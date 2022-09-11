<?php
namespace wcf\system\event\listener;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;
use wcf\system\WCF;

/**
 * JCoins create/delete question listener.
 *
 * @author		2018-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.jcoins.quiz
 */
class JCoinsQuizQuestionEventListener implements IParameterizedEventListener {
	/**
	 * @inheritdoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		if (!MODULE_JCOINS) return;
		
		switch ($eventObj->getActionName()) {
			case 'create':
				$returnValues = $eventObj->getReturnValues();
				$question = $returnValues['returnValues'];
				
				// not for acp
				if ($question->isACP) return;
				
				UserJCoinsStatementHandler::getInstance()->create('com.uz.jcoins.statement.quiz.question', $question, ['userID' => $question->userID]);
				break;
				
			case 'delete':
				foreach ($eventObj->getObjects() as $object) {
					$question = $object->getDecoratedObject();
					
					// not for acp
					if ($question->isACP) continue;
					
					UserJCoinsStatementHandler::getInstance()->revoke('com.uz.jcoins.statement.quiz.question', $question, ['userID' => $question->userID]);
				}
				break;
		}
	}
}
