<?php

use Phalcon\Cli\Task;

class DatabaseTask extends Task
{
  const OPTIONS = [
    '-d'             => '--database',
    '--database'     => '--database',
    '-m'             => '--model',
    '--model'        => '--model',
    '--with-history' => '--with-history',
  ];
  private $runOptions = [];

  public function initialize()
  {

  }

  public function mainAction()
  {
    echo 'This is the default task and the default action' . PHP_EOL;
  }

  public function runAction(array $params) {
    $this->_database = 'db';
    $action = 'echoUsage';
    $parameterCount = count($params);
    for ($index = 0; $index < $parameterCount; $index++) {
      $param = $params[$index];

      // Set the action
      if ($param == 'init') {
        $action = 'initializeDB';
      }
      else if ($param == 'update') {
        $action = 'updateDB';
      }
      else if ($param == 'update-history') {
        $action = 'updateHistoryDatabase';
      }
      else if ($param == 'usage' || $param == 'help') {
        $action = 'echoUsage';
      }

      // Check options
      if (substr($param, 0, 1) === '-') {
        $nextIndex = $index + 1;
        $value = '';
        if ($index + 1 < $parameterCount) {
          for ($subIndex = $index + 1; $subIndex < $parameterCount; $subIndex++) {
            if (substr($params[$subIndex], 0, 1) === '-') {
              $index = $subIndex - 1;
              break;
            }
            $value .= $params[$subIndex];
          }
        }
        // Skip invalid options
        if (!array_key_exists($param, self::OPTIONS)) {
          continue;
        }
        $this->runOptions[self::OPTIONS[$param]] = $value;
      }
    }

    $this->$action();
  }

  private function echoUsage() {
    echo 'Usage: php app/cli.php database run action1 action2' . PHP_EOL;
    echo 'Actions:' . PHP_EOL;
    echo '- init              Initializes the database by importing the schema and running all revisions.' . PHP_EOL;
    echo '- update            Updates the database to the most recent revision.' . PHP_EOL;
    $this->updateHistoryDatabase();
  }

  private function initializeDB() {
    if (array_key_exists('--database', $this->runOptions)) {
      $this->_database = $this->runOptions['--database'];
    }
    $_database       = $this->_database;
    $dbvDataDir      = APP_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'dbv' . DIRECTORY_SEPARATOR . 'data';
    $dbvSchemaDir    = $dbvDataDir . DIRECTORY_SEPARATOR . 'schema';
    $dbvRevisionsDir = $dbvDataDir . DIRECTORY_SEPARATOR . 'revisions';
    $dbvMetaDir      = $dbvDataDir . DIRECTORY_SEPARATOR . 'meta';
    $dbvRevisionFile = $dbvMetaDir . DIRECTORY_SEPARATOR . 'revision';

    $currentRevision = null;
    if (is_file($dbvRevisionFile)) {
      $currentRevision = trim(file_get_contents($dbvRevisionFile));
    }
    $maxRevision = $this->getMaxRevision($dbvRevisionsDir);

    if (!is_null($currentRevision)) {
      echo 'Database has already been initialized.' . PHP_EOL;
      return;
    }

    // Load schema
    if (is_null($currentRevision)) {
      $this->$_database->query("SET FOREIGN_KEY_CHECKS=0");
      $schemaFiles = scandir($dbvSchemaDir);
      while (count($schemaFiles) > 0) {
        $schemaFiles = $this->loadSchema($dbvSchemaDir, $schemaFiles);
      }
      $this->$_database->query("SET FOREIGN_KEY_CHECKS=1");

      // Set the current revision to 0
      $currentRevision = 0;
    }

    // Run revisions
    $revision = $this->runRevisions($dbvRevisionsDir, $currentRevision);

    // Update revision
    file_put_contents($dbvMetaDir . DIRECTORY_SEPARATOR . 'revision', $revision);

    // Initialize history tables
    if (array_key_exists('--with-history', $this->runOptions)) {
      $this->updateHistoryDatabase();
    }

    echo 'Database initialized.' . PHP_EOL;
    return;
  }

