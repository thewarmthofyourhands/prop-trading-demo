<?php

declare(strict_types=1);

namespace App;

use Eva\Env\Env;

class TestApplication extends Application
{
    protected function initializeChdir(): void
    {
        chdir(dirname($_SERVER["SCRIPT_FILENAME"]) . '/../../..');
    }

    protected function initializeEnv(): void
    {
        $this->env = new Env();
        $this->env->load($this->getProjectDir() . '/.env.test');
    }
}
