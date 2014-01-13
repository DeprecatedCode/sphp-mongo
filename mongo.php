<?php

if (!class_exists("MongoClient")) {
  throw new Exception("MongoDB PHP extension not found");
}

/**
 * Create a new Mongo Connection
 */
sys::$plugin->mongo = cmd('mongo', null, array(
  
  'object' => function ($config) {
  
    certify($config);
    
    $user = isset($config->username) ?
      $config->username . ':' . $config->password . '@' : '';
    
    $host = isset($config->host) ? $config->host : 'localhost';
    
    $port = isset($config->port) ? ':' . $config->port : '';
    
    $conn = new MongoClient("mongodb://$user$host$port");
    
    /**
     * Select a Database
     */
    return cmd('mongo.connection', null, array(
      'string' => function ($cmd, $name) use ($conn) {
        
        $db = $conn->selectDB($name);
        
        /**
         * Select a Collection
         */
        return cmd('mongo.database', null, array(
          'string' => function ($cmd, $name) use ($db) {
            
            $collection = $db->$name;
        
            $proxy = new stdClass;
            
            /**
             * Drop Collection
             */
            $proxy->drop = function () use ($collection) {
              return $collection->drop();
            };
            
            /**
             * Collection Name
             */
            $proxy->name = $name;
            
            /**
             * Insert a document
             */
            $proxy->insert = cmd('mongo.insert', null, array(
              'object' => function ($cmd, $object) use ($collection) {
                certify($object);
                return $collection->insert(SPHP_Mongo::format($object));
              },
              'array' => function ($cmd, $array) use ($collection) {
                certify($array);
                $parent = null;
                $arr = a($parent);
                $results = array();
                foreach($array->{'#value'} as $object) {
                  $results[] = apply($cmd, $object);
                }
                $arr->{'#value'} = $results;
                return $arr;
              }
            ));
            
            /**
             * Find documents
             */
            $proxy->find = cmd('mongo.find', null, array(
              'object' => function ($cmd, $object) use ($collection) {
                certify($object);
                $arr = arr($collection->find(SPHP_Mongo::format($object)));
                
                /**
                 * Skip
                 */
                $arr->skip = cmd('mongo.find.skip', null, array(
                  'integer' => function ($skipCmd, $integer) use ($arr) {
                    $arr->{'#value'}->skip($integer);
                    return $arr;
                  }
                ));

                /**
                 * Sort
                 */
                $arr->sort = cmd('mongo.find.sort', null, array(
                  'object' => function ($sortCmd, $object) use ($arr) {
                    certify($object);
                    $sort = array();
                    foreach($object as $key => $value) {
                      if ($key[0] !== '#') {
                        $sort[$key] = $value;
                      }
                    }
                    $arr->{'#value'}->sort($sort);
                    return $arr;
                  }
                ));
                
                /**
                 * Limit
                 */
                $arr->limit = cmd('mongo.find.limit', null, array(
                  'integer' => function ($limitCmd, $integer) use ($arr) {
                    $arr->{'#value'}->limit($integer);
                    return $arr;
                  }
                ));
                
                /**
                 * Return Cursor Array
                 */
                return $arr;
              },
              'array' => function ($cmd, $array) use ($collection) {
                certify($array);
                $results = array();
                foreach($array->{'#value'} as $object) {
                  $results[] = apply($cmd, $object);
                }
                return arr($results);
              }
            ));
            
            /**
             * Count collection
             */
            $proxy->length = cmd('mongo.length', null, array(
              'object' => function ($cmd, $object) use ($collection) {
                return $collection->count(SPHP_Mongo::format($object));
              }
            ));
            
            /**
             * Return the proxy
             */
            return $proxy;
          }

        ));
      
      }
        
    ));

  }

));

/**
 * Helper Class
 */
class SPHP_Mongo {
  public static function format($value) {
    if (is_object($value)) {
      
      /**
       * Regex
       */
      if (isset($value->{'#type'}) && $value->{'#type'} === 'regex') {
        return array('$regex' => $value->{'#value'}, '$options' => 'i');
      }
      
      $result = array();
      $fn = proto($value)->{'#each'};
      $fn($value, function ($key, $val) use (&$result) {
        $result[$key] = SPHP_Mongo::format($val);
      });
      return $result;
    }
    else if (is_array($value)) {
      $result = array();
      foreach ($value as $val) {
        $result[] = SPHP_Mongo::format($val);
      }
      return $result;
    }
    return $value;
  }
}
