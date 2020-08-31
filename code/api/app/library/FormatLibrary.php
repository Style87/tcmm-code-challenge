<?php
namespace Phalcon;

use \DomainException;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Paginator\Factory;

abstract class FormatLibrary
{
  public static function format($data, $columns = null, $relations = [], $filter = null, $page = null, $perPage = null, $order = null)
  {
    if (is_null($data)) return '';
    if (is_numeric($data)) return floatval($data);


    if (is_a($data,'\Phalcon\Mvc\Model',false)) {
      return $data->toArray($columns, $relations);
    }
    if (is_a($data,'\Phalcon\Mvc\Model\Criteria',false)) {
      return self::formatCriteria($data, $columns, $relations, $page, $perPage, $order);
    }
    if (is_a($data,'\Phalcon\Mvc\Model\Query\Builder',false)) {
      return self::formatBuilder($data, $columns, $relations, $filter, $page, $perPage, $order);
    }
    if (is_a($data,'\Phalcon\Paginator\Adapter\Model',false)) {
      return self::formatPage($data, $columns, $relations);
    }
    if (is_a($data,'\Phalcon\Mvc\Model\ResultsetInterface',false)) {
      return self::formatResultSet($data, $columns, $relations, $page, $perPage);
    }

    // We really shouldn't be returning these
    if (is_array($data)) return self::formatObjectArray($data, $columns, $relations);
    if (is_object($data)) return self::formatObject($data, $columns);
    return $data;
  }

  public static function formatBuilder($data, $columns, $relations, $filter, $page, $perPage, $order) {

    $isSingleResult = false;
    if ($data->getLimit() == 1 || $perPage == 1) {
      $isSingleResult = true;
    }

    if (is_null($data->getFrom()) && $filter && $filter['from']) {
      $data->from($filter['from']);
    }

    if ($filter && $filter['from'] === $data->getFrom()) {
      if ($filter['columns']) {
        $columns = $filter['columns'];
      }
      else if (count($filter['select']) > 0) {
        $data->columns($filter['select']);
        $columns = null;
        $filter['columns'] = null;
      }
      if ($filter['distinct']) {
        $data->distinct($filter['distinct']);
      }
      if (isset($filter['conditions'])) {
        $data->andWhere($filter['conditions'], $filter['bind']);
      }
      if ($filter['order']) {
        $data->orderBy($filter['order']);
      }
      if ($filter['having']) {
        foreach ($filter['having'] as $having) {
          if ($having['or']) {
            $data->orHaving($having['condition'], $having['bind']);
          } else {
            $data->andHaving($having['condition'], $having['bind']);
          }
        }
      }
      if ($filter['groupBy']) {
        $data->groupBy($filter['groupBy']);
      }
      if (count($filter['join']) > 0) {
        foreach ($filter['join'] as $key => $join) {
          if ($join['left']) {
            if (array_key_exists('alias', $join) && $join['alias']) {
              $data->leftJoin($key, $join['condition'], $join['alias']);
            } else {
              $data->leftJoin($key, $join['condition']);
            }
          }
          else {
            if (array_key_exists('alias', $join) && $join['alias']) {
              $data->join($key, $join['condition'], $join['alias']);
            } else {
              $data->join($key, $join['condition']);
            }

          }
          $data = $key::getBuilder($data);
        }
      }
    }

    if ($order) {
      $data->orderBy($order);
    }

    if (isset($filter['relations']) && is_array($filter['relations']) && !empty($filter['relations'])) {
      $relations = array_merge($relations, $filter['relations']);
    }

    if (!$isSingleResult && is_numeric($page) && is_numeric($perPage)) {
      $options = [
          'builder' => $data,
          'limit'   => $perPage,
          'page'    => $page,
          'adapter' => 'queryBuilder',
      ];
      $paginator = Factory::load($options);

      return self::formatPage($paginator, $columns, $relations);
    }

    // Set the limit and offset single results of any page can be returned.
    if (is_numeric($perPage)) {
      $data->limit($perPage);
    }
    if (is_numeric($page)) {
      $data->offset($page-1);
    }

    $page = $data->getQuery()->execute();

    if ($isSingleResult) {
      if (isset($page[0])) {
        return $page[0]->toArray($columns, $relations);
      } else {
        return [];
      }
    }

    return self::formatArray($page, $columns, $relations);
  }

