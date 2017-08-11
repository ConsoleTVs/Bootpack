<?php

namespace ConsoleTVs\Bootpack\Classes;

use ConsoleTVs\Support\Helpers;
use Illuminate\Support\Collection;

class Package
{
    public $name;
    public $description;
    public $author;
    public $license;
    public $php;
    public $namespace;

    public function __construct($name)
    {
        $this->name = $name;
        $this->author = config('bootpack.default_author');
        $this->license = config('bootpack.default_license');
        $this->description = config('bootpack.default_description');
        $this->php = config('bootpack.default_php');
        $e_name = explode('/', $name);
        $this->namespace = ucfirst($e_name[0]) . '\\' . ucfirst($e_name[1]);
    }

    public function json()
    {
        return Collection::make([
            'name' => $this->name,
            'description' => $this->description,
            'license' => $this->license,
            'type' => 'library',
            'require' => [
                'php' => ">={$this->php}",
                'illuminate/support' => '5.*'
            ],
            'author' => [
                'name' => explode('<', $this->author)[0],
                'email' => Helpers::getBetween('<', '>', $this->author)
            ],
            'autoload' => [
                'psr-4' => [
                    $this->namespace . '\\' => 'src/',
                ]
            ],
            'extra' => [
                'providers' => [
                    $this->namespace . '\\' . ucfirst(explode('/', $this->name)[0]) . 'ServiceProvider'
                ]
            ],
            'minimum-stability' => "dev",
        ])->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    public function author($author)
    {
        $this->author = $author;

        return $this;
    }

    public function license($license)
    {
        $this->license = $license;

        return $this;
    }

    public function php($php)
    {
        $this->php = $php;

        return $this;
    }

    public function namespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }
}
