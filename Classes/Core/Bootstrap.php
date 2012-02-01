<?php

class ux_Tx_Extbase_Core_Bootstrap extends Tx_Extbase_Core_Bootstrap {
	/**
	 * Runs the the Extbase Framework by resolving an appropriate Request Handler and passing control to it.
	 * If the Framework is not initialized yet, it will be initialized.
	 *
	 * @param string $content The content. Not used
	 * @param array $configuration The TS configuration array
	 * @return string $content The processed content
	 * @api
	 */
	public function run($content, $configuration) {
		$content = parent::run($content, $configuration);
		return $content;
	}	
}