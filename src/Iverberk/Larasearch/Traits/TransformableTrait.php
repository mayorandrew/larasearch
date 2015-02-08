<?php namespace Iverberk\Larasearch\Traits;

use Illuminate\Support\Facades\Config;

trait TransformableTrait {

	/**
	 * Transform the Person model and its relations to an Elasticsearch document.
	 *
	 * @param array $relations
	 * @return array
	 */
	public function transform($relations = [])
	{
		if ($relations) {
			$items = $this->load($relations);
			$doc = $items->toArray();
			foreach ($items->relations as $relationName => &$relation) {
				$doc[$relationName] = $relation->map(function($item) {
					return $item->transform();
				})->toArray();
			}
		} else {
			$doc = $this->toArray();
		}

		return $doc;
	}

}