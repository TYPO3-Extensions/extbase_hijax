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

class Tx_ExtbaseHijax_Persistence_Storage_Typo3DbBackend extends Tx_Extbase_Persistence_Storage_Typo3DbBackend {	
	/**
	 * Returns the number of tuples matching the query.
	 *
	 * @param Tx_Extbase_Persistence_QOM_QueryObjectModelInterface $query
	 * @return integer The number of matching tuples
	 */
	public function getObjectCountByQuery(Tx_Extbase_Persistence_QueryInterface $query) {
		$parameters = array();
		$statement = $query->getStatement();
		if($statement instanceof Tx_Extbase_Persistence_QOM_Statement) {
				/*
				 * Overriding default extbase logic for manually passed SQL
				 */
			$sql = $statement->getStatement();
			$parameters = $statement->getBoundVariables();
			$this->replacePlaceholders($sql, $parameters);
			
			$sqlParser = Tx_ExtbaseHijax_Persistence_Parser_SQL::ParseString($sql);

			$countQuery = $sqlParser->getCountQuery();
			$result = $this->databaseHandle->sql_query($countQuery);
			$this->checkSqlErrors($countQuery);
			$rows = $this->getRowsFromResult($query->getSource(), $result);
			$count = current(current($rows));
			$this->databaseHandle->sql_free_result($result);
		} else {
				/*
				 * Default Extbase logic
				 */
			$count = parent::getObjectCountByQuery($query);
		}

		return (int)$count;
	}
}

?>