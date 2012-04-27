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
		$constraint = $query->getConstraint();
		if($constraint instanceof Tx_Extbase_Persistence_QOM_StatementInterface) {
			throw new Tx_Extbase_Persistence_Storage_Exception_BadConstraint('Could not execute count on queries with a constraint of type Tx_Extbase_Persistence_QOM_StatementInterface', 1256661045);
		}
		$parameters = array();
		
		$statement = $query->getStatement();
		if($statement instanceof Tx_Extbase_Persistence_QOM_Statement) {
			$sql = $statement->getStatement();
			$parameters = $statement->getBoundVariables();
		} else {
			$parameters = array();
			$statementParts = $this->parseQuery($query, $parameters);
			$sql = $this->buildQuery($statementParts, $parameters);
		}
		$this->replacePlaceholders($sql, $parameters);

		$sqlParser = Tx_ExtbaseHijax_Persistence_Parser_SQL::ParseString($sql);
				
			// if limit is set, we need to count the rows "manually" as COUNT(*) ignores LIMIT constraints
		if ($sqlParser->getLimitStatement()) {
			$result = $this->databaseHandle->sql_query($sql);
			$this->checkSqlErrors($statement);
			$count = $this->databaseHandle->sql_num_rows($result);
		} else {
			$result = $this->databaseHandle->sql_query($sqlParser->getCountQuery());
			$this->checkSqlErrors($statement);
			$rows = $this->getRowsFromResult($query->getSource(), $result);
			$count = current(current($rows));
		}
		$this->databaseHandle->sql_free_result($result);
		return (int)$count;
	}
}

?>