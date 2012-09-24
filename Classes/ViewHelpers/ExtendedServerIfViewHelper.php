<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class Tx_ExtbaseHijax_ViewHelpers_ExtendedServerIfViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractConditionViewHelper {

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;
	
	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}	
	
	/**
	 * Initialize all arguments. You need to override this method and call
	 * $this->registerArgument(...) inside this method, to register all your arguments.
	 *
	 * @return void
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('condition', 'mixed', 'View helper condition expression, evaled', TRUE);
	}

	/**
	 * renders <f:then> child if $condition is true, otherwise renders <f:else> child.
	 *
	 * @return string the rendered string
	 * @api
	 */
	public function render() {
		$condition = $this->arguments['condition'];
		if (is_null($condition)) {
			return $this->renderElseChild();
		} elseif ($condition === TRUE) {
			return $this->renderThenChild();
		} elseif ($condition === FALSE) {
			return $this->renderElseChild();
		} elseif (is_array($condition)) {
			return (count($condition) > 0);
		} elseif ($condition instanceof Countable) {
			return (count($condition) > 0);
		} elseif (is_string($condition) && trim($condition) === '') {
			if (trim($condition) === '') {
				return $this->renderElseChild();
			} else if (preg_match('/[a-z^]/', $condition)) {
				$condition = '\'' . $condition . '\'';
			}
		} elseif (is_object($condition)) {
			if ($condition instanceof Iterator && method_exists($condition, 'count')) {
				return (call_user_method('count', $condition) > 0);
			} else if ($condition instanceof DateTime) {
				return $this->renderThenChild();
			} else if ($condition instanceof stdClass) {
				return $this->renderThenChild();
			} else {
				$access = t3lib_div::makeInstance('Tx_Extbase_Reflection_ObjectAccess');
				$propertiesCount = count($access->getGettableProperties($condition));
				if ($propertiesCount > 0) {
					return $this->renderThenChild();
				} else {
					throw new Exception('Unknown object type in IfViewHelper condition: ' . get_class($condition), 1309493049);
				}
			}
		}
		$leftParenthesisCount = substr_count($condition, '(');
		$rightParenthesisCount = substr_count($condition, ')');
		$singleQuoteCount = substr_count($condition, '\'');
		$escapedSingleQuoteCount = substr_count($condition, '\\\'');
		if ($rightParenthesisCount !== $leftParenthesisCount) {
			throw new Exception('Syntax error in IfViewHelper condition, mismatched number of opening and closing paranthesis', 1309490125);
		}
		if (($singleQuoteCount-$escapedSingleQuoteCount) % 2 != 0) {
			throw new Exception('Syntax error in IfViewHelper condition, mismatched number of unescaped single quotes', 1309490125);
		}
		$configuration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK);
		$allowedFunctions = explode(',', $configuration['fluid']['allowedFunctions']);
		$languageConstructs = explode(',', $configuration['fluid']['disallowedConstructs']);
		$functions = get_defined_functions();
		$functions = array_merge($languageConstructs, $functions['internal'], $functions['user']);
		$functions = array_diff($functions, $allowedFunctions);
		$conditionLength = strlen($condition);
		$conditionHasUnderscore = strpos($condition, '_');
		foreach ($functions as $evilFunction) {
			if (strlen($evilFunction) > $conditionLength) {
					// no need to check for presence of this function - quick skip
				continue;
			}
			if (preg_match('/' . $evilFunction . '([\s]){0,}\(/', $condition) === 1) {
				throw new Exception('Disallowed PHP function "' . $evilFunction . '" used in IfViewHelper condition. Allowed functions: ' . $goodFunctions, 1309613359);
			}
		}

		$evaluation = NULL;
		$evaluationCondition = $condition;
		$evaluationCondition = trim($condition, ';');
		$evaluationExpression = '$evaluation = (bool) (' . $evaluationCondition . ');';
		@eval($evaluationExpression);
		if ($evaluation === NULL) {
			throw new Exception('Syntax error while analyzing computed IfViewHelper expression: ' . $evaluationExpression, 1309537403);
			return $this->renderElseChild();
		} else if ($evaluation === TRUE) {
			return $this->renderThenChild();
		} else {
			return $this->renderElseChild();
		}
	}
}
?>
