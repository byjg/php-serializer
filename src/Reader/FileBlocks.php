<?php

namespace ByJG\Serializer\Reader;

use IteratorAggregate;

/**
 * The blocks of a file, read one at a time each time they are iterated. A Serialize holding it, created by
 * Serialize::fromFile() or Serialize::from(), works over its blocks: toIterator() gives each one, and a
 * to*() method converts all of them.
 *
 * It tells the blocks of a file apart from an object that only can be iterated (a collection), which
 * Serialize::from() converts as any object.
 *
 * @extends IteratorAggregate<int, mixed>
 */
interface FileBlocks extends IteratorAggregate
{
}
