<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
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

class Tx_ExtbaseHijax_HTMLConverter_PDFConverter extends Tx_ExtbaseHijax_HTMLConverter_AbstractConverter {
	/**
	 * @param Tx_Extbase_MVC_Web_Response $response
	 * @return Tx_Extbase_MVC_Web_Response
	 */
	public function convert($response) {
		$pathToPDFGenFile = $this->extensionConfiguration->get('pathToPDFGenFile');
		if ($pathToPDFGenFile) {
			list($return_value, $output, $error) = $this->runCommands($pathToPDFGenFile, $response->getContent());
			if ($return_value != 0) {
				error_log("Tx_ExtbaseHijax_HTMLConverter_PDFConverter error:\n$error");
				/* @var $failedConversionException Tx_ExtbaseHijax_HTMLConverter_FailedConversionException */
				$failedConversionException = t3lib_div::makeInstance('Tx_ExtbaseHijax_HTMLConverter_FailedConversionException');
				$failedConversionException->setError($error);
				$failedConversionException->setInput($response);
				$failedConversionException->setOutput($output);
				$failedConversionException->setReturnValue($return_value);
				throw $failedConversionException;
			} else {
				$filename = $this->extractTitle($response->getContent());

				$response->setContent($output);
				$response->setHeader('Content-Type', 'application/pdf');

				if ($filename) {
					require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');
					$fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions'); /* @var $fileFunc t3lib_basicFileFunctions */
					$filename = '; filename = "' . $fileFunc->cleanFileName($filename) . '.pdf"';

					$response->setHeader('Content-Disposition', 'attachment'.$filename);
				} else {
					$response->setHeader('Content-Disposition', 'inline');
				}
			}
		}

		return $response;
	}

	/**
	 * @param string $content
	 * @return string
	 */
	protected function extractTitle($content) {
		preg_match('/<h1([^>]*)>(.*)<\/h1>/msU', $content, $matches);

		return count($matches) ? trim($matches[2]) : '';
	}

	/**
	 * @param string $cmd
	 */
	protected function runCommands($cmds, $output = '', $pipe = TRUE) {
		if (!is_array($cmds)) {
			$cmds = array($cmds);
		}

		foreach ($cmds as $cmd) {
			$proc = proc_open($cmd,
				array(
					array("pipe","r"), //stdin
					array("pipe","w"), //stdout
					array("pipe","w")  //stderr
				),
				$pipes);

			if ($output && $pipe) {
				fwrite($pipes[0], $output);
			}
			fclose($pipes[0]);

			$output = stream_get_contents($pipes[1]);
			$error = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			$return_value = @proc_close($proc);
			if ($error) {
				break;
			}
		}

		return array($return_value, $output, $error);
	}
}