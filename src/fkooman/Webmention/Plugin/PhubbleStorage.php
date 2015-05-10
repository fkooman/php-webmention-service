<?php

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace fkooman\Webmention\Plugin;

use PDO;
use RuntimeException;

class PhubbleStorage
{
    /** @var PDO */
    private $db;

    /** @var string */
    private $prefix;

    public function __construct(PDO $db, $prefix = '')
    {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db = $db;
        $this->prefix = $prefix;
    }

    public function mention($source, $target, $time)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'INSERT INTO %s (id, source, time) VALUES(:id, :source, :time)',
                $this->prefix.'mentions'
            )
        );
        $stmt->bindValue(':id', $target, PDO::PARAM_STR);
        $stmt->bindValue(':source', $source, PDO::PARAM_STR);
        $stmt->bindValue(':time', $time, PDO::PARAM_INT);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new RuntimeException('unable to add');
        }
    }
}
