<?php
use Phalcon\Cli\Task;
use App\Library\Pluralizer;
use App\Library\StringHelper;
use \GetOpt\GetOpt;
use \GetOpt\Option;
class OmiTask extends BaseTask
{
    const TAB = "    ";
    const NEWLINE = "\n";
    // Models
    const modelNamespace = 'App\\Models';
    const aliasesNamespace = 'App\\Models\\Aliases';

    public function describeAction()
    {
        $config = $this->getDI()->getConfig();
        $defaultSchema = $config->database->dbname;
        // define options
        $this->getOpt->addOptions([
            Option::create('c', 'class', GetOpt::OPTIONAL_ARGUMENT)
                ->setDescription('A class to generate the doc for'),
            Option::create('t', 'table', GetOpt::OPTIONAL_ARGUMENT)
                ->setDescription('A table to generate the doc for'),
            Option::create('s', 'schema', GetOpt::OPTIONAL_ARGUMENT)
                ->setDescription('The schema to generate against')
                ->setDefaultValue($defaultSchema),
            Option::create(null, 'with-relations', GetOpt::NO_ARGUMENT)
                ->setDescription('Generates the core routes file for all models'),
        ]);
        // process options
        $this->processGetOpt();
        // check for required options
        $this->checkRequiredOptions($this->requiredOptions);
        // generate models from the db
        $this->generateDoc();
    }
    private function generateDoc()
    {
        $tab = self::TAB;
        $newline = self::NEWLINE;
        // Model namespace, path and file
        $modelNamespace = self::modelNamespace;
        if ($this->getOpt->getOption('class')) {
            $className = $modelNamespace . '\\' . $this->getOpt->getOption('class');
            $class = new $className();
            $tableName = $class->getSource();
        } else if ($this->getOpt->getOption('table')) {
            $tableName = $this->getOpt->getOption('table');
        }
        $inheritDoc = "{$tab}/**{$newline}{$tab}  * {@inheritDoc}{$newline}{$tab}  */{$newline}";
        $schema = $this->getOpt->getOption('schema');
        $tablesInSchema = "Tables_in_{$schema}";
        $sql = "SHOW TABLES";
        $sql .= " WHERE $tablesInSchema = '".$tableName."'";
        $tablesQuery = $this->db->query($sql);
        $tablesQuery->setFetchMode(
            \Phalcon\Db::FETCH_OBJ
        );
        while ($tableResult = $tablesQuery->fetch())
        {
            $class = StringHelper::toPascalCase($tableResult->$tablesInSchema);
            $text = "";
            // Query for columns
            $tableQuery = $this->db->query("DESCRIBE {$tableResult->$tablesInSchema}");
            $tableQuery->setFetchMode(
                \Phalcon\Db::FETCH_OBJ
            );
            $text .= "{$newline}{$class} {$newline}";
            $attributes = [];
            $padLength = 0;
            // Output all class members
            while ($tableAttributeResult = $tableQuery->fetch())
            {
                $padLength = strlen($tableAttributeResult->Field) > $padLength ? strlen($tableAttributeResult->Field) : $padLength;
                $attributes[$tableAttributeResult->Field] = [
                    'field' => $tableAttributeResult->Field,
                    'type' => $tableAttributeResult->Type,
                ];
            }
            ksort($attributes, SORT_STRING|SORT_FLAG_CASE);
            foreach ($attributes as $attribute) {
                $field = str_pad($attribute['field'], $padLength+4, ' ');
                $text .= "{$tab}{$field} {$attribute['type']}{$newline}";
            }
            if ($this->getOpt->getOption('with-relations')) {
                // Build relations

                $text .= "{$newline}";

                list($relationsText, $hasManyToManyText) = $this->buildRelations($schema, $tableName);

                $text .= $relationsText;
            }
            echo $text;
            return;
        }
        if ($this->getOpt->getOption('with-controllers')) {
            $this->generateControllers();
        }
        if ($this->getOpt->getOption('with-routes')) {
            $this->generateRoutes();
        }
        return;
    }

