<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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

class Tx_ExtbaseHijax_Event_Event implements ArrayAccess {
	
	protected
		$value = null,
		$processed = false,
		$name = '',
		$parameters = null;

	/**
	 * Constructs a new Tx_ExtbaseHijax_Event_Event.
	 *
	 * @param string								$name			The event name
	 * @param Tx_Extbase_MVC_RequestInterface		$request		The request
	 * @param array 								$configuration 	Framework configuraiton
	 * @param array	 								$parameters	 	An array of parameters
	 */
	public function __construct($name, $parameters = array()) {
		$this->name = $name;
		$this->parameters = $parameters;
	}

	/**
	 * Returns the event name.
	 *
	 * @return string The event name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the return value for this event.
	 *
	 * @param mixed $value The return value
	 */
	public function setReturnValue($value) {
		$this->value = $value;
	}

	/**
	 * Returns the return value.
	 *
	 * @return mixed The return value
	 */
	public function getReturnValue() {
		return $this->value;
	}

	/**
	 * Sets the processed flag.
	 *
	 * @param Boolean $processed The processed flag value
	 */
	public function setProcessed($processed) {
		$this->processed = (boolean) $processed;
	}

	/**
	 * Returns whether the event has been processed by a listener or not.
	 *
	 * @return Boolean true if the event has been processed, false otherwise
	 */
	public function isProcessed() {
		return $this->processed;
	}

	/**
	 * Returns the event parameters.
	 *
	 * @return array The event parameters
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * Returns true if the parameter exists (implements the ArrayAccess interface).
	 *
	 * @param	string	$name	The parameter name
	 *
	 * @return Boolean true if the parameter exists, false otherwise
	 */
	public function offsetExists($name) {
		return array_key_exists($name, $this->parameters);
	}

	/**
	 * Returns a parameter value (implements the ArrayAccess interface).
	 *
	 * @param	string	$name	The parameter name
	 *
	 * @return mixed	The parameter value
	 */
	public function offsetGet($name) {
		if (!array_key_exists($name, $this->parameters)) {
			throw new InvalidArgumentException(sprintf('The event "%s" has no "%s" parameter.', $this->name, $name));
		}

		return $this->parameters[$name];
	}

	/**
	 * Sets a parameter (implements the ArrayAccess interface).
	 *
	 * @param string	$name	 The parameter name
	 * @param mixed	 $value	The parameter value 
	 */
	public function offsetSet($name, $value) {
		$this->parameters[$name] = $value;
	}

	/**
	 * Removes a parameter (implements the ArrayAccess interface).
	 *
	 * @param string $name		The parameter name
	 */
	public function offsetUnset($name) {
		unset($this->parameters[$name]);
	}
}