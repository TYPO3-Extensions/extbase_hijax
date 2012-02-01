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

class Tx_ExtbaseHijax_Event_Dispatcher implements t3lib_Singleton {
	/**
	 * @var array
	 */
	protected $listeners = array();
	
	/**
	 * @var array
	 */
	protected $currentElementListenersStack = array();
	
	/**
	 * @var array
	 */
	protected $currentElementListeners = array();

	/**
	 * @var array
	 */
	protected $pendingEvents = array();
	
	/**
	 * @var array
	 */
	protected $nextPhasePendingEvents = array();
	
	/**
	 * @var array
	 */
	protected $pendingEventNames = array();
	
	/**
	 * @var array
	 */
	protected $nextPhasePendingEventNames = array();
	
	/**
	 * Connects a listener to a given event name.
	 *
	 * @param string 							$name An event name
	 * @param mixed								$callback Callback function
	 * @param Tx_ExtbaseHijax_Event_Listener	$listener TYPO3 Extbase listener
	 */
	public function connect($name, $callback = null, $listener = null) {
		if (!$listener) {
			$listener = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager')->get('Tx_ExtbaseHijax_MVC_Dispatcher')->getCurrentListener();
		}
		
		if (!isset($this->listeners[$name])) {
			$this->listeners[$name] = array();
		}
		
		if ($callback && in_array($name, $this->pendingEventNames)) {
			foreach ($this->pendingEvents[$name] as $event) {
				/* @var $event Tx_ExtbaseHijax_Event_Event */
				if (is_string($callback)) {
					t3lib_div::callUserFunction($callback, $event, $this, $checkPrefix = false);
				} else {
					call_user_func($callback, $event);
				}
			}
		}
		
		$this->listeners[$name][] = array('listener' => $listener, 'callback' => $callback);

		if (!isset($this->currentElementListeners[$name])) {
			$this->currentElementListeners[$name] = array();
		}
		
		$this->currentElementListeners[$name][] = array('listener' => $listener, 'callback' => $callback);
	}

	/**
	 * Disconnects a listener for a given event name.
	 *
	 * @param string							$name			An event name
	 * @param mixed								$callback	A PHP callable
	 * @param Tx_ExtbaseHijax_Event_Listener	$listener TYPO3 Extbase listener
	 *
	 * @return mixed false if listener does not exist, null otherwise
	 */
	public function disconnect($name, $callback = null, $listener = null) {
		if (!isset($this->listeners[$name])) {
			return false;
		}
		
		if (!$listener) {
			$listener = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager')->get('Tx_ExtbaseHijax_MVC_Dispatcher')->getCurrentListener();
		}
		
		foreach ($this->listeners[$name] as $i => $callable) {
			if ($listener->getId() === $callable['listener']->getId() && $callback === $callable['callback']) {
				unset($this->listeners[$name][$i]);
				if (count($this->listeners[$name])==0) {
					unset($this->listeners[$name]);
				}
			}
		}
		
		foreach ($this->currentElementListeners[$name] as $i => $callable) {
			if ($listener->getId() === $callable['listener']->getId() && $callback === $callable['callback']) {
				unset($this->currentElementListeners[$name][$i]);
				if (count($this->currentElementListeners[$name])==0) {
					unset($this->currentElementListeners[$name]);
				}
			}
		}		
	}

	/**
	 * Notifies all listeners of a given event.
	 *
	 * @param Tx_ExtbaseHijax_Event_Event $event A Tx_ExtbaseHijax_Event_Event instance
	 *
	 * @return Tx_ExtbaseHijax_Event_Event The Tx_ExtbaseHijax_Event_Event instance
	 */
	public function notify(Tx_ExtbaseHijax_Event_Event $event) {
		if (!isset($this->nextPhasePendingEvents[$event->getName()])) {
			$this->nextPhasePendingEvents[$event->getName()] = array();
		}
		$this->nextPhasePendingEvents[$event->getName()][] = $event;
		
		if (!in_array($event->getName(), $this->nextPhasePendingEventNames)) {
			$this->nextPhasePendingEventNames[] = $event->getName();
		}
		
		return $event;
	}
	