    private function getHasManyAliasClass($referencedColumn, $referencedClass, $class) {
      $newline          = self::NEWLINE;
      $modelNamespace   = self::modelNamespace;
      $aliasesNamespace = self::aliasesNamespace;

      $classSingular = StringHelper::toCamelCase(Pluralizer::singular($class));
      $referencedClassSingular = Pluralizer::singular($referencedClass);
      $alias = '';

      if ($referencedColumn == "{$classSingular}Id") {
        $alias = $referencedClass;
      }
      else {
        // Strip id from the end
        $str_1 = preg_replace('/(_id|Id)$/','', $referencedColumn);

        // Convert to snake case for split
        $str_2 = StringHelper::toSnakeCase($str_1);

        // Explode to get the last word
        $str_3 = explode('_', $str_2);

        // Get the last index
        $lastIndex = count($str_3) - 1;

        // Pluralize the last word
        $str_3[$lastIndex] = Pluralizer::plural($str_3[$lastIndex]);

        // Implode back to a string
        $str_4 = implode('_', $str_3);

        // Convert to pascal case for usage
        $str_5 = StringHelper::toPascalCase($str_4);

        $alias = $referencedClassSingular . $str_5;
      }

      return $alias;
    }

    private function buildRelations($schema, $tableName) {
      $newline = self::NEWLINE;
      $tab = self::TAB;
      $modelNamespace   = self::modelNamespace;
      $aliasesNamespace = self::aliasesNamespace;
      $class = StringHelper::toPascalCase($tableName);

      $qualifiedClassName = "{$modelNamespace}\\{$class}";
      $object = new $qualifiedClassName();

      $relations = [
          'hasOne' => [],
          'hasMany' => [],
          'hasManyToMany' => [],
      ];

      foreach ($object->getModelsManager()->getRelations($qualifiedClassName) as $relation) {
        if (in_array($relation->getType(), [Phalcon\Mvc\Model\Relation::BELONGS_TO, Phalcon\Mvc\Model\Relation::HAS_ONE])) {
          // print "Has One: " . $relation->getOptions()['alias'] . "{$newline}";
          $relations['hasOne'][$relation->getOptions()['alias']] = $relation->getOptions()['alias'];
        } else if ($relation->getType() == Phalcon\Mvc\Model\Relation::HAS_MANY) {
          // print "Has Many: " . $relation->getOptions()['alias'] . "{$newline}";
          $relations['hasMany'][$relation->getOptions()['alias']] = $relation->getOptions()['alias'];
        } else if ($relation->getType() == Phalcon\Mvc\Model\Relation::HAS_MANY_THROUGH) {
          // print "Has Many To Many: " . $relation->getOptions()['alias'] . "{$newline}";
          $relations['hasManyToMany'][$relation->getOptions()['alias']] = $relation->getOptions()['alias'];
        }
      }

      $text = '';

      // has one
      $text .= "{$class} Has One{$newline}";
      ksort($relations['hasOne']);
      foreach ($relations['hasOne'] as $relationClass) {
          $text .= "{$tab}{$relationClass}{$newline}";
      }

      $text .= "{$newline}";

      // has many
      $text .= "{$class} Has Many{$newline}";
      ksort($relations['hasMany']);
      // echo print_r($relations['hasMany'],true).PHP_EOL;
      foreach ($relations['hasMany'] as $relationClass) {
          $text .= "{$tab}{$relationClass}{$newline}";
      }

      $text .= "{$newline}";

      // has many to many
      $text .= "{$class} Has Many To Many{$newline}";
      ksort($relations['hasManyToMany']);
      foreach ($relations['hasManyToMany'] as $relationClassName => $relationClass) {
          $text .= "{$tab}{$relationClass}{$newline}";
      }

      return [$text, $hasManyToManyText];
    }

