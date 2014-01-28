<?php
/**
 * Created by IntelliJ IDEA.
 * User: cgerpheide
 * Date: 8/29/13
 * Time: 3:29 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Risk\Model;

class Model {

	protected $data = [];
	
    /**
     * Convenience method since I always want find_all to return an array.
     *
     * @param array $data
     * @param null $order_by_column
     * @param string $direction
     * @param array $fields
     * @return array
     */
    public static function find_all($data = array(), $order_by_column = null)
    {
    	$db = new \PDO('mysql:host=localhost;dbname=risk', 'root', 'root');
    	
    	$query = 'SELECT * FROM ' . static::$table;
    	
    	if($data)
    	{
    		$keys_with_equals = array_map(function($v){ return '`'.$v.'`=?'; }, array_keys($data));
    		$where = ' WHERE ' . implode(' AND ', $keys_with_equals);
    		$query .= $where;
    	}
    	
    	$stmt = $db->prepare($query);
    	
    	$stmt->execute(array_values($data));
    	$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    	
    	if(!$rows) $rows = [];
    	
    	// Convert to objects
    	$objs = [];
    	foreach($rows as $row)
    	{
    		$objs[] = new static($row);
    	}
    	
        return $objs;
    }
    
    public function __construct($data = []) 
    {
		$this->data = $data;
    }
    
    public function __call($name, $args)
    {
    	return $this->data[$name];
    }
    
    // TODO implement
    public function find_i($params);
    public function delete();
    public function set($field, $value);
    public function save();

}