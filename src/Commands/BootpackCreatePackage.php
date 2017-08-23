<?php

namespace ConsoleTVs\Bootpack\Commands;

use ConsoleTVs\Support\Helpers;
use Illuminate\Console\Command;
use ConsoleTVs\Bootpack\Classes\Package;

class BootpackCreatePackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bootpack:create {name : Package name} {--path : Location to create the package}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new package';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = explode('/', $this->argument('name'));
        $p_name = $name[1];
        $name = $name[0] . '/' . $name[1];
        $path = $this->option('path') ? base_path($this->option('path')) : base_path(config('bootpack.base_path'));
        $path = $path . '/' . $name;

        $this->logo('By Èrik Campobadal - erik.cat');

        $this->comment('The package root will be: ' . $path);
        if ($this->confirm('The package creation is going to start, type yes to begin', 'yes')) {
            if (!is_dir($path)) {
                $this->comment('Creating the root folder...');
                mkdir($path, 0777, true);
                $this->info("Project root created at: {$path}");
                $this->comment('Collecting package information...');

                $package = $this->package($name);

                $this->comment('Great, confirm the following data before we go ahead!');

                $this->table(array_keys(get_object_vars($package)), [get_object_vars($package)]);

                while (!$this->confirm('Everything looks cool?', 'yes')) {
                    $this->comment('Woah! Let me ask you everything again!');

                    $package = $this->package($name);

                    $this->comment('Great, confirm the following data before we go ahead!');

                    $this->table(array_keys(get_object_vars($package)), [get_object_vars($package)]);
                };

                $this->comment('Fantastic! Let me create the composer.json for your...');

                file_put_contents($path . '/composer.json', $package->json());

                $this->info("The package configuration has been saved in " . $path . '/composer.json');

                $this->comment('Woah, this is getting dirty, lets create the package structure');

                Helpers::copyDir(__DIR__ . '/../Source', $path . '/src');

                $this->info('Package structure created');

                $this->comment("Things are going fast artisan, let's add your package information into them");

                Helpers::massStrReplaceFile('{{ NAMESPACE }}', $package->namespace, $path);
                Helpers::massStrReplaceFile('{{ NAME }}', $p_name, $path);
                Helpers::massStrReplaceFile('{{ UCNAME }}', ucfirst($p_name), $path);

                rename(
                    $path . '/src/Config/config.php',
                    $path . '/src/Config/' . $p_name . '.php'
                );
                rename(
                    $path . '/src/ServiceProvider.php',
                    $path . '/src/' . ucfirst($p_name) . 'ServiceProvider.php'
                );
                rename(
                    $path . '/src/Controllers/Controller.php',
                    $path . '/src/Controllers/' . ucfirst($p_name) . 'Controller.php'
                );
                rename(
                    $path . '/src/Commands/Command.php',
                    $path . '/src/Commands/' . ucfirst($p_name) . 'Command.php'
                );
                rename(
                    $path . '/src/Middleware/Middleware.php',
                    $path . '/src/Middleware/' . ucfirst($p_name) . 'Middleware.php'
                );
                rename(
                    $path . '/src/Migrations/2017_08_11_171401_create_some_table.php',
                    $path . '/src/Migrations/2017_08_11_171401_create_' . ucfirst($p_name) . '_table.php'
                );
                rename(
                    $path . '/src/Contracts/Contract.php',
                    $path . '/src/Contracts/' . ucfirst($p_name) . 'Contract.php'
                );
                rename(
                    $path . '/src/Classes/Class.php',
                    $path . '/src/Classes/' . ucfirst($p_name) . 'Class.php'
                );

                $this->info('Yey! The package structure is ready for action!');
                $this->comment('Hey we are almost done with this! Let me add the class loader to the current composer project...');

                $l_composer = json_decode(file_get_contents(base_path('composer.json')), true);
                $l_composer['autoload']['psr-4'][$package->namespace.'\\'] = str_replace(base_path().'/', '', $path).'/src';
                file_put_contents(
                    base_path('composer.json'),
                    json_encode($l_composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );

                $this->info('Your main composer.json file has been updated.');
                $this->comment('Seems like the only thing left is to dump the composer autoload...');
                if ($this->confirm('Do you want to dump composer auto-load?', 'yes')) {
                    if ($this->confirm('Do you have composer command in your path?', 'yes')) {
                        print shell_exec('cd ' . base_path() . ' && composer dump-autoload');
                    } else {
                        if ($this->confirm('Do you want me to try using the downloaded binary?', 'yes')) {
                            $c_path = base_path('vendor/composer/composer/bin/composer');
                            print shell_exec('cd ' . base_path() . ' && ' . $c_path . ' dump-autoload');
                        } else {
                            $c_path = $this->ask('Please specify the full composer executable file path');
                            print shell_exec('cd ' . base_path() . ' && ' . $c_path . ' dump-autoload');
                        }
                    }
                    $this->table(['namespace', 'path'], [[
                        'namespace' => $package->namespace,
                        'path' => str_replace(base_path() . '/', '', $path)
                    ]]);
                } else {
                    $this->line('Skipping the composer dump autoload...');
                    $this->line('Pleae manually dump the autoload: composer dump-autoload');
                }

                if ($this->confirm('Do you want to create REAMDE.md?', 'yes')) {
                    $this->comment('Creating REAMDE.md...');

                    $readme = fopen("$path/README.md", "w");
                    fwrite($readme, "# $package->name\n\n$package->description");
                    fclose($readme);

                    $this->info('Very cool! The REAMDE.md has been created!');
                } else {
                    $this->line('Skipping the creation of REAMDE.md...');
                }

                $this->comment('Searching for license File...');

                $licenses = scandir(__DIR__ . '/../Licenses');

                if (in_array($package->license, $licenses)) {
                    copy(__DIR__ . "/../Licenses/$package->license", "$path/LICENSE");
                    Helpers::strReplaceFile('{{ YEAR }}', date('Y'), "$path/LICENSE");
                    Helpers::strReplaceFile('{{ AUTHOR }}', $package->author, "$path/LICENSE");
                    $this->info('Nice! The LICENSE file is ready!');
                } else {
                    $this->error("Whoops! The License of your package is unknown for Bootpack so cannot be created automatically");
                }


                if ($this->confirm('Do you want to continue? Make sure the auto-load was dumped', 'yes')) {
                    $this->comment('Registering the service provider in the current laravel application...');

                    Helpers::strReplaceFile(
                        'ConsoleTVs\\Bootpack\\BootpackServiceProvider::class',
                        "ConsoleTVs\\Bootpack\\BootpackServiceProvider::class,\n\t\t"
                        . $package->namespace . "\\" . ucfirst($p_name) . 'ServiceProvider::class',
                        base_path('config/app.php')
                    );

                    $this->info('Very cool! The service provider is registered!');

                    if ($this->confirm('Feeling like creating the git repository as well?', 'yes')) {
                        if ($this->confirm('Do you have git command in your path?', 'yes')) {
                            print shell_exec('cd ' . $path . ' && git init');
                        } else {
                            $g_path = $this->ask('Please specify the full git executable file path');
                            print shell_exec('cd ' . $path . ' && ' . $g_path . '  init');
                        }
                    }

                    $this->line('');
                    $this->logo('Please donate if you found this usefull');
                    $this->info('Congratulations! Your package is created, configured and ready to be coded :)');
                    $this->line('Package location: ' . $path);
                } else {
                    $this->line("Uhhh... Well... You'll need to dump the auto-load next time...");
                    $this->line("You'll also need to register the package service provider to the application before developing...");
                    $this->info('Aborted package extra steps...');
                    $this->line('Package location: ' . $path);
                }
            } else {
                $this->error("The folder '{$path}' already exists");
            }
        }
    }

    protected function package($name)
    {
        $package = new Package($name);

        $package->author($this->ask("What is your name?", $package->author));
        $package->name($this->ask("What is the package name?", $package->name));
        $package->description($this->ask("What is the package description?", $package->description));
        $package->license($this->ask("What is the package license?", $package->license));
        $package->php($this->ask("What is the package min PHP version?", $package->php));
        $package->namespace($this->ask("What is the package namespace?", $package->namespace));

        return $package;
    }

    protected function logo($msg = '')
    {
        $this->line('');
        $this->line("  ____              _                    _    ");
        $this->line(" |  _ \            | |                  | |   ");
        $this->line(" | |_) | ___   ___ | |_ _ __   __ _  ___| | __");
        $this->line(" |  _ < / _ \ / _ \| __| '_ \ / _` |/ __| |/ /");
        $this->line(" | |_) | (_) | (_) | |_| |_) | (_| | (__|   <   " . $msg);
        $this->line(" |____/ \___/ \___/ \__| .__/ \__,_|\___|_|\_\\");
        $this->line("                       | |                    ");
        $this->line("                       |_|                    ");
        $this->line('');
    }
}