    private function buildRelationsForBaseTable($schema, $tableName, $columnName = null) {
      $query = "
        SELECT TABLE_NAME,COLUMN_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE REFERENCED_TABLE_SCHEMA = '{$schema}' AND ";
      if (is_null($columnName)) {
        $query .= "(TABLE_NAME = '{$tableName}' OR REFERENCED_TABLE_NAME = '{$tableName}') ";
      } else {
        $query .= "((TABLE_NAME = '{$tableName}' AND COLUMN_NAME = '{$columnName}') OR (REFERENCED_TABLE_NAME = '{$tableName}' AND REFERENCED_COLUMN_NAME = '{$columnName}')) ";
      }
      $query .= "ORDER BY REFERENCED_TABLE_NAME ASC, COLUMN_NAME ASC;";
      $relationQuery = $this->db->query($query);
      $relationQuery->setFetchMode(
          \Phalcon\Db::FETCH_OBJ
      );
      $relations = [
          'hasOne' => [],
          'hasMany' => [],
          'hasManyToMany' => [],
      ];
      if ($relationQuery->numRows() == 0)
      {
        return $relations;
      }
      while($relationResult = $relationQuery->fetch()) {
          $referencedClass = StringHelper::toPascalCase($relationResult->REFERENCED_TABLE_NAME);
          // Has one
          if ($relationResult->TABLE_NAME == $tableName)
          {
              if (!isset($relations['hasOne'][$referencedClass])) {
                  $relations['hasOne'][$referencedClass] = [];
              }

              $relations['hasOne'][$referencedClass][] = [
                  'column' => $relationResult->COLUMN_NAME,
                  'referenced-class' => $referencedClass,
                  'referenced-column' => $relationResult->REFERENCED_COLUMN_NAME,
              ];
          }
          // Has one or many
          else
          {
              $referencedClass = StringHelper::toPascalCase($relationResult->TABLE_NAME);

              $relations['hasMany'][$referencedClass][] = [
                  'column' => $relationResult->REFERENCED_COLUMN_NAME,
                  'referenced-class' => $referencedClass,
                  'referenced-column' => $relationResult->COLUMN_NAME,
              ];

              $indexQuery1 = $this->db->query("SHOW INDEX FROM {$relationResult->TABLE_NAME} FROM {$schema} WHERE Column_name = '{$relationResult->COLUMN_NAME}'");
              $indexQuery1->setFetchMode(
                  \Phalcon\Db::FETCH_OBJ
              );
              $hasManyToManyRelations = [];
              while ($indexResult1 = $indexQuery1->fetch()) {
                  $indexQuery2 = $this->db->query("SHOW INDEX FROM {$relationResult->TABLE_NAME} FROM {$schema} WHERE Key_name = '{$indexResult1->Key_name}'");
                  $count = $indexQuery2->numRows();
                  if ($count == 2) {
                      $indexQuery3 = $this->db->query(
                          "SHOW INDEX FROM {$relationResult->TABLE_NAME} FROM {$schema} WHERE Key_name = '{$indexResult1->Key_name}' AND Column_name != '{$relationResult->COLUMN_NAME}'"
                      );
                      $indexQuery3->setFetchMode(
                          \Phalcon\Db::FETCH_OBJ
                      );
                      $indexResult3 = $indexQuery3->fetch();
                      $hasManyToManyQuery = $this->db->query("SELECT TABLE_NAME,COLUMN_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '{$schema}' AND (TABLE_NAME = '{$relationResult->TABLE_NAME}' AND COLUMN_NAME = '{$indexResult3->Column_name}') ORDER BY REFERENCED_TABLE_NAME ASC, COLUMN_NAME ASC");
                      $hasManyToManyQuery->setFetchMode(
                          \Phalcon\Db::FETCH_OBJ
                      );

                      while ($hasManyToManyResult = $hasManyToManyQuery->fetch())
                      {
                          $referencedThroughClass = StringHelper::toPascalCase($hasManyToManyResult->REFERENCED_TABLE_NAME);

                          if (!isset($relations['hasManyToMany'][$referencedThroughClass])) {
                            $relations['hasManyToMany'][$referencedThroughClass] = [];
                          }

                          $key = "'{$relationResult->REFERENCED_COLUMN_NAME}', \\{$modelNamespace}\\{$referencedClass}::class, '{$relationResult->COLUMN_NAME}', '{$hasManyToManyResult->COLUMN_NAME}', \\{$modelNamespace}\\{$referencedThroughClass}::class, '{$hasManyToManyResult->REFERENCED_COLUMN_NAME}'";
                          if (!array_key_exists($key, $hasManyToManyRelations)) {
                              $relations['hasManyToMany'][$referencedThroughClass][] = [
                                  'column' => $relationResult->REFERENCED_COLUMN_NAME,
                                  'lookup-class' => $referencedClass,
                                  'lookup-column' => $relationResult->COLUMN_NAME,
                                  'lookup-referenced-column' => $hasManyToManyResult->COLUMN_NAME,
                                  'referenced-class' => $referencedThroughClass,
                                  'referenced-column' => $hasManyToManyResult->REFERENCED_COLUMN_NAME,
                              ];

                              $hasManyToManyRelations[$key] = 1;
                          }
                      }
                  }
              }
          }
      }
      return $relations;
    }

