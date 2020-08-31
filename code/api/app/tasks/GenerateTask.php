<?php

// TODO: Recognize when a model has views and create proper relations on the view

use Phalcon\Cli\Task;

use App\Library\Pluralizer;
use App\Library\StringHelper;
use \GetOpt\GetOpt;
use \GetOpt\Option;
use Phalcon\Mvc\Model\Relation;

class GenerateTask extends BaseTask
{
    const TAB = "  ";
    const NEWLINE = "\n";

    const configFile = APP_PATH . '/config/core-routes.php';

    // Models
    const coreModelsNamespace = 'App\\Core\\Models';
    const modelNamespace = 'App\\Models';
    const coreFilePath = APP_PATH . '/core/models/';
    const modelFilePath = APP_PATH . '/models/';

    // Aliases
    const aliasesNamespace = 'App\\Models\\Aliases';
    const aliasesFilePath = APP_PATH . '/models/aliases/';

    // Controllers
    const coreControllerNamespace = 'App\\Core\\Controllers';
    const controllerNamespace = 'App\\Controllers';
    const coreControllerFilePath = APP_PATH . '/core/controllers/';
    const controllerFilePath = APP_PATH . '/controllers/';

    // JSON Schema
    const jsonSchemaPath = APP_PATH . '/json-schema/';

    const coreExtends = 'extends \\App\\Models\\BaseCoreModel';

    public function mainAction()
    {
        $this->echoUsage();
    }

    private function echoUsage() {
      echo 'Usage: task generate [action] [options]' . PHP_EOL;
      echo 'Actions:' . PHP_EOL;
      echo '- models      Generates models.' . PHP_EOL;
      echo '- routes      Generates routes.' . PHP_EOL;
      echo '- controllers Generates controllers.' . PHP_EOL;
      echo '- json        Generates json for dart conversion.' . PHP_EOL;
    }

    public function modelsAction()
    {
        $config = $this->getDI()->getConfig();
        $defaultSchema = $config->database->dbname;

        // define options
        $this->getOpt->addOptions([

            Option::create('t', 'table', GetOpt::OPTIONAL_ARGUMENT)
                ->setDescription('A single table to generate the model for'),

            Option::create('s', 'schema', GetOpt::REQUIRED_ARGUMENT)
                ->setDescription('The schema to generate against')
                ->setDefaultValue($defaultSchema),

            Option::create(null, 'with-routes', GetOpt::NO_ARGUMENT)
                ->setDescription('Generates the core routes file for all models'),

            Option::create(null, 'with-controllers', GetOpt::NO_ARGUMENT)
                ->setDescription('Generates the core controller for the models generated'),

        ]);

        // process options
        $this->processGetOpt();

        // check for required options
        $this->checkRequiredOptions($this->requiredOptions);

        // generate models from the db
        $this->generateModels();
    }

    public function routesAction()
    {
        $this->requiredOptions = ['schema'];

        // define options
        $this->getOpt->addOptions([

            Option::create('s', 'schema', GetOpt::REQUIRED_ARGUMENT)
                ->setDescription('[REQUIRED] The schema to generate against'),

        ]);

        // process options
        $this->processGetOpt();

        // check for required options
        $this->checkRequiredOptions($this->requiredOptions);

        // generate models from the db
        $this->generateRoutes();
    }

    public function controllersAction()
    {
        $this->requiredOptions = ['schema'];

        // define options
        $this->getOpt->addOptions([

            Option::create('t', 'table', GetOpt::OPTIONAL_ARGUMENT)
                ->setDescription('A single table to generate the controller for'),

            Option::create('s', 'schema', GetOpt::REQUIRED_ARGUMENT)
                ->setDescription('[REQUIRED] The schema to generate against'),

            Option::create(null, 'without-get', GetOpt::NO_ARGUMENT)
                ->setDescription('Generates the controllers without a get action'),

            Option::create(null, 'without-post', GetOpt::NO_ARGUMENT)
                ->setDescription('Generates the controllers without a post action'),

            Option::create(null, 'without-put', GetOpt::NO_ARGUMENT)
                ->setDescription('Generates the controllers without a put action'),

            Option::create(null, 'without-patch', GetOpt::NO_ARGUMENT)
                ->setDescription('Generates the controllers without a patch action'),

            Option::create(null, 'without-delete', GetOpt::NO_ARGUMENT)
                ->setDescription('Generates the controllers without a delete action'),
        ]);

        // process options
        $this->processGetOpt();

        // check for required options
        $this->checkRequiredOptions($this->requiredOptions);

        // generate models from the db
        $this->generateControllers();
    }

    public function jsonAction()
    {
      $config = $this->getDI()->getConfig();
      $defaultSchema = $config->database->dbname;

      // define options
      $this->getOpt->addOptions([
          Option::create('c', 'class', GetOpt::OPTIONAL_ARGUMENT)
              ->setDescription('A class to generate the json for'),
          Option::create(null, 'with-relations', GetOpt::NO_ARGUMENT)
              ->setDescription('Generates the json without relational data'),
      ]);
      // process options
      $this->processGetOpt();
      // check for required options
      $this->checkRequiredOptions($this->requiredOptions);
      // generate models from the db
      $this->generateJson($this->getOpt->getOption('class'), $this->getOpt->getOption('with-relations'));
    }

