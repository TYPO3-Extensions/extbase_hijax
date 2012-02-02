<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class Tx_ExtbaseHijax_ViewHelpers_IfViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractConditionViewHelper {
	/**
	 * An array of Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode
	 * @var array
	 */
	protected $childNodes = array();

	/**
	 * Setter for ChildNodes - as defined in ChildNodeAccessInterface
	 *
	 * @param array $childNodes Child nodes of this syntax tree node
	 * @return void
	 */
	public function setChildNodes(array $childNodes) {
		$this->childNodes = $childNodes;
	}
		
	/**
	 * renders <f:then> child if $condition is true, otherwise renders <f:else> child.
	 *
	 * @param string $condition View helper condition
	 * @return string the rendered string
	 */
	public function render($condition) {
		$thenChild = $this->renderThenChild();
		if ($thenChild) {
			$thenChild = '<div class="hijax-content">'.$thenChild.'</div>';
		}
		$elseChild = $this->renderElseChild();
		if ($elseChild) {
			$elseChild = '<div class="hijax-content-else">'.$elseChild.'</div>';
		}
		
		return '<div class="hijax-element hijax-js-conditional" data-hijax-element-type="conditional" data-hijax-condition="'.$this->arguments['condition'].'">'.$thenChild.$elseChild.'<div class="hijax-loading"></div></div>';
	}
	
	/**
	 * Returns value of "then" attribute.
	 * If then attribute is not set, iterates through child nodes and renders ThenViewHelper.
	 * If then attribute is not set and no ThenViewHelper and no ElseViewHelper is found, all child nodes are rendered
	 *
	 * @return string rendered ThenViewHelper or contents of <f:if> if no ThenViewHelper was found
	 */
	protected function renderThenChild() {
		if ($this->hasArgument('then')) {
			return $this->arguments['then'];
		}
		if ($this->hasArgument('__thenClosure')) {
			$thenClosure = $this->arguments['__thenClosure'];
			return $thenClosure();
		} elseif ($this->hasArgument('__elseClosure') || $this->hasArgument('else')) {
			return '';
		}
	
		$elseViewHelperEncountered = FALSE;
		foreach ($this->childNodes as $childNode) {
			if ($childNode instanceof Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode
					&& $childNode->getViewHelperClassName() === 'Tx_ExtbaseHijax_ViewHelpers_ThenViewHelper') {
				$data = $childNode->evaluate($this->renderingContext);
				return $data;
			}
			if ($childNode instanceof Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode
					&& $childNode->getViewHelperClassName() === 'Tx_ExtbaseHijax_ViewHelpers_ElseViewHelper') {
				$elseViewHelperEncountered = TRUE;
			}
		}
	
		if ($elseViewHelperEncountered) {
			return '';
		} else {
			return $this->renderChildren();
		}
	}
	
	/**
	 * Returns value of "else" attribute.
	 * If else attribute is not set, iterates through child nodes and renders ElseViewHelper.
	 * If else attribute is not set and no ElseViewHelper is found, an empty string will be returned.
	 *
	 * @return string rendered ElseViewHelper or an empty string if no ThenViewHelper was found
	 */
	protected function renderElseChild() {
		if ($this->hasArgument('else')) {
			return $this->arguments['else'];
		}
		if ($this->hasArgument('__elseClosure')) {
			$elseClosure = $this->arguments['__elseClosure'];
			return $elseClosure();
		}
		foreach ($this->childNodes as $childNode) {
			if ($childNode instanceof Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode
					&& $childNode->getViewHelperClassName() === 'Tx_ExtbaseHijax_ViewHelpers_ElseViewHelper') {
				return $childNode->evaluate($this->renderingContext);
			}
		}
	
		return '';
	}	
	
	/**
	 * The compiled ViewHelper adds two new ViewHelper arguments: __thenClosure and __elseClosure.
	 * These contain closures which are be executed to render the then(), respectively else() case.
	 *
	 * @param string $argumentsVariableName
	 * @param string $renderChildrenClosureVariableName
	 * @param string $initializationPhpCode
	 * @param Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $syntaxTreeNode
	 * @param Tx_Fluid_Core_Compiler_TemplateCompiler $templateCompiler
	 * @return string
	 * @internal
	 */
	public function compile($argumentsVariableName, $renderChildrenClosureVariableName, &$initializationPhpCode, Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode $syntaxTreeNode, Tx_Fluid_Core_Compiler_TemplateCompiler $templateCompiler) {
		foreach ($syntaxTreeNode->getChildNodes() as $childNode) {
			if ($childNode instanceof Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode
					&& $childNode->getViewHelperClassName() === 'Tx_ExtbaseHijax_ViewHelpers_ThenViewHelper') {
	
				$childNodesAsClosure = $templateCompiler->wrapChildNodesInClosure($childNode);
				$initializationPhpCode .= sprintf('%s[\'__thenClosure\'] = %s;', $argumentsVariableName, $childNodesAsClosure) . chr(10);
			}
			if ($childNode instanceof Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode
					&& $childNode->getViewHelperClassName() === 'Tx_ExtbaseHijax_ViewHelpers_ElseViewHelper') {
	
				$childNodesAsClosure = $templateCompiler->wrapChildNodesInClosure($childNode);
				$initializationPhpCode .= sprintf('%s[\'__elseClosure\'] = %s;', $argumentsVariableName, $childNodesAsClosure) . chr(10);
			}
		}
		return Tx_Fluid_Core_Compiler_TemplateCompiler::SHOULD_GENERATE_VIEWHELPER_INVOCATION;
	}	
}
?>
