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

class Tx_ExtbaseHijax_Tslib_FE_Hook implements t3lib_Singleton {
	
	protected static $loopCount = 0;
	
	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;
		
	/**
	 * Extension Configuration
	 *
	 * @var Tx_ExtbaseHijax_Configuration_ExtensionInterface
	 */
	protected $extensionConfiguration;
	
	/**
	 * @var Tx_ExtbaseHijax_Event_Dispatcher
	 */
	protected $hijaxEventDispatcher;
	
	/**
	 * @var Tx_ExtbaseHijax_Service_Serialization_ListenerFactory
	 */
	protected $listenerFactory;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->initializeObjectManager();
	}
	
	/**
	 * Initializes the Object framework.
	 *
	 * @return void
	 */
	protected function initializeObjectManager() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
		$this->extensionConfiguration = $this->objectManager->get('Tx_ExtbaseHijax_Configuration_ExtensionInterface');
		$this->hijaxEventDispatcher = $this->objectManager->get('Tx_ExtbaseHijax_Event_Dispatcher');
		$this->listenerFactory = $this->objectManager->get('Tx_ExtbaseHijax_Service_Serialization_ListenerFactory');
	}

	/**
	 * @param array $params
	 * @param tslib_fe $pObj
	 */
	public function contentPostProcAll($params, $pObj) {
		$this->contentPostProc($params, $pObj, 'all');
	}

	/**
	 * @param array $params
	 * @param tslib_fe $pObj
	 */
	public function contentPostProcOutput($params, $pObj) {
		$this->contentPostProc($params, $pObj, 'output');
	}
	
	/**
	 * @param array $params
	 * @param tslib_fe $pObj
	 * @param string $hookType
	 */
	protected function contentPostProc($params, $pObj, $hookType) {		
		if ($this->extensionConfiguration->shouldIncludeEofe() && !$this->extensionConfiguration->hasIncludedEofe()) {
			$this->extensionConfiguration->setIncludedEofe(true);
			
			$eofe = $pObj->cObj->cObjGetSingle(
				$pObj->tmpl->setup['config.']['extbase_hijax.']['eofe'], 
				$pObj->tmpl->setup['config.']['extbase_hijax.']['eofe.']
			);
			
			$pObj->content = str_ireplace('</body>',  $eofe . '</body>', $pObj->content);
		}
		
		if ($this->extensionConfiguration->shouldIncludeSofe() && !$this->extensionConfiguration->hasIncludedSofe()) {
			$this->extensionConfiguration->setIncludedSofe(true);

			$sofe = $pObj->cObj->cObjGetSingle(
				$pObj->tmpl->setup['config.']['extbase_hijax.']['sofe'], 
				$pObj->tmpl->setup['config.']['extbase_hijax.']['sofe.']
			);
			
			$pObj->content = preg_replace('/<body([^>]*)>/msU',  '<body$1>'.$sofe, $pObj->content);
		}
		
		$bodyClass = $pObj->tmpl->setup['config.']['extbase_hijax.']['bodyClass'];
		if ($bodyClass && !$this->extensionConfiguration->hasAddedBodyClass()) {
			
			$matches = array();
			preg_match('/<body([^>]*)class="([^>]*)">/msU', $pObj->content, $matches);
			$count = 0;
			if (count($matches)) {
				$classes = t3lib_div::trimExplode(" ", $matches[2], true);
				if (!in_array($bodyClass, $classes)) {
					$pObj->content = preg_replace('/<body([^>]*)class="([^>]*)">/msU',  '<body$1class="$2 '.$bodyClass.'">', $pObj->content, -1, $count);
				}
			} else {
				$pObj->content = preg_replace('/<body([^>]*)>/msU',  '<body$1 class="'.$bodyClass.'">', $pObj->content, -1, $count);
			}
			if ($count) {
				$this->extensionConfiguration->setAddedBodyClass(true);
			}
		}
		
		while ($this->hijaxEventDispatcher->hasPendingNextPhaseEvents()) {
			$this->hijaxEventDispatcher->promoteNextPhaseEvents();
			$this->parseAndRunEventListeners($pObj);
			
			if (self::$loopCount++>99) {
					// preventing dead loops
				break;
			}
		}
		
		if ($hookType=='output') {
			$this->replaceXMLCommentsWithDivs($pObj);
		}
	}
	
	/**
	 * @param tslib_fe $pObj
	 * @return void
	 */
	protected function parseAndRunEventListeners($pObj) {
		$tempContent = preg_replace_callback('/<!-- ###EVENT_LISTENER_(?P<elementId>\d*)### START (?P<listenerDefinition>.*) -->(?P<content>.*?)<!-- ###EVENT_LISTENER_(\\1)### END -->/msU', array($this, 'parseAndRunEventListenersCallback'), $pObj->content);
		$pObj->content = $tempContent;
	}
	
	/**
	 * @param array $match
	 * @return string
	 */
	protected function parseAndRunEventListenersCallback($match) {
		$matchesListenerDef = array();
		preg_match('/(?P<listenerId>[a-z0-9_]*)\((?P<eventNames>.*)\);/msU', $match['listenerDefinition'], $matchesListenerDef);
			
		$elementId = $match['elementId'];
		$listenerId = $matchesListenerDef['listenerId'];
		$eventNames = $this->convertCSVToArray($matchesListenerDef['eventNames']);
			
		$shouldProcess = FALSE;
			
		foreach ($eventNames as $eventName) {
			if ($this->hijaxEventDispatcher->hasPendingEventWithName($eventName)) {
				$shouldProcess = TRUE;
				break;
			}
		}
			
		if ($shouldProcess) {
			/* @var $listener Tx_ExtbaseHijax_Event_Listener */
			$listener = $this->listenerFactory->findById($listenerId);
	
			$bootstrap = t3lib_div::makeInstance('Tx_Extbase_Core_Bootstrap');
			$bootstrap->initialize($listener->getConfiguration());
			$request = $listener->getRequest();
				
			/* @var $response Tx_Extbase_MVC_Web_Response */
			$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');
	
			$dispatcher = $this->objectManager->get('Tx_Extbase_MVC_Dispatcher');
			$dispatcher->dispatch($request, $response);
			
			$result = $response->getContent();
		} else {
			$result = $match[0];
		}

		return $result;
	}	
	
	/**
	 * @param tslib_fe $pObj
	 * @return void
	 */
	protected function replaceXMLCommentsWithDivs($pObj) {
		$pObj->content = preg_replace_callback('/<!-- ###EVENT_LISTENER_(?P<elementId>\d*)### START (?P<listenerDefinition>.*) -->(?P<content>.*?)<!-- ###EVENT_LISTENER_(\\1)### END -->/msU', array($this, 'replaceXMLCommentsWithDivsCallback'), $pObj->content);
	}

	/**
	 * @param array $match
	 * @return string
	 */
	protected function replaceXMLCommentsWithDivsCallback($match) {
		$matchesListenerDef = array();
		preg_match('/(?P<listenerId>[a-z0-9_]*)\((?P<eventNames>.*)\);/msU', $match['listenerDefinition'], $matchesListenerDef);
			
		$elementId = $match['elementId'];
		$listenerId = $matchesListenerDef['listenerId'];
		
		return '<div class="hijax-element hijax-js-listener" data-hijax-element-id="'.$elementId.'" data-hijax-listener-id="'.$listenerId.'" data-hijax-listener-events="'.htmlspecialchars($matchesListenerDef['eventNames']).'">'.$match['content'].'</div>';
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

?>