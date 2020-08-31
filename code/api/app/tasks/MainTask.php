<?php

use Phalcon\Cli\Task;

use Phalcon\FormatLibrary;

use App\Models\GalleryImages;
use App\Models\GalleryImageCategories;
use App\Models\GalleryImageCategoryGalleryImageLkp;
use App\Models\Hashtag;
use App\Models\Images;
use App\Models\Session;
use App\Models\User;

use Phalcon\SimpleImage;

class MainTask extends Task
{
    public function mainAction()
    {
        $this->echoUsage();
    }

    private function echoUsage() {
      echo 'Usage: task [task] [action] [options]' . PHP_EOL;
      echo 'Tasks:' . PHP_EOL;
      echo '- database Preforms database related tasks.' . PHP_EOL;
      echo '- generate Preforms code generation tasks.' . PHP_EOL;
      echo '- omi      Get omi information.' . PHP_EOL;
    }
}
