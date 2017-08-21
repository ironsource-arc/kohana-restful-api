<?php defined('SYSPATH') or die('No direct script access.');

abstract class Rest extends Controller_Rest {

    /**
     * Supported operators for filters
     *
     * @var Array
     */
    protected $operators = array( '=', '>', '<' );

    public function before()
    {
        parent::before();

        self::load_user();      // load user object

        self::load_offset();    // load pagination offset

        self::load_limit();     // load pagination limit

        self::load_sort();      // load sort column

        self::load_order();     // load sort order

        self::load_fields();    // load fetch fields

        self::load_filters();   // load search filters
    }

    protected function load_user()
    {
        $this->user = $this->_user->user;
    }

    protected function load_offset()
    {
        if( isset($this->_params['offset']) && $this->_params['offset'] != '' )
        {
            $this->pagination_offset = $this->_params['offset'];
        }
    }

    protected function load_limit()
    {
        if( isset($this->_params['limit']) && $this->_params['limit'] != '' )
        {
            $this->pagination_limit = $this->_params['limit'];
        }
    }

    protected function load_sort()
    {
        if( isset($this->_params['sort']) && $this->_params['sort'] != '' )
        {
            if( in_array($this->_params['sort'], $this->_fetchable_fields) )
            {
                $this->sort_column = $this->_params['sort'];
            }
            else
            {
                $khe = new Kohana_HTTP_Exception_400('Bad Request: Sort column is not fetchable');
                $this->_error($khe);
            }
        }
    }

    protected function load_order()
    {
        if( isset($this->_params['order']) && $this->_params['order'] != '' )
        {
            if( $this->_params['order'] == 'asc' || $this->_params['order'] == 'desc' )
            {
                $this->sort_order = $this->_params['order'];
            }
            else
            {
                $khe = new Kohana_HTTP_Exception_400('Bad Request: Invalid Order');
                $this->_error($khe);
            }
        }
    }

    protected function load_fields()
    {
        $this->fields = $this->_fetchable_fields;

        if( isset($this->_params['fields']) && $this->_params['fields'] != '' )
        {
            $this->fields = explode(',', $this->_params['fields']);
            
            foreach( $this->fields as $field )
            {
                if( !in_array($field, $this->_fetchable_fields) )
                {
                    $khe = new Kohana_HTTP_Exception_400("Bad Request: Field '$field' is not fetchable");
                    $this->_error($khe);
                }
            }
        }
    }

    protected function load_filters()
    {
        if( isset($this->_params['filters']) && $this->_params['filters'] != '' )
        {
            $this->filters = array();

            foreach( explode(',', $this->_params['filters']) as $filter )
            {
                for( $i = 0; $i < count($this->operators); $i++)
                {
                    if( strpos( $filter, $this->operators[$i] ) )
                    {
                        $operator = $this->operators[$i];
                    }
                }

                $pairs = explode( $operator, $filter );

                if( count( $pairs ) == 2 && in_array($pairs[0], $this->_fetchable_fields) )
                {
                    $this->filters[$pairs[0]] = array(
                        'operator'  => $operator,
                        'value'     => $pairs[1]
                    );
                }
                else
                {
                    $khe = new Kohana_HTTP_Exception_400('Bad Request: Filter is not available');
                    $this->_error($khe);
                }
            }
        }
    }

    protected function load_results( $resources )
    {
        if( $this->request->param( $this->subresource_key ) )
        {
            $resource = $resources->where(
                'id', '=', $this->request->param( $this->subresource_key )
            )->find();

            if( !$resource->loaded() )
            {
                $khe = new Kohana_HTTP_Exception_400('Bad Request: Access denied');
                $this->_error($khe);
            }

            return $resource;
        }
        else
        {
            // set meta information which has to be appended to response
            $this->metadata['pagination']['offset'] = $this->pagination_offset;
            $this->metadata['pagination']['limit'] = $this->pagination_limit;
            
            $order = array(
                $this->sort_column => $this->sort_order
            );

            return self::load_conditions(
                $resources, $this->filters, $order, $this->pagination_offset, $this->pagination_limit
            );
        }
    }

    public function fetch_data()
    {
        $data = array();        // will hold class data

        if( count( $this->resources ) == 1 )
        {
            foreach( $this->fields as $field )
            {
                $data[$field] = $this->resources->get($field);
            }
        }
        else
        {
            $temp = array();

            foreach( $this->resources as $resource )
            {
                foreach( $this->fields as $field )
                {
                    $temp[$field] = $resource->get($field);
                }
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
    public function load_conditions( $resources, $conditions = NULL, $order = NULL, $offset = NULL, $limit = NULL )
    {
        if( is_array($conditions) )
        {
            foreach($conditions as $field => $condition)
            {
                // treat a simple condition
                if( !is_array($condition) )
                {
                    $resources->where( $field, "=", $condition );
                }
                else
                {
                    $relation = "and_where";

                    if(array_key_exists("relation", $condition) && strtolower($condition["relation"]) == "or")
                    {
                        $relation = "or_where";
                    }

                    $resources->$relation( $field, $condition["operator"], $condition["value"] );
                }
            }
        }

        // set the optional order clause
        if( $order )
        {
            foreach($order as $field => $direction)
            {
                $resources->order_by($field, $direction);
            }
        }

        if( $offset )
        {
            $resources->offset( $offset );
        }

        if( $limit )
        {
            $resources->limit( $limit );
        }

        return $resources->find_all();
    }

}   // End