    /**
     *
     */
    private function generateModels()
    {
        $tab = self::TAB;
        $newline = self::NEWLINE;

        // Model namespace, path and file
        $coreNamespace = self::coreModelsNamespace;
        $modelNamespace = self::modelNamespace;
        $aliasesNamespace = self::aliasesNamespace;
        $coreFilePath = self::coreFilePath;
        $modelFilePath = self::modelFilePath;
        $aliasesFilePath = self::aliasesFilePath;

        if (!is_dir($coreFilePath)) {
          mkdir($coreFilePath);
        }

        $coreExtends = self::coreExtends;

        $inheritDoc = "{$tab}/**{$newline}{$tab}  * {@inheritDoc}{$newline}{$tab}  */{$newline}";
        $schema = $this->getOpt->getOption('schema');

        $tablesInSchema = "Tables_in_{$schema}";
        $sql = "SHOW TABLES";

        if ($this->getOpt->getOption('table')) {
            $sql .= " WHERE $tablesInSchema = '".$this->getOpt->getOption('table')."'";
        }

        $tablesQuery = $this->db->query($sql);

        $tablesQuery->setFetchMode(
            \Phalcon\Db::FETCH_OBJ
        );

        while ($tableResult = $tablesQuery->fetch())
        {
            $class = StringHelper::toPascalCase($tableResult->$tablesInSchema);
            $hasManyToManyText = '';
            $text = "<?php{$newline}";
            $text .= "namespace {$coreNamespace};{$newline}{$newline}";

            $text .= "use Phalcon\Mvc\Model\Relation;{$newline}";
            $text .= "use Phalcon\Validation;{$newline}";
            $text .= "use Phalcon\Validation\Validator\InclusionIn;{$newline}";
            $text .= "use Phalcon\Validation\Validator\Email;{$newline}";
            $text .= "use Phalcon\Validation\Validator\PresenceOf;{$newline}";
            $text .= "use Phalcon\Validation\Validator\Digit as DigitValidator;{$newline}";
            $text .= "use Phalcon\Validation\Validator\Numericality;{$newline}";

            $text .= "{$newline}";

            $text .= "{$newline}/**{$newline}  * class {$class}{$newline}  */";
            // Query for columns
            $tableQuery = $this->db->query("DESCRIBE {$tableResult->$tablesInSchema}");
            $tableQuery->setFetchMode(
                \Phalcon\Db::FETCH_OBJ
            );
            $text .= "{$newline}class {$class} {$coreExtends}{$newline}{{$newline}";
            $attributes = [];
            $dateTimeAttributes = [];
            $isCompositeKey = -1;

            // Output all class members
            while ($tableAttributeResult = $tableQuery->fetch())
            {
                $isCompositeKey += $tableAttributeResult->Key == 'PRI' ? 1 : 0;
                $type = 'string';
                $mysqlType = preg_replace("/\(.*$/", '', $tableAttributeResult->Type);
                if (in_array($mysqlType, ['datetime', 'timestamp', 'date']))
                {
                    $type = 'DateTime';
                    $dateTimeAttributes[] = $tableAttributeResult->Field;
                }
                else if (in_array($mysqlType, ['bit', 'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'bool', 'boolean']))
                {
                    $type = 'int';
                }
                else if (in_array($mysqlType, ['decimal', 'dec', 'numeric', 'fixed', 'float', 'double', 'double precision', 'real', 'float', ]))
                {
                    $type = 'float';
                }
                else if (in_array($mysqlType, ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'binary', 'varbinary']))
                {
                    $type = 'string';
                }
                $text .= "{$tab}/**{$newline}{$tab}  * @var ".$type."{$newline}{$tab}  */{$newline}";
                $text .= "{$tab}".'protected $' . $tableAttributeResult->Field . ";{$newline}{$newline}";
                $tableAttributeResult->isEnum = false;
                if ($mysqlType == 'enum')
                {
                    $tableAttributeResult->isEnum = true;
                    $enumValues = preg_replace("/^.*\(/", '', $tableAttributeResult->Type);
                    $enumValues = trim($enumValues, ')');
                    $tableAttributeResult->enumString = $enumValues;
                    $enumValues = explode(',', $enumValues);
                    $text .= "{$tab}/**{$newline}{$tab}  * ".$tableAttributeResult->Field.": ".$tableAttributeResult->Type."{$newline}{$tab}  */{$newline}";
                    foreach($enumValues as $enumValue) {
                        $enumValue = trim($enumValue, "'");
                        $enumName = strtoupper(StringHelper::toSnakeCase($enumValue));
                        $text .= "{$tab} const " . strtoupper(StringHelper::toSnakeCase($tableAttributeResult->Field)) . "_{$enumName} = '{$enumValue}';{$newline}";
                    }
                    $text .= "{$newline}";
                }
                $tableAttributeResult->simpleType = $type;
                $attributes[] = $tableAttributeResult;
            }

            // $text .= "{$tab}/**{$newline}{$tab}  * @var ".$type."{$newline}{$tab}  */{$newline}";
            if (count($dateTimeAttributes) > 0) {
              $text .= "{$tab}"."protected \$dateTimeFields = ['" . implode("','", $dateTimeAttributes) . "'];{$newline}{$newline}";
            }

            // Build relations
            list($relationsText, $hasManyToManyText) = $this->buildRelations($schema, $tableResult->$tablesInSchema);

            $text .= $inheritDoc;
            // Build the initialize method
            $text .= "{$tab}public function initialize(){$newline}";
            $text .= "{$tab}{{$newline}";
            $text .= "{$tab}{$tab}\$this->setSource('{$tableResult->$tablesInSchema}');{$newline}";

            $text .= "{$relationsText}{$newline}";

            $text .= "{$tab}}{$newline}";

            // validation method
            $text .= "{$tab}public function validation(){$newline}";
            $text .= "{$tab}{{$newline}";
            $text .= "{$tab}{$tab}\$validator = new Validation();{$newline}";

            foreach ($attributes as $attribute) {
                if ($attribute->Null == 'NO' && $attribute->Extra != 'auto_increment' && is_null($attribute->Default)) {
                    $text .= "{$tab}{$tab}\$validator->add({$newline}";
                    $text .= "{$tab}{$tab}{$tab}'{$attribute->Field}',{$newline}";
                    $text .= "{$tab}{$tab}{$tab}new PresenceOf([{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}'message' => 'The :field attribute is required',{$newline}";
                    $text .= "{$tab}{$tab}{$tab}]){$newline}";
                    $text .= "{$tab}{$tab});{$newline}";
                }
                if ($attribute->simpleType == 'int') {
                    $text .= "{$tab}{$tab}\$validator->add({$newline}";
                    $text .= "{$tab}{$tab}{$tab}'{$attribute->Field}',{$newline}";
                    $text .= "{$tab}{$tab}{$tab}new DigitValidator([{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}'message' => ':field must be an integer',{$newline}";

                    if ($attribute->Null == 'YES' || !is_null($attribute->Default) || $attribute->Extra == 'auto_increment') {
                        $text .= "{$tab}{$tab}{$tab}{$tab}'allowEmpty' => true,{$newline}";
                    }

                    $text .= "{$tab}{$tab}{$tab}]){$newline}";
                    $text .= "{$tab}{$tab});{$newline}";
                }
                if ($attribute->simpleType == 'float') {
                    $text .= "{$tab}{$tab}\$validator->add({$newline}";
                    $text .= "{$tab}{$tab}{$tab}'{$attribute->Field}',{$newline}";
                    $text .= "{$tab}{$tab}{$tab}new Numericality([{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}'message' => ':field must be numeric',{$newline}";
                    if ($attribute->Null == 'YES' || !is_null($attribute->Default)) {
                        $text .= "{$tab}{$tab}{$tab}{$tab}'allowEmpty' => true,{$newline}";
                    }
                    $text .= "{$tab}{$tab}{$tab}]){$newline}";
                    $text .= "{$tab}{$tab});{$newline}";
                }
                if ($attribute->isEnum) {
                    $message = str_replace(',', ' or ', $attribute->enumString);
                    $message = str_replace('\'', '\\\'', $message);
                    $text .= "{$tab}{$tab}\$validator->add({$newline}";
                    $text .= "{$tab}{$tab}{$tab}'{$attribute->Field}',{$newline}";
                    $text .= "{$tab}{$tab}{$tab}new InclusionIn([{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}'message' => ':field must be {$message}',{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}'domain' => [{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}{$tab}{$attribute->enumString},{$newline}";

                    $text .= "{$tab}{$tab}{$tab}{$tab}],{$newline}";
                    if ($attribute->Null == 'YES' || !is_null($attribute->Default)) {
                        $text .= "{$tab}{$tab}{$tab}{$tab}'allowEmpty' => true,{$newline}";
                    }
                    $text .= "{$tab}{$tab}{$tab}]){$newline}";
                    $text .= "{$tab}{$tab});{$newline}";
                }
            }

            $text .= "{$tab}{$tab}return \$this->validate(\$validator);{$newline}";
            $text .= "{$tab}}{$newline}";

            // getAttributes method
            $text .= "{$newline}{$tab}/**{$newline}";
            $text .= "{$tab}  * Get model attributes{$newline}";
            $text .= "{$tab}  * @return array{$newline}";
            $text .= "{$tab}  */{$newline}";

            $text .= "{$tab}public function getAttributes(){$newline}";
            $text .= "{$tab}{{$newline}";

            $text .= "{$tab}{$tab}\$metaData = \$this->getModelsMetaData();{$newline}";
            $text .= "{$tab}{$tab}return \$metaData->getNonPrimaryKeyAttributes(\$this);{$newline}";
            $text .= "{$tab}}{$newline}";

            // getters and setters
            foreach ($attributes as $attribute) {
                $methodName = StringHelper::toPascalCase($attribute->Field);

                $text .= "{$newline}{$tab}/**{$newline}";
                $text .= "{$tab}  * Gets {$attribute->Field}{$newline}";
                $text .= "{$tab}  * @return {$attribute->simpleType}{$newline}";
                $text .= "{$tab}  */{$newline}";
                $text .= "{$tab}public function get{$methodName}(){$newline}";
                $text .= "{$tab}{{$newline}";
                $text .= "{$tab}{$tab}return \$this->{$attribute->Field};{$newline}";
                $text .= "{$tab}}{$newline}";

                $text .= "{$newline}{$tab}/**{$newline}";
                $text .= "{$tab}  * Sets {$attribute->Field}{$newline}";
                $text .= "{$tab}  * @param \$value{$newline}";
                $text .= "{$tab}  * @return {$class}{$newline}";
                $text .= "{$tab}  */{$newline}";
                $text .= "{$tab}public function set{$methodName}(\$value){$newline}";
                $text .= "{$tab}{{$newline}";
                $text .= "{$tab}{$tab}\$this->{$attribute->Field} = \$value;{$newline}";
                $text .= "{$tab}{$tab}return \$this;{$newline}";
                $text .= "{$tab}}{$newline}";
            }

            // closing brace
            $text .= "{$newline}}{$newline}";

            $file = fopen("{$coreFilePath}{$class}.php", "w");
            fwrite($file, $text);
            fclose($file);

            // User editable model file
            if (!file_exists("{$modelFilePath}{$class}.php")) {
                $text = "<?php{$newline}";
                $text .= "namespace {$modelNamespace};{$newline}{$newline}";

                $text .= "{$newline}use {$coreNamespace}\\{$class} as Core{$class};";

                $text .= "{$newline}/**{$newline}  * class {$class}{$newline}  */";

                $text .= "{$newline}class {$class} extends Core{$class}{$newline}{{$newline}";

                $text .= "{$tab}public function initialize(){$newline}";
                $text .= "{$tab}{{$newline}";
                $text .= "{$tab}{$tab}parent::initialize();{$newline}";
                $text .= $hasManyToManyText;
                $text .= "{$tab}}{$newline}";

                $text .= "}{$newline}";

                $file = fopen("{$modelFilePath}{$class}.php", "w");
                fwrite($file, $text);
                fclose($file);
            }
        }

        if ($this->getOpt->getOption('with-controllers')) {
            $this->generateControllers();
        }

        if ($this->getOpt->getOption('with-routes')) {
            $this->generateRoutes();
        }

        return;
    }

    private function generateControllers()
    {
        $tab = self::TAB;
        $newline = self::NEWLINE;

        // Model namespace
        $modelNamespace = self::modelNamespace;

        // Controller namespace, path and file
        $coreControllerNamespace = self::coreControllerNamespace;
        $controllerNamespace = self::controllerNamespace;
        $coreControllerFilePath = self::coreControllerFilePath;
        $controllerFilePath = self::controllerFilePath;

        $schema = $this->getOpt->getOption('schema');

        $tablesInSchema = "Tables_in_{$schema}";
        $sql = "SHOW TABLES";

        if ($this->getOpt->getOption('table')) {
            $sql .= " WHERE $tablesInSchema = '".$this->getOpt->getOption('table')."'";
        }

        $tablesQuery = $this->db->query($sql);

        $tablesQuery->setFetchMode(
            \Phalcon\Db::FETCH_OBJ
        );

        while ($tableResult = $tablesQuery->fetch())
        {
            $class = StringHelper::toPascalCase($tableResult->$tablesInSchema);
            $controllerClass = $class.'Controller';

            // Query for columns
            $tableQuery = $this->db->query("DESCRIBE {$tableResult->$tablesInSchema}");
            $tableQuery->setFetchMode(
                \Phalcon\Db::FETCH_OBJ
            );

            $attributes = [];
            $isCompositeKey = -1;
            // Output all class members
            while ($tableAttributeResult = $tableQuery->fetch())
            {
                $isCompositeKey += $tableAttributeResult->Key == 'PRI' ? 1 : 0;
                if ($isCompositeKey) {
                    break;
                }
                $type = 'string';
                $mysqlType = preg_replace("/\(.*$/", '', $tableAttributeResult->Type);
                if (in_array($mysqlType, ['datetime', 'timestamp', 'date']))
                {
                    $type = 'DateTime';
                }
                else if (in_array($mysqlType, ['bit', 'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'bool', 'boolean']))
                {
                    $type = 'int';
                }
                else if (in_array($mysqlType, ['decimal', 'dec', 'numeric', 'fixed', 'float', 'double', 'double precision', 'real', 'float', ]))
                {
                    $type = 'float';
                }
                else if (in_array($mysqlType, ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'binary', 'varbinary']))
                {
                    $type = 'string';
                }
                $tableAttributeResult->isEnum = false;
                if ($mysqlType == 'enum')
                {
                    $tableAttributeResult->isEnum = true;
                    $enumValues = preg_replace("/^.*\(/", '', $tableAttributeResult->Type);
                    $enumValues = trim($enumValues, ')');
                    $tableAttributeResult->enumString = $enumValues;
                }
                $tableAttributeResult->simpleType = $type;
                $attributes[] = $tableAttributeResult;
            }

            if (!$isCompositeKey) {
                // Core controller
                $text = "<?php{$newline}";
                $text .= "namespace {$coreControllerNamespace};{$newline}{$newline}";

                $text .= "use {$controllerNamespace}\\BaseCoreController;{$newline}";
                $text .= "use App\\Exceptions\\AppException;{$newline}";
                $text .= "use App\\Library\\StringHelper;{$newline}";
                $text .= "use {$modelNamespace}\\{$class};{$newline}";
                $text .= "{$newline}";

                $text .= "{$newline}/**{$newline}  * class {$controllerClass}{$newline}  */";

                $text .= "{$newline}class {$controllerClass} extends BaseCoreController{$newline}{{$newline}";

                // GET
                if (!$this->getOpt->getOption('without-get')) {
                    $text .= "{$newline}{$tab}/**{$newline}";
                    $text .= "{$tab}  * Gets {$class}{$newline}";
                    $text .= "{$tab}  * @return {$class}|Array<{$class}>{$newline}";
                    $text .= "{$tab}  */{$newline}";
                    $text .= "{$tab}public function get(){$newline}";
                    $text .= "{$tab}{{$newline}";
                    $text .= "{$tab}{$tab}\$builder = \$this->modelsManager->createBuilder()->from('{$modelNamespace}\\{$class}');{$newline}";
                    $text .= "{$tab}{$tab}return {$class}::getBuilder(\$builder);{$newline}";
                    $text .= "{$tab}}{$newline}";
                }

                // POST
                if (!$this->getOpt->getOption('without-post')) {
                    $text .= "{$newline}{$tab}/**{$newline}";
                    $text .= "{$tab}  * Creates a {$class}{$newline}";
                    $text .= "{$tab}  * @return {$class}{$newline}";
                    $text .= "{$tab}  */{$newline}";
                    $text .= "{$tab}public function post(){$newline}";
                    $text .= "{$tab}{{$newline}";
                    $text .= "{$tab}{$tab}\$object = new {$class}();{$newline}";
                    $text .= "{$tab}{$tab}\$attributes = \$object->getAttributes();{$newline}";
                    $text .= "{$tab}{$tab}// Remove any blacklisted columns{$newline}";
                    $text .= "{$tab}{$tab}if (isset(\$GLOBALS['OMA_COLUMN_BLACKLIST']['{$class}'])) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$columnIntersection = array_intersect(\$attributes, \$GLOBALS['OMA_COLUMN_BLACKLIST']['{$class}']);{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$attributes = array_diff(\$attributes, \$columnIntersection);{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}foreach (\$attributes as \$attribute) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$methodName = 'set'.StringHelper::toPascalCase(\$attribute);{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$object->\$methodName(\$this->getInput(\$attribute));{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}if(\$object->save() == false) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$error = '';{$newline}";
                    $text .= "{$tab}{$tab}{$tab}foreach (\$object->getMessages() as \$message) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}\$error .= \$message.\"\\n\";{$newline}";
                    $text .= "{$tab}{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}{$tab}throw new AppException( AppException::EMSG_POST, \$error );{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}\$object->refresh();{$newline}";
                    $text .= "{$tab}{$tab}return \$object;{$newline}";
                    $text .= "{$tab}}{$newline}";
                }

                // PUT
                if (!$this->getOpt->getOption('without-put')) {
                    $text .= "{$newline}{$tab}/**{$newline}";
                    $text .= "{$tab}  * Updates a {$class}{$newline}";
                    $text .= "{$tab}  * @param \$id{$newline}";
                    $text .= "{$tab}  * @return {$class}{$newline}";
                    $text .= "{$tab}  */{$newline}";
                    $text .= "{$tab}public function put(\$id){$newline}";
                    $text .= "{$tab}{{$newline}";
                    $text .= "{$tab}{$tab}\$object = {$class}::findFirst(\$id);{$newline}";
                    $text .= "{$tab}{$tab}if (!\$object) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}throw new AppException( AppException::EMSG_PUT);{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}{$newline}";
                    $text .= "{$tab}{$tab}\$attributes = \$object->getAttributes();{$newline}";
                    $text .= "{$tab}{$tab}// Remove any blacklisted columns{$newline}";
                    $text .= "{$tab}{$tab}if (isset(\$GLOBALS['OMA_COLUMN_BLACKLIST']['{$class}'])) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$columnIntersection = array_intersect(\$attributes, \$GLOBALS['OMA_COLUMN_BLACKLIST']['{$class}']);{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$attributes = array_diff(\$attributes, \$columnIntersection);{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}foreach (\$attributes as \$attribute) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$methodName = 'set'.StringHelper::toPascalCase(\$attribute);{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$object->\$methodName(\$this->getInput(\$attribute));{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}if(\$object->save() == false) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$error = '';{$newline}";
                    $text .= "{$tab}{$tab}{$tab}foreach (\$object->getMessages() as \$message) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}\$error .= \$message.\"\\n\";{$newline}";
                    $text .= "{$tab}{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}{$tab}throw new AppException( AppException::EMSG_PUT, \$error );{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}\$object->refresh();{$newline}";
                    $text .= "{$tab}{$tab}return \$object;{$newline}";
                    $text .= "{$tab}}{$newline}";
                }

                // PATCH
                if (!$this->getOpt->getOption('without-patch')) {
                    $text .= "{$newline}{$tab}/**{$newline}";
                    $text .= "{$tab}  * Updates a {$class}{$newline}";
                    $text .= "{$tab}  * @param \$id{$newline}";
                    $text .= "{$tab}  * @return {$class}{$newline}";
                    $text .= "{$tab}  */{$newline}";
                    $text .= "{$tab}public function patch(\$id){$newline}";
                    $text .= "{$tab}{{$newline}";
                    $text .= "{$tab}{$tab}\$object = {$class}::findFirst(\$id);{$newline}";
                    $text .= "{$tab}{$tab}if (!\$object) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}throw new AppException( AppException::EMSG_PATCH);{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}{$newline}";
                    $text .= "{$tab}{$tab}\$attributes = \$object->getAttributes();{$newline}";
                    $text .= "{$tab}{$tab}// Remove any blacklisted columns{$newline}";
                    $text .= "{$tab}{$tab}if (isset(\$GLOBALS['OMA_COLUMN_BLACKLIST']['{$class}'])) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$columnIntersection = array_intersect(\$attributes, \$GLOBALS['OMA_COLUMN_BLACKLIST']['{$class}']);{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$attributes = array_diff(\$attributes, \$columnIntersection);{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}foreach (\$attributes as \$attribute) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}if (\$this->getInput(\$attribute) == null) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}continue;{$newline}";
                    $text .= "{$tab}{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$methodName = 'set'.StringHelper::toPascalCase(\$attribute);{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$object->\$methodName(\$this->getInput(\$attribute));{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}if(\$object->save() == false) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$error = '';{$newline}";
                    $text .= "{$tab}{$tab}{$tab}foreach (\$object->getMessages() as \$message) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}\$error .= \$message.\"\\n\";{$newline}";
                    $text .= "{$tab}{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}{$tab}throw new AppException( AppException::EMSG_PATCH, \$error );{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}\$object->refresh();{$newline}";
                    $text .= "{$tab}{$tab}return \$object;{$newline}";
                    $text .= "{$tab}}{$newline}";
                }

                // DELETE
                if (!$this->getOpt->getOption('without-delete')) {
                    $text .= "{$newline}{$tab}/**{$newline}";
                    $text .= "{$tab}  * Deletes a {$class}{$newline}";
                    $text .= "{$tab}  * @param \$id{$newline}";
                    $text .= "{$tab}  * @return null{$newline}";
                    $text .= "{$tab}  */{$newline}";
                    $text .= "{$tab}public function delete(\$id){$newline}";
                    $text .= "{$tab}{{$newline}";
                    $text .= "{$tab}{$tab}\$object = {$class}::findFirst(\$id);{$newline}";
                    $text .= "{$tab}{$tab}if (!\$object) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}throw new AppException( AppException::EMSG_DELETE);{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}{$newline}";
                    $text .= "{$tab}{$tab}if(\$object->delete() == false) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}\$error = '';{$newline}";
                    $text .= "{$tab}{$tab}{$tab}foreach (\$object->getMessages() as \$message) {{$newline}";
                    $text .= "{$tab}{$tab}{$tab}{$tab}\$error .= \$message.\"\\n\";{$newline}";
                    $text .= "{$tab}{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}{$tab}throw new AppException( AppException::EMSG_DELETE, \$error );{$newline}";
                    $text .= "{$tab}{$tab}}{$newline}";
                    $text .= "{$tab}{$tab}return null;{$newline}";
                    $text .= "{$tab}}{$newline}";
                }

                $text .= "}{$newline}";

                $file = fopen("{$coreControllerFilePath}{$controllerClass}.php", "w");
                fwrite($file, $text);
                fclose($file);

                // User editable controller file
                if (!file_exists("{$controllerFilePath}{$controllerClass}.php")) {
                    $text = "<?php{$newline}";
                    $text .= "namespace {$controllerNamespace};{$newline}{$newline}";

                    $text .= "{$newline}use {$coreControllerNamespace}\\{$controllerClass} as Core{$controllerClass};";

                    $text .= "{$newline}/**{$newline}  * class {$controllerClass}{$newline}  */";

                    $text .= "{$newline}class {$controllerClass} extends Core{$controllerClass}{$newline}{{$newline}";
                    $text .= "}{$newline}";

                    $file = fopen("{$controllerFilePath}{$controllerClass}.php", "w");
                    fwrite($file, $text);
                    fclose($file);
                }
            }
        }

        return;
    }

    private function generateRoutes()
    {
        $newline = self::NEWLINE;

        $configFile = self::configFile;
        $controllerNamespace = self::controllerNamespace;
        $schema = $this->getOpt->getOption('schema');

        $tablesInSchema = "Tables_in_{$schema}";
        $sql = "SHOW TABLES";

        $tablesQuery = $this->db->query($sql);

        $tablesQuery->setFetchMode(
            \Phalcon\Db::FETCH_OBJ
        );

        $text = "<?php{$newline}";
        $text .= "use Phalcon\\Mvc\\Micro\\Collection;{$newline}";

        while ($tableResult = $tablesQuery->fetch())
        {
            $class = StringHelper::toPascalCase($tableResult->$tablesInSchema);
            $controllerClass = $class.'Controller';

            // Query for columns
            $tableQuery = $this->db->query("DESCRIBE {$tableResult->$tablesInSchema}");
            $tableQuery->setFetchMode(
                \Phalcon\Db::FETCH_OBJ
            );

            $attributes = [];
            $isCompositeKey = -1;
            // Output all class members
            while ($tableAttributeResult = $tableQuery->fetch())
            {
                $isCompositeKey += $tableAttributeResult->Key == 'PRI' ? 1 : 0;
                if ($isCompositeKey) {
                    break;
                }
                $type = 'string';
                $mysqlType = preg_replace("/\(.*$/", '', $tableAttributeResult->Type);
                if (in_array($mysqlType, ['datetime', 'timestamp', 'date']))
                {
                    $type = 'DateTime';
                }
                else if (in_array($mysqlType, ['bit', 'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'bool', 'boolean']))
                {
                    $type = 'int';
                }
                else if (in_array($mysqlType, ['decimal', 'dec', 'numeric', 'fixed', 'float', 'double', 'double precision', 'real', 'float', ]))
                {
                    $type = 'float';
                }
                else if (in_array($mysqlType, ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'binary', 'varbinary']))
                {
                    $type = 'string';
                }
                $tableAttributeResult->isEnum = false;
                if ($mysqlType == 'enum')
                {
                    $tableAttributeResult->isEnum = true;
                    $enumValues = preg_replace("/^.*\(/", '', $tableAttributeResult->Type);
                    $enumValues = trim($enumValues, ')');
                    $tableAttributeResult->enumString = $enumValues;
                }
                $tableAttributeResult->simpleType = $type;
                $attributes[] = $tableAttributeResult;
            }


            if (!$isCompositeKey) {
                $uri = StringHelper::toKebabCase($class);
                $text.= "{$newline}";
                $text .= "\$controller = new Collection();{$newline}";
                $text .= "\$controller->setHandler('\\{$controllerNamespace}\\{$controllerClass}', true);{$newline}";
                $text .= "\$controller->setPrefix('/{$uri}');{$newline}";
                $text .= "\$controller->get('', 'get');{$newline}";
                $text .= "\$controller->post('', 'post');{$newline}";
                $text .= "\$controller->put('/{id:[0-9]+}', 'put');{$newline}";
                $text .= "\$controller->patch('/{id:[0-9]+}', 'patch');{$newline}";
                $text .= "\$controller->delete('/{id:[0-9]+}', 'delete');{$newline}";
                $text .= "\$app->mount(\$controller);{$newline}";

            }
        }

        $file = fopen("{$configFile}", "w");
        fwrite($file, $text);
        fclose($file);

        return;
    }

    private function generateJson($class, $withRelations = false, &$visitedClasses = [])
    {
        $modelNamespace = self::modelNamespace;
        $jsonSchemaPath = self::jsonSchemaPath;
        if (!is_dir($jsonSchemaPath)) {
            mkdir($jsonSchemaPath);
        }
        $tableName = null;
        $className = $modelNamespace . '\\' . $class;
        $class = new $className();
        $tableName = $class->getSource();

        $schema = $class->getDi()->getConfig()->database->dbname;
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
            $json = [
                'type' => 'object',
                'title' => $class,
                'properties' => [],
            ];
            // Query for columns
            $tableQuery = $this->db->query("DESCRIBE {$tableResult->$tablesInSchema}");
            $tableQuery->setFetchMode(
                \Phalcon\Db::FETCH_OBJ
            );
            // Output all class members
            while ($tableAttributeResult = $tableQuery->fetch())
            {
                $attribute = [
                    'type' => 'string'
                ];
                $mysqlType = preg_replace("/\(.*$/", '', $tableAttributeResult->Type);
                if (in_array($mysqlType, ['datetime', 'timestamp', 'date']))
                {
                    $attribute['format'] = 'date-time';
                }
                else if (in_array($mysqlType, ['bit', 'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'bool', 'boolean']))
                {
                    $attribute['type'] = 'integer';
                }
                else if (in_array($mysqlType, ['decimal', 'dec', 'numeric', 'fixed', 'float', 'double', 'double precision', 'real']))
                {
                    $attribute['type'] = 'number';
                }
                if ($mysqlType == 'enum')
                {
                    $tableAttributeResult->isEnum = true;
                    $enumValues = preg_replace("/^.*\('/", '', $tableAttributeResult->Type);
                    $enumValues = trim($enumValues, "')");
                    $enumValues = explode("','", $enumValues);
                    $attribute['enum'] = $enumValues;
                }
                $json['properties'][$tableAttributeResult->Field] = $attribute;
            }

            // Build relations
            if ($withRelations) {
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

              // has one
              ksort($relations['hasOne']);
              foreach ($relations['hasOne'] as $relationClass) {
                $json['properties'][$relationClass] = [
                    'type' => 'object',
                    'title' => $relationClass,
                    'properties' => [
                        'foo' => ['type' => 'string']
                    ]
                ];
              }
              // has many
              ksort($relations['hasMany']);
              foreach ($relations['hasMany'] as $relationClass) {
                $json['properties'][$relationClass] = [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'title' => $relationClass,
                        'properties' => [
                            'foo' => ['type' => 'string']
                        ]
                    ]
                ];
              }
              // has many to many
              ksort($relations['hasManyToMany']);
              foreach ($relations['hasManyToMany'] as $relationClass) {
                $json['properties'][$relationClass] = [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'title' => $relationClass,
                        'properties' => [
                            'foo' => ['type' => 'string']
                        ]
                    ]
                ];
              }
            }

            $file = fopen("{$jsonSchemaPath}" . DIRECTORY_SEPARATOR . "{$class}.json", "w");
            fwrite($file, json_encode($json));
            fclose($file);
        }
        return $json;
    }

    private function getHasManyAliasClass($referencedColumn, $referencedClass, $class) {
      $newline          = self::NEWLINE;
      $modelNamespace   = self::modelNamespace;
      $aliasesNamespace = self::aliasesNamespace;
      $aliasesFilePath  = self::aliasesFilePath;

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

      $fullyQualifiedAliasedClassName = "{$aliasesNamespace}\\{$alias}";

      if (!class_exists($fullyQualifiedAliasedClassName, false)) {
        $aliasText =  "<?php{$newline}";
        $aliasText .= "namespace {$aliasesNamespace};{$newline}{$newline}";
        $aliasText .= "class {$alias} extends {$class} {}{$newline}";
        $file = fopen("{$aliasesFilePath}{$alias}.php", "w");
        fwrite($file, $aliasText);
        fclose($file);
      }

      return $alias;
    }

    private function buildRelations($schema, $tableName) {
      $newline = self::NEWLINE;
      $tab = self::TAB;
      $modelNamespace   = self::modelNamespace;
      $aliasesNamespace = self::aliasesNamespace;
      $class = StringHelper::toPascalCase($tableName);

      $informationSchemaTablesQuery = $this->db->query("SELECT TABLE_TYPE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$schema}' AND TABLE_NAME = '{$tableName}';");
      $informationSchemaTablesQuery->setFetchMode(
          \Phalcon\Db::FETCH_OBJ
      );
      $informationSchemaTables = $informationSchemaTablesQuery->fetch();
      $isView = false;
      if ($informationSchemaTables->TABLE_TYPE == 'BASE TABLE') {
        $relations = $this->buildRelationsForBaseTable($schema, $tableName);
      } else if ($informationSchemaTables->TABLE_TYPE == 'VIEW') {
        $isView = true;
        list($relations, $viewAttributesArray) = $this->buildRelationsForView($schema, $tableName);
      }

      $text = '';

      // has one
      ksort($relations['hasOne']);
      foreach ($relations['hasOne'] as $relationClass) {
          $aliasBy = 'CLASS';
          if (count($relationClass) > 1) {
              $aliasBy = 'COLUMN';
          }
          foreach ($relationClass as $relation) {
            $text .= $this->buildHasOneText($relation, $aliasBy);
          }

      }

      // has many
      ksort($relations['hasMany']);
      // echo print_r($relations['hasMany'],true).PHP_EOL;
      foreach ($relations['hasMany'] as $relationClass) {
          $aliasBy = 'CLASS';
          if (count($relationClass) > 1) {
              $aliasBy = 'COLUMN';
          }
          foreach ($relationClass as $relation) {
            $text .= $this->buildHasManyText($relation, $aliasBy, $class, $isView, $viewAttributesArray);
          }

      }

      // has many to many
      ksort($relations['hasManyToMany']);
      foreach ($relations['hasManyToMany'] as $relationClassName => $relationClass) {
          $aliasBy = 'CLASS';
          if (count($relationClass) > 1) {
              $aliasBy = 'COLUMN';
          }
          foreach ($relationClass as $relation) {
              $column = $relation['column'];
              $lookupClass = $relation['lookup-class'];
              $lookupColumn = $relation['lookup-column'];
              $lookupReferencedColumn = $relation['lookup-referenced-column'];
              $referencedClass = $relation['referenced-class'];
              $referencedColumn = $relation['referenced-column'];

              if ($aliasBy == 'CLASS') {
                  $alias = ", [{$newline}{$tab}{$tab}{$tab}{$tab}'alias' => '{$referencedClass}'{$newline}{$tab}{$tab}{$tab}]";
                  $text .= "{$tab}{$tab}\$this->hasManyToMany({$newline}";
                  $text .= "{$tab}{$tab}{$tab}'{$column}',{$newline}";
                  $text .= "{$tab}{$tab}{$tab}\\{$modelNamespace}\\{$lookupClass}::class,{$newline}";
                  $text .= "{$tab}{$tab}{$tab}'{$lookupColumn}', '{$lookupReferencedColumn}',{$newline}";
                  $text .= "{$tab}{$tab}{$tab}\\{$modelNamespace}\\{$referencedClass}::class,{$newline}";
                  $text .= "{$tab}{$tab}{$tab}'{$referencedColumn}'{$alias}{$newline}";
                  $text .= "{$tab}{$tab});{$newline}";
              } else {
                  $columnClassName = StringHelper::toPascalCase(preg_replace('/(_id|Id)$/','', $lookupColumn));
                  // $alias = ", [{$newline}{$tab}{$tab}{$tab}//'alias' => 'SET_A_GOOD_ALIAS'{$newline}//{$tab}{$tab}{$tab}]";
                  $hasManyToManyText .= "{$newline}";
                  $hasManyToManyText .= "{$tab}{$tab}//\$this->hasManyToMany({$newline}";
                  $hasManyToManyText .= "{$tab}{$tab}{$tab}//'{$column}',{$newline}";
                  $hasManyToManyText .= "{$tab}{$tab}{$tab}//\\{$modelNamespace}\\{$lookupClass}::class,{$newline}";
                  $hasManyToManyText .= "{$tab}{$tab}{$tab}//'{$lookupColumn}', '{$lookupReferencedColumn}',{$newline}";
                  $hasManyToManyText .= "{$tab}{$tab}{$tab}//\\{$modelNamespace}\\{$referencedClass}::class,{$newline}";
                  $hasManyToManyText .= "{$tab}{$tab}{$tab}//'{$referencedColumn}', [{$newline}";
                  $hasManyToManyText .= "{$tab}{$tab}{$tab}//'alias' => 'SET_A_GOOD_ALIAS'{$newline}";
                  $hasManyToManyText .= "{$tab}{$tab}{$tab}//]{$newline}";
                  $hasManyToManyText .= "{$tab}{$tab}//);{$newline}";
              }
          }

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
          $alias = ", [{$newline}{$tab}{$tab}{$tab}'alias' => '{$relation['referenced-class']}'{$newline}{$tab}{$tab}]";
          $viewAlias = ", [{$newline}{$tab}{$tab}{$tab}'alias' => 'V{$relation['referenced-class']}'{$newline}{$tab}{$tab}]";
      } else {
          $columnClassName = StringHelper::toPascalCase(preg_replace('/(_id|Id)$/','', $relation['column']));
          $columnClassNameArray = explode('-', StringHelper::toKebabCase($columnClassName));
          $lastIndex = count($columnClassNameArray) - 1;
          $columnClassNameArray[$lastIndex] = Pluralizer::plural($columnClassNameArray[$lastIndex]);
          $aliasClassName = StringHelper::toPascalCase(implode('-', $columnClassNameArray));
          $alias = ", [{$newline}{$tab}{$tab}{$tab}'alias' => '{$aliasClassName}'{$newline}{$tab}{$tab}]";
      }

      $column = $relation['column'];
      $referencedClass = $relation['referenced-class'];
      $referencedColumn = $relation['referenced-column'];
      $text = "{$tab}{$tab}\$this->hasOne('{$column}', \\{$modelNamespace}\\{$referencedClass}::class, '{$referencedColumn}'{$alias});{$newline}";

      $viewReferencedClass = "\\{$modelNamespace}\\V{$referencedClass}";
      if (class_exists($viewReferencedClass)) {
        print "CLASS ESISTS".PHP_EOL;
        $text .= "{$tab}{$tab}\$this->hasOne('{$column}', {$viewReferencedClass}::class, '{$referencedColumn}'{$viewAlias});{$newline}";
      }
      else {
        print 'NOOOPE'.PHP_EOL;
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
      $extraTemplate = ", [{$newline}";

      if (!$isView) {
        $extraTemplate .= "{$tab}{$tab}{$tab}{$tab}'foreignKey' => [{$newline}";
        $extraTemplate .= "{$tab}{$tab}{$tab}{$tab}{$tab}'action' => Relation::ACTION_CASCADE,{$newline}";
        $extraTemplate .= "{$tab}{$tab}{$tab}{$tab}],{$newline}";
      }
      $extraTemplate .= "{$tab}{$tab}{$tab}{$tab}'alias' => ':ALIAS:',{$newline}";
      $extraTemplate .= "{$tab}{$tab}{$tab}]";

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

      $extra = str_replace(':ALIAS:', $alias, $extraTemplate);

      $text .= "{$tab}{$tab}\$this->hasMany({$newline}";
      $text .= "{$tab}{$tab}{$tab}'{$column}',{$newline}";
      $text .= "{$tab}{$tab}{$tab}\\{$modelNamespace}\\{$referencedClass}::class,{$newline}";
      $text .= "{$tab}{$tab}{$tab}'{$referencedColumn}'{$extra}{$newline}";
      $text .= "{$tab}{$tab});{$newline}";

      $viewReferencedClass = "\\{$modelNamespace}\\V{$referencedClass}";
      if (class_exists($viewReferencedClass)) {
        $viewReferencedObject = new $viewReferencedClass();
        $viewRelations = $this->modelsManager->getHasOne($viewReferencedObject);
        foreach ($viewRelations as $viewRelation) {
          if ($viewRelation->getReferencedModel() == "{$modelNamespace}\\{$class}") {
            $extra = str_replace(':ALIAS:', $viewAlias, $extraTemplate);
            $text .= "{$tab}{$tab}\$this->hasMany({$newline}";
            $text .= "{$tab}{$tab}{$tab}'{$column}',{$newline}";
            $text .= "{$tab}{$tab}{$tab}{$viewReferencedClass}::class,{$newline}";
            $text .= "{$tab}{$tab}{$tab}'{$referencedColumn}'{$extra}{$newline}";
            $text .= "{$tab}{$tab});{$newline}";
            break;
          }
        }
      }

      return $text;
    }
}