  public static function formatCriteria($data, $columns, $relations, $page, $perPage, $order) {
    if ($order) {
      $data->orderBy($order);
    }

    $data = $data->execute();

    if ($page && $perPage) {
      return self::formatPage(new PaginatorModel(
        [
          "data"  => $data,
          "limit" => $perPage,
          "page"  => $page,
        ]
      ), $columns, $relations);
    }

    return self::formatArray($data, $columns, $relations);
  }

  public static function formatPage($data, $columns, $relations) {
    // Get the paginated results
    $page = $data->getPaginate();

    if (!is_null($columns) || !is_null($relations)) {
      if (is_array($page->items)) {
        $page->items = array_map(function($item) use($columns, $relations){
          return $item->toArray($columns, $relations);
        }, $page->items);
      } else if (is_object($page->items)) {
        $output = [];
        foreach ($page->items as $datum) {
          $output[] = $datum->toArray($columns, $relations);
        }
        $page->items = $output;
      }
    }

    return $page;
  }

  public static function formatObjectArray($dataArray, $columns)
  {
    $json = array();

    if(!self::is_assoc($dataArray))
    {
      foreach($dataArray as $item)
      {
        $json[] = self::format($item, $columns);
      }
    } else {
      foreach($dataArray as $key => $value)
      {
        $json[$key] = self::format($value, $columns);
      }
      unset($json['pass']);
    }
    return $json;
  }

  static function is_assoc($array) {
    return (bool)count(array_filter(array_keys((array) $array), 'is_string'));
  }

  public static function formatObject($data, $columns)
  {
    if (!is_null($columns)) {
      $columns = array_flip($columns);

      $data = array_map(function($item) use($columns){
        return array_intersect($item, $columns);
      }, $data);
    }

    unset($data->pass);
    return $data;
  }

  public static function formatResultSet($data, $columns, $relations, $page, $perPage)
  {
    if ($page && $perPage) {
      return self::formatPage(new PaginatorModel(
        [
          "data"  => $data,
          "limit" => $perPage,
          "page"  => $page,
        ]
      ), $columns, $relations);
    }

    return self::formatArray($data, $columns, $relations);
  }

  public static function formatArray($page, $columns, $relations) {
    $output = [];

    foreach ($page as $key=>$datum)
    {
      $output[] = $datum->toArray($columns, $relations);
    }

    return $output;
  }

  /**
 * Encode array from latin1 to utf8 recursively
 * @param $dat
 * @return array|string
 */
   public static function convert_from_latin1_to_utf8_recursively($dat)
   {
      if (is_string($dat)) {
         return utf8_encode($dat);
      } elseif (is_array($dat)) {
         $ret = [];
         foreach ($dat as $i => $d) $ret[ $i ] = self::convert_from_latin1_to_utf8_recursively($d);

         return $ret;
      } elseif (is_object($dat)) {
         foreach ($dat as $i => $d) $dat->$i = self::convert_from_latin1_to_utf8_recursively($d);

         return $dat;
      } else {
         return $dat;
      }
   }

   /**
    * Helper method to create a JSON error.
    *
    * @param int $errno An error number from json_last_error()
    *
    * @return void
    */
   public static function handleJsonError($errno)
   {
       $messages = array(
           JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
           JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
           JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
           JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
           JSON_ERROR_UTF8 => 'Malformed UTF-8 characters' //PHP >= 5.3.3
       );
       throw new DomainException(
           isset($messages[$errno])
           ? $messages[$errno]
           : 'Unknown JSON error: ' . $errno
       );
   }
}
?>
