<?php

use Phalcon\Cli\Task;

use \GetOpt\GetOpt;
use \GetOpt\Option;
use \GetOpt\ArgumentException;
use \GetOpt\ArgumentException\Missing;

class BaseTask extends Task
{
  protected $getOpt = null;
  protected $requiredOptions = [];

  public function initialize()
  {
    $this->getOpt = new GetOpt();

    // define help option
    $this->getOpt->addOptions([

      Option::create('h', 'help', GetOpt::NO_ARGUMENT)
          ->setDescription('Display program help'),

    ]);
  }

  protected function processGetOpt() {
    // process arguments and catch user errors
    try {
      try {
        $this->getOpt->process();

        if ($this->getOpt->getOption('help')) {
          echo $this->getOpt->getHelpText();
          exit;
        }

      } catch (Missing $exception) {
        echo $exception->getMessage().PHP_EOL;
        // catch missing exceptions if help is requested
        if (!$this->getOpt->getOption('help')) {
          throw $exception;
        }
      }
    } catch (ArgumentException $exception) {
      file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
      echo PHP_EOL . $this->getOpt->getHelpText();
      exit;
    }
  }

  protected function checkRequiredOptions($requiredOptions) {
    // check for required options
    foreach ($requiredOptions as $requiredOption) {
      if (!$this->getOpt->getOption($requiredOption)) {
          echo $this->getOpt->getHelpText();
          exit;
      }
    }

  }
}
