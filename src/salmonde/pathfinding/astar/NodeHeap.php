<?php
declare(strict_types=1);

namespace salmonde\pathfinding\astar;

use SplMinHeap;

class NodeHeap extends SplMinHeap {
	protected function compare($value1, $value2) : int {
		return (int) ($value2->getF() - $value1->getF());
	}
}