	/**
	 * @return void
	 */
	public function promoteNextPhaseEvents() {
		$this->pendingEvents = $this->nextPhasePendingEvents;
		$this->pendingEventNames = $this->nextPhasePendingEventNames;
		
		$this->nextPhasePendingEvents = array();
		$this->nextPhasePendingEventNames = array();
	}
	
	/**
	 * @return boolean
	 */
	public function hasPendingNextPhaseEvents() {
		return (boolean) count($this->nextPhasePendingEventNames);
	}
		
	/**
	 * @return boolean
	 */
	public function hasPendingEvents() {
		return (boolean) count($this->pendingEventNames);
	}
	
	/**
	 * @return boolean
	 */
	public function hasPendingEventWithName($name) {
		return in_array($name, $this->pendingEventNames);
	}
	
	/**
	 * Notifies all listeners of a given event until one returns a non null value.
	 *
	 * @param	Tx_ExtbaseHijax_Event_Event $event A Tx_ExtbaseHijax_Event_Event instance
	 *
	 * @return Tx_ExtbaseHijax_Event_Event The Tx_ExtbaseHijax_Event_Event instance
	 */
	public function notifyUntil(Tx_ExtbaseHijax_Event_Event $event) {
		/*
		foreach ($this->getListeners($event->getName()) as $listener) {
			if (is_string($listener)) {
				$retVal = t3lib_div::callUserFunction($listener, $event, $this, $checkPrefix = false);
			} else {
				$retVal = call_user_func($listener, $event);
			}
			
			if ($retVal) {
				$event->setProcessed(true);
				break;
			}
		}
		*/
		return $event;
	}

	/**
	 * Filters a value by calling all listeners of a given event.
	 *
	 * @param	Tx_ExtbaseHijax_Event_Event	$event	 A Tx_ExtbaseHijax_Event_Event instance
	 * @param	mixed		$value	 The value to be filtered
	 *
	 * @return Tx_ExtbaseHijax_Event_Event The Tx_ExtbaseHijax_Event_Event instance
	 */
	public function filter(Tx_ExtbaseHijax_Event_Event $event, $value) {
		/*
		foreach ($this->getListeners($event->getName()) as $listener) {
			if (is_string($listener)) {
				$value = t3lib_div::callUserFunction($listener, array($event, $value), $this, $checkPrefix = false);
			} else {
				$value = call_user_func_array($listener, array($event, $value));
			}
		}

		$event->setReturnValue($value);
		*/
		return $event;
	}

	/**
	 * Returns true if the given event name has some listeners.
	 *
	 * @param	string	 	$name		The event name
	 * @param	boolean 	$current	Determines if the lookup should be done only on current element listeners
	 *
	 * @return Boolean true if some listeners are connected, false otherwise
	 */
	public function hasListeners($name = '', $current = false) {
		if ($current) {
			$listeners = &$this->currentElementListeners;
		} else {
			$listeners = &$this->listeners;
		}
		
		if ($name) {
			if (!isset($listeners[$name])) {
				$listeners[$name] = array();
			}
	
			return (boolean) count($listeners[$name]);
		} else {
			return (boolean) count($listeners);
		}
	}
	
	/**
	 * Returns all listeners associated with a given event name.
	 *
	 * @param	string	 $name		The event name
	 *
	 * @return array	An array of listeners
	 */
	public function getListeners($name = '', $current = false) {
		if ($current) {
			$listeners = &$this->currentElementListeners;
		} else {
			$listeners = &$this->listeners;
		}
		
		if ($name) {
			if (!isset($listeners[$name])) {
				return array();
			}
	
			return $listeners[$name];
		} else {
			return $listeners;
		}
	}
	
	/**
	 * Denotes start of content element rendering execution
	 * 
	 * @return void
	 */
	public function startContentElement() {
		array_push($this->currentElementListenersStack, $this->currentElementListeners);
		$this->currentElementListeners = array();
	}
	
	/**
	 * Denotes end of content element rendering execution
	 * 
	 * @return void
	 */
	public function endContentElement() {
		$this->currentElementListeners = array_pop($this->currentElementListenersStack);
	}
}