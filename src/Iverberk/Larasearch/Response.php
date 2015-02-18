<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Model;
use Iverberk\Larasearch\Response\Results;

class Response {

	/**
	 * @var \Illuminate\Database\Eloquent\Model
	 */
	private $model;

	/**
	 * Elasticsearch response
	 *
	 * @var array
	 */
	private $response;

	/**
	 * @param Model $model
	 * @param array $response
	 */
	public function __construct(Model $model, Array $response)
	{
		$this->model = $model;
		$this->response = $response;
	}

	/**
	 * @return Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * @return array
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @return Results
	 */
	public function getResults()
	{
		return new Results($this);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Collection|static[]
	 */
	public function getRecords()
	{
		$hits = $this->getHits();
		if(count($hits) > 0)
		{
			$ids = []; $queryids = [];
			for ($i = 0, $length = count($hits); $i < $length; $i++) {
				$hit =& $hits[$i];
				$hit['_i'] = $i;
				$ids[$hit['_id']] = $hit;
			}
			foreach ($ids as $id => &$hit) {
				$queryids[] = $id;
			}
			
			$records = call_user_func_array(array($this->model, 'whereIn'), array('id', $queryids))->get();
			
			foreach ($records as &$record) {
				$record->hit = $ids[$record->id];
			}
			
			$records->sortBy(function($record) {
				return $record->hit['_i'];
			});
			
			return $records;
		}
		else
		{
			return call_user_func(array($this->model, 'newCollection'));
		}
	}

	/**
	 * @return mixed
	 */
	public function getTook()
	{
		return $this->response['took'];
	}

	/**
	 * @return mixed
	 */
	public function getHits()
	{
		return $this->response['hits']['hits'];
	}

	/**
	 * @return mixed
	 */
	public function getTimedOut()
	{
		return $this->response['timed_out'];
	}

	/**
	 * @return mixed
	 */
	public function getShards()
	{
		return $this->response['_shards'];
	}

	/**
	 * @return mixed
	 */
	public function getMaxScore()
	{
		return $this->response['hits']['max_score'];
	}

	/**
	 * @return mixed
	 */
	public function getTotal()
	{
		return $this->response['hits']['total'];
	}

	/**
	 * @param array $fields
	 * @return mixed
	 */
	public function getSuggestions($fields = [])
	{
		if (!empty($fields))
		{
			$results = [];
			foreach ($fields as $field)
			{
				foreach ($this->response['suggest'] as $key => $value)
				{
					if (preg_match("/^${field}.*/", $key) !== false)
					{
						$results[$field] = $value;
					}
				}
			}

			return $results;
		}
		else
		{
			return $this->response['suggest'];
		}
	}

	/**
	 * @param string $name
	 * @return array
	 */
	public function getAggregations($name = '')
	{
		return empty($name) ? $this->response['aggregations'] : $this->response['aggregations'][$name];
	}

}