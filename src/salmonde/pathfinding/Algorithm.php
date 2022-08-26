<?php
declare(strict_types=1);

namespace salmonde\pathfinding;

use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\World;
use salmonde\pathfinding\utils\validator\Validator;

abstract class Algorithm {

	protected Vector3 $startPos;
	protected Vector3 $targetPos;
	private World $world;
	private ?PathResult $pathResult = null;

	/** @var Validator[] */
	protected array $validatorsFlattened = [];
	/** @var Validator[][] */
	private array $validators = [];

	public function __construct(World $world, Vector3 $startPos, Vector3 $targetPos) {
		$this->world = $world;
		$this->startPos = $startPos;
		$this->targetPos = $targetPos;
		$this->reset();
	}

	abstract public function reset() : void;

	public function getWorld() : World {
		return $this->world;
	}

	public function getStartPos() : Vector3 {
		return $this->startPos;
	}

	public function setStartPos(Vector3 $startPos) : void {
		$this->startPos = $startPos;
		$this->reset();
	}

	public function getTargetPos() : Vector3 {
		return $this->targetPos;
	}

	public function setTargetPos(Vector3 $targetPos) : void {
		$this->targetPos = $targetPos;
		$this->reset();
	}

	public function addValidator(Validator $validator) : void {
		$this->validators[$validator->getPriority()][spl_object_hash($validator)] = $validator;
		$this->revamp();
	}

	public function removeValidator(Validator $validator) : void {
		unset($this->validators[$validator->getPriority()][spl_object_hash($validator)]);
		$this->revamp();
	}

	public function getValidators() : array {
		return $this->validatorsFlattened;
	}

	public function getHighestValidatorPriority() : int {
		if (count($this->getValidators()) === 0) {
			return 0;
		}

		return max($this->getValidatorPriorities());
	}

	protected function getValidatorPriorities() : array {
		$priorities = [];
		foreach ($this->getValidators() as $validator) {
			$priorities[] = $validator->getPriority();
		}

		return $priorities;
	}

	public function getLowestValidatorPriority() : int {
		if (count($this->getValidators()) === 0) {
			return 0;
		}

		return min($this->getValidatorPriorities());
	}

	public function getPathResult() : ?PathResult {
		return $this->pathResult;
	}

	protected function setPathResult(PathResult $pathResult) : void {
		$this->pathResult = $pathResult;
	}

	public function resetPathResult() : void {
		$this->pathResult = null;
	}

	abstract public function tick() : void;

	abstract public function isFinished() : bool;

	protected function isValidBlock(Block $block, int $side) : bool {
		$oppositeSide = Facing::opposite($side);
		foreach ($this->getValidators() as $validator) {
			if (!$validator->isValidBlock($this, $block, $oppositeSide)) {
				return false;
			}
		}

		return true;
	}

	private function revamp() : void {
		$this->validatorsFlattened = [];
		foreach ($this->validators as $validators) {
			$this->validatorsFlattened = [...$this->validatorsFlattened, ...$validators];
		}
	}
}
