<?php defined('SYSPATH') or die('No direct script access.');

abstract class Rest extends Controller_Rest
{
    public function before()
    {
        parent::before();
    }

    public function init()
    {
        self::load_offset();    // load pagination offset

        self::load_limit();     // load pagination limit

        self::load_sort();      // load sort column

        self::load_order();     // load sort order

        self::load_fields();    // load fetch fields

        self::load_filters();   // load search filters
    }

    protected function load_offset()
    {
        if (isset($this->_params['offset']) && $this->_params['offset'] != '') {
            $this->pagination_offset = $this->_params['offset'];
        }
    }

    protected function load_limit()
    {
        if (isset($this->_params['limit']) && $this->_params['limit'] != '') {
            $this->pagination_limit = $this->_params['limit'] > $this->max_limit ? $this->max_limit : $this->_params['limit'];
        }
    }

    protected function load_sort()
    {
        if (isset($this->_params['sort']) && $this->_params['sort'] != '') {
            if (in_array($this->_params['sort'], $this->_fetchable_fields)) {
                $this->sort_column = $this->_params['sort'];
            } else {
                $khe = new Kohana_HTTP_Exception_400('Bad Request: Sort column is not fetchable');
                $this->_error($khe);
            }
        }
    }

    protected function load_order()
    {
        if (isset($this->_params['order']) && $this->_params['order'] != '') {
            if ($this->_params['order'] == 'asc' || $this->_params['order'] == 'desc') {
                $this->sort_order = $this->_params['order'];
            } else {
                $khe = new Kohana_HTTP_Exception_400('Bad Request: Invalid Order');
                $this->_error($khe);
            }
        }
    }

    protected function load_fields()
    {
        $this->fields = $this->_fetchable_fields;

        if (isset($this->_params['fields']) && $this->_params['fields'] != '') {
            $this->fields = explode(',', $this->_params['fields']);
            
            foreach ($this->fields as $field) {
                if (!in_array($field, $this->_fetchable_fields)) {
                    $khe = new Kohana_HTTP_Exception_400("Bad Request: Field '$field' is not fetchable");
                    $this->_error($khe);
                }
            }
        }
    }

    protected function load_filters()
    {
        if (isset($this->_params['filters']) && $this->_params['filters'] != '') {
            $this->filters = self::build_filters($this->_params['filters'], $this->_fetchable_fields);

            if (!$this->filters) {
                $this->_error("Bad Request: Invalid filter", 400);
            }
        }
    }

    public static function build_filters($filters, $fetchable, $operators = array( '=', '>', '<' ))
    {
        $operator = '=';        // default operator
        $filters_array = array();
        
        foreach (explode(',', $filters) as $filter) {
            for ($i = 0; $i < count($operators); $i++) {
                if (strpos($filter, $operators[$i])) {
                    $operator = $operators[$i];
                }
            }

            $pairs = explode($operator, $filter);

            if (count($pairs) == 2 && in_array($pairs[0], $fetchable)) {
                $filters_array[$pairs[0]] = array(
                    'operator'  => $operator,
                    'value'     => $pairs[1]
                );
            } else {
                return false;
            }
        }

        return $filters_array;
    }

    public function get_results($resources, $table = null)
    {
        // set meta information which has to be appended to response
        $this->metadata['offset'] = $this->pagination_offset;
        $this->metadata['limit'] = $this->pagination_limit;
            
        $order = array(
            $this->sort_column => $this->sort_order
        );

        return self::load_conditions(
            $resources,
            $order,
            $this->pagination_offset,
            $this->pagination_limit,
            $this->filters
        )->find_all();
    }

    public function fetch_data($resources, $fields, $is_single_resource = false, $callable = null)
    {
        $data = array();        // will hold class data

        if ($is_single_resource) {
            $resource = $resources;
            foreach ($fields as $field) {
                if (isset($resource->$field)) {
                    $data[$field] = $resource->get($field);
                }
            }
            $data = $callable ? $callable($resource, $data) : $data;
        } else {
            $temp = array();

            foreach ($resources as $resource) {
                foreach ($fields as $field) {
                    if (isset($resource->$field)) {
                        $temp[$field] = $resource->get($field);
                    }
                }
                $temp = $callable ? $callable($resource, $temp) : $temp;
                $data[] = $temp;
            }
        }

        return $data;
    }

    /**
    * Get rows determined by where clauses
    * @param	Array	$conditions
    * Simple:
    * array('field1' => $value1, 'field2' => $value2)
    * Advanced:
    * array(
    *      id' => array(
    *          'relation' => 'OR'          // 'AND', 'OR', default 'AND',
    *          'operator' = 'like',        // <, >, =, in, between
    *          'value' => '123',	        // in case of between: array( 25, 30 )
    *      )
    *  )
    * @param 	Array	$order    optional: array('field1' => 'DESC', 'field2' => 'ASC')
    * @return	FALSE | Database_MySQL_Result
    */
    public static function load_conditions($resources, $order = null, $offset = null, $limit = null, $conditions = null)
    {
        if (is_array($conditions)) {
            foreach ($conditions as $field => $condition) {
                // treat a simple condition
                if (!is_array($condition)) {
                    $resources->where($field, "=", $condition);
                } else {
                    $relation = "and_where";

                    if (array_key_exists("relation", $condition) && strtolower($condition["relation"]) == "or") {
                        $relation = "or_where";
                    }

                    $resources->$relation($field, $condition["operator"], $condition["value"]);
                }
            }
        }

        // set the optional order clause
        if ($order) {
            foreach ($order as $field => $direction) {
                $resources->order_by($field, $direction);
            }
        }

        if ($offset) {
            $resources->offset($offset);
        }

        if ($limit) {
            $resources->limit($limit);
        }

        return $resources;
    }
}   // End
