<?php defined('SYSPATH') or die('No direct script access.');

abstract class Rest extends Controller_Rest {

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

        if( $this->request->param('business_id') )
        {
            $this->business = $this->user->businesses->where(
                'id', '=', $this->request->param('business_id')
            )->find();

            if( !$this->business->loaded() )
            {
                throw new Kohana_HTTP_Exception_400('Bad Request: Access denied');
            }
        }
    }

    protected function load_user()
    {
        $this->user = $this->_user->user;
    }

    protected function load_offset()
    {
        if( isset($this->_params['offset']) )
        {
            $this->pagination_offset = $this->_params['offset'];
        }
    }

    protected function load_limit()
    {
        if( isset($this->_params['limit']) )
        {
            $this->pagination_limit = $this->_params['limit'];
        }
    }

    protected function load_sort()
    {
        if( isset($this->_params['sort']) && in_array($this->_params['sort'], $this->_fetchable_fields) )
        {
            $this->sort_column = $this->_params['sort'];
        }
        else
        {
            throw new Kohana_HTTP_Exception_400('Bad Request: Sort column is not fetchable');
        }
    }

    protected function load_order()
    {
        if( isset($this->_params['order']) )
        {
            if( $this->_params['order'] == 'asc' || $this->_params['order'] == 'desc' )
            {
                $this->sort_order = $this->_params['order'];
            }
        }
    }

    protected function load_fields()
    {
        $this->fields = $this->_fetchable_fields;

        if( isset($this->_params['fields']) && $this->_params['fields'] != '' )
        {
            $this->fields = explode(',', $this->_params['fields']);
        }
    }

    protected function load_filters()
    {
        if( isset($this->_params['filters']) && $this->_params['filters'] != '' )
        {
            $this->filters = array();

            foreach( explode(',', $this->_params['filters']) as $filter )
            {
                $pairs = explode( '=', $filter );

                if( count( $pairs ) == 2 && in_array($pairs[0], $this->_fetchable_fields) )
                {
                    $this->filters[$pairs[0]] = array(
                        'operator'  => '=',
                        'value'     => $pairs[1]
                    );
                }
                else
                {
                    throw new Kohana_HTTP_Exception_400('Bad Request: Filter is not available');
                }
            }
        }
    }

    protected function load_results( $resources )
    {
        $order = array(
            $this->sort_column => $this->sort_order
        );

        return self::load_conditions(
            $resources, $this->filters, $order, $this->pagination_offset, $this->pagination_limit
        );
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