    private function buildRelationsForView($schema, $tableName) {
      $createViewQuery = $this->db->query("SHOW CREATE VIEW {$tableName};");
      $createViewQuery->setFetchMode(
          \Phalcon\Db::FETCH_ASSOC
      );
      $createView = $createViewQuery->fetch();

      $createViewText = $createView['Create View'];
      $pattern = '/^.*?'.$tableResult->$tablesInSchema.'` AS \(?select /';
      $createViewText = preg_replace($pattern, '', $createViewText);
      $pattern = '/AS `.*?`,/';
      $createViewText = preg_replace($pattern, "$1\n", $createViewText);
      $createViewTextArray = explode("\n", $createViewText);
      $createViewTextArrayFiltered = array_filter($createViewTextArray, function($element){return substr($element, 0, 1) == '`';});
      $viewAttributesArray = [];
      foreach ($createViewTextArrayFiltered as $viewAttribute) {
        $viewAttributeSplit = explode('.', $viewAttribute);
        $key = trim($viewAttributeSplit[1], ' `');
        $value = trim($viewAttributeSplit[0], ' `');

        if (!StringHelper::endsWith($key, 'id')) {
          continue;
        }

        $viewAttributesArray[$key] = $value;
      }

      $relations = [
        'hasOne' => [],
        'hasMany' => [],
        'hasManyToMany' => [],
      ];

      foreach ($viewAttributesArray as $column => $table) {
        $relations = array_merge_recursive($relations, $this->buildRelationsForBaseTable($schema, $table, $column));
      }

      return [$relations, $viewAttributesArray];
    }

    private function buildHasOneText($relation, $aliasBy) {
      $newline = self::NEWLINE;
      $tab = self::TAB;

      $modelNamespace = self::modelNamespace;

      if ($aliasBy == 'CLASS') {
          $alias = "{$relation['referenced-class']}";
      } else {
          $columnClassName = StringHelper::toPascalCase(preg_replace('/(_id|Id)$/','', $relation['column']));
          $columnClassNameArray = explode('-', StringHelper::toKebabCase($columnClassName));
          $lastIndex = count($columnClassNameArray) - 1;
          $columnClassNameArray[$lastIndex] = Pluralizer::plural($columnClassNameArray[$lastIndex]);
          $aliasClassName = StringHelper::toPascalCase(implode('-', $columnClassNameArray));
          $alias = "{$aliasClassName}";
      }

      $column = $relation['column'];
      $referencedClass = $relation['referenced-class'];
      $referencedColumn = $relation['referenced-column'];
      $text = "{$tab}{$alias}{$newline}";

      $viewReferencedClass = "\\{$modelNamespace}\\V{$referencedClass}::class";
      if (class_exists($viewReferencedClass)) {
        $viewRelations = $this->modelsManager->getRelations($viewReferencedClass);
        echo print_r($viewRelations,true).PHP_EOL;
      }

      return $text;
    }

    private function buildHasManyText($relation, $aliasBy, $class, $isView, $viewAttributesArray) {
      $newline = self::NEWLINE;
      $tab = self::TAB;

      $modelNamespace = self::modelNamespace;

      $column = $relation['column'];
      $referencedClass = $relation['referenced-class'];
      $referencedColumn = $relation['referenced-column'];

      $text = '';

      if ($aliasBy == 'CLASS') {
        $alias = $referencedClass;
        $viewAlias = "V{$referencedClass}";
      } else {
        if ($isView) {
          $alias = $this->getHasManyAliasClass($referencedColumn, $referencedClass, StringHelper::toPascalCase($viewAttributesArray[$column]));
        } else {
          $alias = $this->getHasManyAliasClass($referencedColumn, $referencedClass, $class);
        }
      }

      $text .= "{$tab}{$alias}{$newline}";

      $viewReferencedClass = "\\{$modelNamespace}\\V{$referencedClass}";
      if (class_exists($viewReferencedClass)) {
        $viewReferencedObject = new $viewReferencedClass();
        $viewRelations = $this->modelsManager->getHasOne($viewReferencedObject);
        foreach ($viewRelations as $viewRelation) {
          if ($viewRelation->getReferencedModel() == "{$modelNamespace}\\{$class}") {
            $text .= "{$tab}{$viewAlias}{$newline}";
            break;
          }
        }
      }

      return $text;
    }
}