  private function updateDB() {
    if (array_key_exists('--database', $this->runOptions)) {
      $this->_database = $this->runOptions['--database'];
    }
    $_database       = $this->_database;
    $dbvDataDir      = APP_PATH . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'dbv' . DIRECTORY_SEPARATOR . 'data';
    $dbvSchemaDir    = $dbvDataDir . DIRECTORY_SEPARATOR . 'schema';
    $dbvRevisionsDir = $dbvDataDir . DIRECTORY_SEPARATOR . 'revisions';
    $dbvMetaDir      = $dbvDataDir . DIRECTORY_SEPARATOR . 'meta';
    $dbvRevisionFile = $dbvMetaDir . DIRECTORY_SEPARATOR . 'revision';

    $currentRevision = null;
    if (is_file($dbvRevisionFile)) {
      $currentRevision = trim(file_get_contents($dbvRevisionFile));
    }
    $maxRevision = $this->getMaxRevision($dbvRevisionsDir);

    if ($currentRevision == $maxRevision) {
      echo 'Database up to date.' . PHP_EOL;
      return;
    }

    // Load schema
    if (is_null($currentRevision)) {
      echo 'Must initialize the database before updating it.' . PHP_EOL;
      return;
    }

    // Run revisions
    $revision = $this->runRevisions($dbvRevisionsDir, $currentRevision);

    // Update revision
    file_put_contents($dbvMetaDir . DIRECTORY_SEPARATOR . 'revision', $revision);

    // Update history tables
    if (array_key_exists('--with-history', $this->runOptions)) {
      $this->updateHistoryDatabase();
    }

    echo 'Database up to date.' . PHP_EOL;
    return;
  }

  private function loadSchema($schemaDir, $schemaFiles) {
    $_database   = $this->_database;
    $errorSchema = [];
    foreach ($schemaFiles as $schemaFile) {
      if ($schemaFile === '.' || $schemaFile === '..' || !(substr($schemaFile, -4) === '.sql')) {
        continue;
      }
      $file = $schemaDir. DIRECTORY_SEPARATOR . $schemaFile;
      if (!file_exists($file)) {
        continue;
      }
      $content = file_get_contents($file);
      try {
        $this->$_database->query($content);
      }
      catch (Exception $e) {
        $errorSchema[] = $schemaFile;
      }
    }
    return $errorSchema;
  }

  private function runRevisions($dbvRevisionsDir, $minimumRevision = 0) {
    $_database      = $this->_database;
    $revisionNumber = 0;
    $revisions = scandir($dbvRevisionsDir);
    foreach ($revisions as $revision) {
      if ($revision === '.' || $revision === '..') {
        continue;
      }
      if (!is_dir($dbvRevisionsDir. DIRECTORY_SEPARATOR . $revision)) {
        continue;
      }
      if ($revision <= $minimumRevision) {
        continue;
      }
      $revisionDir = $dbvRevisionsDir. DIRECTORY_SEPARATOR . $revision;

      $revisionFiles = scandir($revisionDir);
      $this->$_database->query("SET FOREIGN_KEY_CHECKS=0");
      foreach ($revisionFiles as $revisionFile) {
        if ($revisionFile === '.' || $revisionFile === '..' || (!(substr($revisionFile, -4) === '.sql') && $revision <= 62)) {
          continue;
        }
        $file = $revisionDir. DIRECTORY_SEPARATOR . $revisionFile;
        if (!file_exists($file)) {
          continue;
        }

        $content = file_get_contents($file);
        $this->$_database->query($content);
      }
      $this->$_database->query("SET FOREIGN_KEY_CHECKS=1");
      $revisionNumber = $revision;
    }

    return $revisionNumber;
  }

  private function getMaxRevision($dbvRevisionsDir) {
    foreach (new DirectoryIterator($dbvRevisionsDir) as $file) {
        if ($file->isDir() && !$file->isDot() && is_numeric($file->getBasename())) {
            $return[] = $file->getBasename();
        }
    }

    rsort($return, SORT_NUMERIC);
    return $return[0];
  }
}
