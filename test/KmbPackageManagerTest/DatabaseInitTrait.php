<?php
/**
 * @copyright Copyright (c) 2014 Orange Applications for Business
 * @link      http://github.com/kambalabs for the sources repositories
 *
 * This file is part of Kamba.
 *
 * Kamba is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * Kamba is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kamba.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace KmbPackageManagerTest;

use PDO;
use PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet;
use PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection;
use PHPUnit_Extensions_Database_Operation_Factory;

trait DatabaseInitTrait
{
    /**
     * @param PDO $connection
     */
    public static function initSchema(PDO $connection)
    {
        $connection->exec(file_get_contents(__DIR__ . '/../../../KmbMcollective/data/migrations/sqlite/mcollective_schema.sql'));
    }

    /**
     * @param PDO $connection
     */
    public static function initFixtures(PDO $connection)
    {
        PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT()->execute(
            new PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($connection),
            new PHPUnit_Extensions_Database_DataSet_FlatXmlDataSet(__DIR__ . '/../data/fixtures.xml')
        );
    }
}
