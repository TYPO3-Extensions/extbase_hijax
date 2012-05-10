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
	 * @var array
	 */
	protected $skipPendingEvents = array();

	/**
	 * @var array
	 */
	protected $nextPhaseSkipPendingEvents = array();

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;
	
	/**
	 * @var Tx_ExtbaseHijax_Service_Serialization_ListenerFactory
	 */
	protected $listenerFactory;	
	
	/**
	 * @var boolean
	 */
	protected $isHijaxElement;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->listenerFactory = $this->objectManager->get('Tx_ExtbaseHijax_Service_Serialization_ListenerFactory');
	}
	
	/**
	 * Connects a listener to a given event name.
	 *
	 * @param string 							$name An event name
	 * @param mixed								$callback Callback function
	 * @param Tx_ExtbaseHijax_Event_Listener	$listener TYPO3 Extbase listener
	 */
	public function connect($name, $callback = null, $listener = null) {
		$this->setIsHijaxElement(true);
		if (!$listener) {
			/* @var $listener Tx_ExtbaseHijax_Event_Listener */
			$listener = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager')->get('Tx_ExtbaseHijax_MVC_Dispatcher')->getCurrentListener();
		}
		
		if (!isset($this->listeners[$name])) {
			$this->listeners[$name] = array();
		}
		
		$events = array();
		
		if (in_array($name, $this->pendingEventNames)) {
			foreach ($this->pendingEvents[$name] as $event) {
				/* @var $event Tx_ExtbaseHijax_Event_Event */
				$events[] = $event;
				if ($callback) {
					if (is_string($callback)) {
						t3lib_div::callUserFunction($callback, $event, $this, $checkPrefix = false);
					} else {
						call_user_func($callback, $event);
					}
				}
			}
		}
		
		$this->listeners[$name][] = array('listener' => $listener, 'callback' => $callback);

		if (!isset($this->currentElementListeners[$name])) {
			$this->currentElementListeners[$name] = array();
		}
		
		$this->currentElementListeners[$name][] = array('listener' => $listener, 'callback' => $callback);
		
		return $events;
	}

	/**
	 * @param boolean $isHijaxElement
	 * @return void
	 */
	public function setIsHijaxElement($isHijaxElement) {
		$this->isHijaxElement = $isHijaxElement;
	}
	
	/**
	 * @return boolean
	 */
	public function getIsHijaxElement() {
		return $this->isHijaxElement;
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
	 * @param boolean $skipNotifier Skips notifier when processing the event (prevents dead loops)
	 * @param Tx_ExtbaseHijax_Event_Listener	$listener TYPO3 Extbase listener
	 *
	 * @return Tx_ExtbaseHijax_Event_Event The Tx_ExtbaseHijax_Event_Event instance
	 */
	public function notify(Tx_ExtbaseHijax_Event_Event $event, $skipNotifier = false, $listener = null) {
		if (!isset($this->nextPhasePendingEvents[$event->getName()])) {
			$this->nextPhasePendingEvents[$event->getName()] = array();
		}
		$this->nextPhasePendingEvents[$event->getName()][] = $event;
		
		if ($skipNotifier) {
			if (!$listener) {
				/* @var $listener Tx_ExtbaseHijax_Event_Listener */
				$listener = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager')->get('Tx_ExtbaseHijax_MVC_Dispatcher')->getCurrentListener();
			}
			
			if (!in_array($event->getName(), $this->nextPhaseSkipPendingEvents)) {
				$this->nextPhaseSkipPendingEvents[] = $event->getName().';'.$listener->getId();
			}
		}
		
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
		$this->skipPendingEvents = $this->nextPhaseSkipPendingEvents;
		
		$this->nextPhasePendingEvents = array();
		$this->nextPhasePendingEventNames = array();
		$this->nextPhaseSkipPendingEvents = array();
		$this->isHijaxElement = false;
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
	public function hasPendingEventWithName($eventName, $listenerId) {
		return in_array($eventName, $this->pendingEventNames) && !in_array($eventName.';'.$listenerId, $this->skipPendingEvents);
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
		$this->resetContextArguments();
		$this->setIsHijaxElement(false);
	}
	
	/**
	 * Denotes end of content element rendering execution
	 * 
	 * @return void
	 */
	public function endContentElement() {
		$this->currentElementListeners = array_pop($this->currentElementListenersStack);
	}
	
	/**
	 * @param tslib_fe $pObj
	 * @return void
	 */
	public function parseAndRunEventListeners(&$content) {
		$tempContent = preg_replace_callback('/<!-- ###EVENT_LISTENER_(?P<elementId>\d*)### START (?P<listenerDefinition>.*) -->(?P<content>.*?)<!-- ###EVENT_LISTENER_(\\1)### END -->/msU', array($this, 'parseAndRunEventListenersCallback'), $content);
		$content = $tempContent;			
	}
	
	/**
	 * @param array $match
	 * @return string
	 */
	protected function parseAndRunEventListenersCallback($match) {
		$matchesListenerDef = array();
		preg_match('/(?P<listenerId>[a-z0-9_-]*)\((?P<eventNames>.*)\);/msU', $match['listenerDefinition'], $matchesListenerDef);
			
		$elementId = $match['elementId'];
		$listenerId = $matchesListenerDef['listenerId'];
		$eventNames = $this->convertCSVToArray($matchesListenerDef['eventNames']);
			
		$shouldProcess = FALSE;
			
		foreach ($eventNames as $eventName) {
			if ($this->hasPendingEventWithName($eventName, $listenerId)) {
				$shouldProcess = TRUE;
				break;
			}
		}
			
		if ($shouldProcess) {
			/* @var $listener Tx_ExtbaseHijax_Event_Listener */
			$listener = $this->listenerFactory->findById($listenerId);
			
			if ($listener) {
				/* @var $bootstrap Tx_Extbase_Core_Bootstrap */
				$bootstrap = t3lib_div::makeInstance('Tx_Extbase_Core_Bootstrap');
				$bootstrap->cObj = $listener->getCObj();
				$bootstrap->initialize($listener->getConfiguration());
				$request = $listener->getRequest();
		
				/* @var $response Tx_Extbase_MVC_Web_Response */
				$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');
		
				$dispatcher = $this->objectManager->get('Tx_Extbase_MVC_Dispatcher');
				$dispatcher->dispatch($request, $response);
					
				$result = $response->getContent();
			} else {
				// TODO: log error message
				$result = $match[0];
			}
		} else {
			$result = $match[0];
		}
	
		return $result;
	}
	
	/**
	 * @param tslib_fe $pObj
	 * @return void
	 */
	public function replaceXMLCommentsWithDivs(&$content) {
		$this->replaceXMLCommentsWithDivsFound = TRUE;
		while ($this->replaceXMLCommentsWithDivsFound) {
			$this->replaceXMLCommentsWithDivsFound = FALSE;
			$content = preg_replace_callback('/<!-- ###EVENT_LISTENER_(?P<elementId>\d*)### START (?P<listenerDefinition>.*) -->(?P<content>.*?)<!-- ###EVENT_LISTENER_(\\1)### END -->/msU', array($this, 'replaceXMLCommentsWithDivsCallback'), $content);
		}
	}
	
	/**
	 * @var array
	 */
	protected $contextArguments = array();
	
	/**
	 * @param array $contextArguments
	 * 
	 * @return void
	 */
	public function registerContextArguments($contextArguments) {
		$this->contextArguments = array_merge($this->contextArguments, $contextArguments);
	}
	
	/**
	 * @return array
	 */
	public function getContextArguments() {
		return $this->contextArguments;
	}
	
	/**
	 * @return void
	 */
	protected function resetContextArguments() {
		$this->contextArguments = array();
	}
	
	/**
	 * @param array $match
	 * @return string
	 */
	protected function replaceXMLCommentsWithDivsCallback($match) {
		$this->replaceXMLCommentsWithDivsFound = TRUE;
		$matchesListenerDef = array();
		preg_match('/(?P<listenerId>[a-z0-9_-]*)\((?P<eventNames>.*)\);/msU', $match['listenerDefinition'], $matchesListenerDef);
			
		$elementId = $match['elementId'];
		$listenerId = $matchesListenerDef['listenerId'];
		
		return '<div class="hijax-element hijax-js-listener" data-hijax-result-target="this" data-hijax-result-wrap="false" data-hijax-element-type="listener" data-hijax-element-id="'.$elementId.'" data-hijax-listener-id="'.$listenerId.'" data-hijax-listener-events="'.htmlspecialchars($matchesListenerDef['eventNames']).'"><div class="hijax-content">'.$match['content'].'</div><div class="hijax-loading"></div></div>';
	}
	
	/**
	 * @param string $data
	 * @param string $delimiter
	 * @param string $enclosure
	 * @return array
	 */
	protected function convertCSVToArray($data, $delimiter = ',', $enclosure = '"') {
		$instream = fopen("php://temp", 'r+');
		fwrite($instream, $data);
		rewind($instream);
		$csv = fgetcsv($instream, 9999999, $delimiter, $enclosure);
		fclose($instream);
		return $csv;
	}	
}